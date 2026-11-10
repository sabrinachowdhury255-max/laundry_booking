<?php

/* LaundryWise file overview: Returns digital availability information for the requested machines and booking period. Availability here is based on system records, not physical sensors. */
 
 
/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start(); 
 
require_once "config/database.php"; 
 
if (!isset($_SESSION["user_id"])) { 
    header("Location: login.php"); 
    exit(); 
} 
 
if (!isset($_GET["machine_id"])) { 
    die("Machine not selected."); 
} 
 
$machine_id = intval($_GET["machine_id"]); 
 
$sql = "SELECT id, machine_name, machine_type, status 
        FROM machines 
        WHERE id = ? 
        AND status = 'active'"; 
 
$stmt = $conn->prepare($sql); 
/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param("i", $machine_id); 
$stmt->execute(); 
 
$result = $stmt->get_result(); 
 
if ($result->num_rows !== 1) { 
    die("Machine not found."); 
} 
 
$machine = $result->fetch_assoc(); 


/* 
 * Use Copenhagen time for the booking system.
 */ 

/* Explanation: Uses Denmark’s local timezone so booking date/time validation follows the residence’s local clock. */
$timezone = new DateTimeZone("Europe/Copenhagen"); 
$now = new DateTime("now", $timezone); 

$current_date = $now->format("Y-m-d"); 
$current_time = $now->format("H:i"); 
 
 
$selected_date = $_GET["date"] ?? $current_date; 
 
$date_object = DateTime::createFromFormat( 
    "Y-m-d", 
    $selected_date 
); 
 
if ( 
    !$date_object || 
    $date_object->format("Y-m-d") !== $selected_date 
) { 
    $selected_date = $current_date; 
} 
 
if ($selected_date < $current_date) { 
    $selected_date = $current_date; 
} 
 
 
/* 
 * Check whether a slot is booked. 
 */ 
 
function isBooked( 
    $conn, 
    $machine_id, 
    $selected_date, 
    $start_time, 
    $end_time 
) { 
 
    $sql = "SELECT id 
            FROM bookings 
            WHERE machine_id = ? 
            AND booking_date = ? 
            AND status = 'confirmed' 
            AND start_time < ? 
            AND end_time > ? 
            LIMIT 1"; 
 
    $stmt = $conn->prepare($sql); 
 
    $stmt->bind_param( 
        "isss", 
        $machine_id, 
        $selected_date, 
        $end_time, 
        $start_time 
    ); 
 
    $stmt->execute(); 
 
    $result = $stmt->get_result(); 
 
    return $result->num_rows > 0; 
} 
 
 
/* 
 * Check whether the slot has already passed today. 
 */ 
 
function isPastSlot( 
    $selected_date, 
    $start_time, 
    $current_date, 
    $current_time 
) { 
 
    if ($selected_date !== $current_date) { 
        return false; 
    } 
 
    return $start_time <= $current_time; 
} 
 
?> 
 
<!DOCTYPE html> 
 
<html lang="en"> 
 
<head> 
 
<meta charset="UTF-8"> 
 
<meta 
    name="viewport" 
    content="width=device-width, initial-scale=1.0" 
> 
 
<title> 
    <?php 
    echo htmlspecialchars( 
        $machine["machine_name"] 
    ); 
    ?> 
    - Availability 
</title> 
 
<link 
    rel="stylesheet" 
    href="css/style.css" 
> 
 
</head> 
 
<body> 
 
<div class="container"> 
 
<h1> 
    <?php 
    echo htmlspecialchars( 
        $machine["machine_name"] 
    ); 
    ?> 
</h1> 
 
<p> 
    <strong>Type:</strong> 
    <?php 
    echo htmlspecialchars( 
        $machine["machine_type"] 
    ); 
    ?> 
</p> 
 
<p> 
    <strong>Status:</strong> 
    <?php 
    echo htmlspecialchars( 
        $machine["status"] 
    ); 
    ?> 
</p> 
 
 
<h2>Check Availability</h2> 
 
 
<form 
    method="GET" 
    action="availability.php" 
    class="date-form" 
> 
 
    <input 
        type="hidden" 
        name="machine_id" 
        value="<?php 
        echo htmlspecialchars($machine_id); 
        ?>" 
    > 
 
    <label for="date"> 
        Select Date: 
    </label> 
 
    <input 
        type="date" 
        id="date" 
        name="date" 
        value="<?php 
        echo htmlspecialchars($selected_date); 
        ?>" 
        min="<?php 
        echo $current_date; 
        ?>" 
        required 
    > 
 
    <button type="submit"> 
        Check Availability 
    </button> 
 
</form> 
 
 
<h2> 
    Time Slots for 
    <?php 
    echo htmlspecialchars($selected_date); 
    ?> 
</h2> 
 
 
<?php 
 
for ($hour = 8; $hour < 22; $hour++) { 
 
    $start_time = 
        sprintf( 
            "%02d:00", 
            $hour 
        ); 
 
    $end_time = 
        sprintf( 
            "%02d:00", 
            $hour + 1 
        ); 
 
    /* 
     * MySQL TIME values. 
     */ 
 
    $database_start_time = 
        $start_time . ":00"; 
 
    $database_end_time = 
        $end_time . ":00"; 
 
 
    $booked = isBooked( 
        $conn, 
        $machine_id, 
        $selected_date, 
        $database_start_time, 
        $database_end_time 
    ); 
 
 
    $past = isPastSlot( 
        $selected_date, 
        $start_time, 
        $current_date, 
        $current_time 
    ); 
 
 
    if ($past) { 
 
        $slot_class = "slot past"; 
 
    } elseif ($booked) { 
 
        $slot_class = "slot booked"; 
 
    } else { 
 
        $slot_class = "slot available"; 
    } 
 
?> 
 
<div class="<?php echo $slot_class; ?>"> 
 
    <span> 
        <?php 
        echo htmlspecialchars( 
            $start_time 
        ); 
        ?> 
 
        - 
 
        <?php 
        echo htmlspecialchars( 
            $end_time 
        ); 
        ?> 
    </span> 
 
 
    <?php if ($past): ?> 
 
        <span> 
            Past 
        </span> 
 
 
    <?php elseif ($booked): ?> 
 
        <span> 
            Booked 
        </span> 
 
        <a 
            href="recommendations.php?machine_id=<?php 
            echo urlencode( 
                $machine_id 
            ); 
            ?>&date=<?php 
            echo urlencode( 
                $selected_date 
            ); 
            ?>&start_time=<?php 
            echo urlencode( 
                $start_time 
            ); 
            ?>&end_time=<?php 
            echo urlencode( 
                $end_time 
            ); 
            ?>" 
        > 
            View altervatives 
        </a> 
 
 
    <?php else: ?> 
 
        <a 
            href="book.php?machine_id=<?php 
            echo urlencode( 
                $machine_id 
            ); 
            ?>&date=<?php 
            echo urlencode( 
                $selected_date 
            ); 
            ?>&start_time=<?php 
            echo urlencode( 
                $start_time 
            ); 
            ?>&end_time=<?php 
            echo urlencode( 
                $end_time 
            ); 
            ?>" 
        > 
            Book this slot 
        </a> 
 
    <?php endif; ?> 
 
</div> 
 
<?php 
 
} 
 
?> 
 
 
<a 
    href="dashboard.php" 
    class="back" 
> 
    Back to Dashboard 
</a> 
 
</div> 
 
</body> 
 
</html>