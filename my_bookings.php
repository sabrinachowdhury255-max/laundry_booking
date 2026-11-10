<?php

/* LaundryWise file overview: Displays the signed-in resident’s booking records and available booking actions. User filtering is important so one resident sees only their own bookings. */

/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();
require_once "config/database.php";

// Make sure the resident is logged in.
if (!isset($_SESSION["user_id"])) {
    /* Explanation: Redirects the browser after processing, commonly to prevent resubmitting a form or to enforce login flow. */
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$timezone = new DateTimeZone("Europe/Copenhagen");
$now = new DateTime("now", $timezone);

// Retrieve this resident's bookings.
$sql = "SELECT
            bookings.id,
            bookings.booking_date,
            bookings.start_time,
            bookings.end_time,
            bookings.status,
            machines.machine_name
        FROM bookings
        INNER JOIN machines ON bookings.machine_id = machines.id
        WHERE bookings.user_id = ?
        ORDER BY bookings.booking_date DESC, bookings.start_time DESC";

$stmt = $conn->prepare($sql);
/* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
$upcomingCount = 0;
$confirmedCount = 0;
$pastCount = 0;

while ($booking = $result->fetch_assoc()) {
    $bookingEnd = new DateTime(
        $booking["booking_date"] . " " . $booking["end_time"],
        $timezone
    );
    $bookingStart = new DateTime(
        $booking["booking_date"] . " " . $booking["start_time"],
        $timezone
    );
    $booking["booking_has_passed"] = $bookingEnd <= $now;
    $booking["booking_is_upcoming"] = $bookingStart > $now
        && strtolower($booking["status"]) === "confirmed";
    $booking["formatted_date"] = $bookingStart->format("D, d M Y");
    $booking["formatted_start"] = $bookingStart->format("H:i");
    $booking["formatted_end"] = $bookingEnd->format("H:i");

    if ($booking["booking_is_upcoming"]) {
        $upcomingCount++;
    }
    if (strtolower($booking["status"]) === "confirmed") {
        $confirmedCount++;
    }
    if ($booking["booking_has_passed"]) {
        $pastCount++;
    }

    $bookings[] = $booking;
}

function bookingStatusClass($status, $hasPassed) {
    if ($hasPassed) return "status-past";
    switch (strtolower($status)) {
        case "confirmed": return "status-confirmed";
        case "cancelled":
        case "canceled": return "status-cancelled";
        case "pending": return "status-pending";
        default: return "status-default";
    }
}

function bookingStatusLabel($status, $hasPassed) {
    if ($hasPassed && strtolower($status) === "confirmed") return "Completed";
    return ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | LaundryWise</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/laundrywise-nav.css">
    <style>
        :root {
            --lw-navy: #193664;
            --lw-blue: #2864ed;
            --lw-muted: #667995;
            --lw-border: #dce5f5;
            --lw-purple: #8b5cf6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: #17233c;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(ellipse at 8% 8%, rgba(211,196,255,.68), transparent 34%),
                radial-gradient(ellipse at 95% 22%, rgba(190,220,255,.7), transparent 32%),
                linear-gradient(135deg, #f5f0ff 0%, #eef4ff 55%, #f8f0ff 100%);
        }
        .lw-page {
            width: min(1200px, calc(100% - 36px));
            margin: 26px auto 48px;
        }
        .lw-nav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 18px; padding: 15px 22px; margin-bottom: 24px;
            background: rgba(255,255,255,.88); border: 1px solid rgba(255,255,255,.95);
            border-radius: 22px; box-shadow: 0 8px 28px rgba(53,76,135,.08);
            backdrop-filter: blur(12px);
        }
        .lw-brand { color: var(--lw-navy); font-size: 20px; font-weight: 800; text-decoration: none; letter-spacing: -.4px; }
        .lw-nav-links { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .lw-nav-links a {
            color: #50617e; text-decoration: none; font-size: 14px; font-weight: 650;
            padding: 10px 13px; border-radius: 12px; transition: .2s ease;
        }
        .lw-nav-links a:hover, .lw-nav-links a.active { color: var(--lw-blue); background: #eef3ff; }
        .lw-hero {
            padding: clamp(24px, 4vw, 42px); border: 1px solid rgba(255,255,255,.95);
            border-radius: 28px; background: rgba(255,255,255,.78);
            box-shadow: 0 18px 48px rgba(63,82,145,.08);
        }
        .lw-eyebrow { color: #526dff; font-size: 12px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase; }
        .lw-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; }
        h1 { margin: 8px 0 8px; color: var(--lw-navy); font-size: clamp(30px, 4vw, 43px); line-height: 1.12; letter-spacing: -1.2px; }
        .lw-subtitle { margin: 0; color: var(--lw-muted); font-size: 16px; line-height: 1.6; }
        .lw-primary, .lw-secondary, .lw-cancel {
            display:inline-flex; align-items:center; justify-content:center; gap:8px;
            border-radius: 13px; padding: 12px 17px; font-weight: 750; font-size: 14px;
            text-decoration:none; cursor:pointer; transition:transform .18s, box-shadow .18s;
        }
        .lw-primary { color:white; background:linear-gradient(135deg,#3676ff,#6447ed); box-shadow:0 7px 16px rgba(49,99,231,.2); white-space:nowrap; }
        .lw-primary:hover { transform:translateY(-1px); box-shadow:0 10px 20px rgba(49,99,231,.26); }
        .lw-secondary { color:var(--lw-navy); background:white; border:1px solid var(--lw-border); }
        .lw-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:15px; margin:26px 0 30px; }
        .lw-stat { background:#fff; border:1px solid var(--lw-border); border-radius:19px; padding:19px 21px; }
        .lw-stat-top { display:flex; align-items:center; gap:10px; color:var(--lw-muted); font-size:13px; }
        .lw-stat-icon { display:grid; place-items:center; width:37px; height:37px; border-radius:12px; background:#edf2ff; font-size:19px; }
        .lw-stat-number { margin-top:10px; color:var(--lw-navy); font-size:30px; font-weight:850; line-height:1; }
        .lw-stat-note { margin-top:7px; color:#8190a8; font-size:12px; }
        .lw-section-head { display:flex; justify-content:space-between; align-items:end; gap:16px; margin:0 0 16px; }
        h2 { margin:0; color:var(--lw-navy); font-size:23px; letter-spacing:-.4px; }
        .lw-section-head p { margin:6px 0 0; color:var(--lw-muted); font-size:14px; }
        .lw-booking-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:17px; }
        .lw-booking-card { overflow:hidden; background:#fff; border:1px solid var(--lw-border); border-radius:21px; box-shadow:0 8px 22px rgba(53,76,135,.045); }
        .lw-card-banner { display:flex; align-items:center; gap:15px; padding:20px 21px; background:linear-gradient(120deg,#e8e2ff,#e1edff 58%,#f5e7ff); }
        .lw-machine-art { display:grid; place-items:center; flex:0 0 66px; height:66px; border-radius:18px; background:rgba(255,255,255,.75); font-size:34px; }
        .lw-machine-title { min-width:0; flex:1; }
        .lw-machine-title h3 { margin:0 0 5px; color:var(--lw-navy); font-size:18px; }
        .lw-machine-title p { margin:0; color:#647795; font-size:13px; }
        .lw-status { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:7px 10px; font-size:11px; font-weight:800; white-space:nowrap; }
        .lw-status:before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
        .status-confirmed { color:#11764e; background:#dcf8e9; }
        .status-cancelled { color:#a83240; background:#ffe5e8; }
        .status-pending { color:#93600b; background:#fff2ce; }
        .status-past, .status-default { color:#64748b; background:#edf1f7; }
        .lw-card-body { padding:19px 21px 21px; }
        .lw-booking-details { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .lw-detail { padding:12px 13px; border-radius:13px; background:#f7f9fd; min-width:0; }
        .lw-detail span { display:block; color:#7787a1; font-size:12px; margin-bottom:6px; }
        .lw-detail strong { color:#263954; font-size:14px; overflow-wrap:anywhere; }
        .lw-card-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:17px; padding-top:15px; border-top:1px solid #edf1f7; }
        .lw-booking-id { color:#8b98ad; font-size:12px; }
        .lw-cancel { color:#b42335; background:#fff4f4; border:1px solid #ffd9dd; padding:9px 13px; }
        .lw-cancel:hover { background:#ffe8eb; }
        .lw-no-action { color:#8794a8; font-size:12px; }
        .lw-empty { padding:45px 22px; text-align:center; background:rgba(255,255,255,.9); border:1px solid var(--lw-border); border-radius:22px; }
        .lw-empty-icon { display:grid; place-items:center; width:70px; height:70px; margin:0 auto 16px; border-radius:22px; background:linear-gradient(135deg,#e8e2ff,#e1edff); font-size:32px; }
        .lw-empty h3 { margin:0 0 8px; color:var(--lw-navy); font-size:20px; }
        .lw-empty p { margin:0 auto 20px; max-width:430px; color:var(--lw-muted); font-size:14px; line-height:1.6; }
        @media (max-width:760px) {
            .lw-page { width:calc(100% - 22px); margin-top:12px; }
            .lw-nav { align-items:flex-start; flex-direction:column; padding:15px; border-radius:18px; }
            .lw-nav-links { width:100%; gap:4px; }
            .lw-nav-links a { padding:9px 10px; font-size:13px; }
            .lw-hero { padding:22px 16px; border-radius:22px; }
            .lw-hero-top { flex-direction:column; align-items:stretch; }
            .lw-primary { align-self:flex-start; }
            .lw-stats { gap:9px; }
            .lw-stat { padding:13px 12px; border-radius:15px; }
            .lw-stat-number { font-size:25px; }
            .lw-stat-top { display:block; font-size:12px; }
            .lw-stat-icon { margin-bottom:9px; }
            .lw-booking-list { grid-template-columns:1fr; }
        }
        @media (max-width:420px) {
            .lw-stats { grid-template-columns:1fr; }
            .lw-stat { display:flex; align-items:center; gap:12px; }
            .lw-stat-icon { flex-shrink:0; margin:0; }
            .lw-stat-number { margin:0 0 0 auto; }
            .lw-stat-note { display:none; }
            .lw-booking-details { grid-template-columns:1fr 1fr; }
            .lw-status { font-size:10px; padding:6px 8px; }
            .lw-card-banner, .lw-card-body { padding-left:15px; padding-right:15px; }
        }
    </style>
</head>
<body>
<div class="lw-page">
    <nav class="dashboard-nav lw-prototype-nav" aria-label="Main navigation">
        <a class="lw-brand" href="dashboard.php" aria-label="LaundryWise home"><span class="lw-brand-icon" aria-hidden="true">▣</span><span class="lw-brand-name">LaundryWise</span></a>
        <div class="lw-prototype-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a class="active" href="my_bookings.php" aria-current="page">My Bookings</a>
            <a href="booking_help.php">Booking Help</a>
            <a href="policies.php">Policies &amp; Rules</a>
            <a href="settings.php">My Account</a>
            <a class="lw-nav-logout" href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="lw-hero">
        <div class="lw-hero-top">
            <div>
                <div class="lw-eyebrow">Your laundry at a glance</div>
                <h1>My Bookings</h1>
                <p class="lw-subtitle">
                    Welcome, <?php echo htmlspecialchars($_SESSION["name"] ?? "Resident"); ?>.
                    Review your reservations and manage upcoming laundry slots.
                </p>
            </div>
            <a class="lw-primary" href="dashboard.php#recommendation-search">＋ Find a laundry slot <span aria-hidden="true">→</span></a>
        </div>

        <section class="lw-stats" aria-label="Booking summary">
            <div class="lw-stat">
                <div class="lw-stat-top"><span class="lw-stat-icon">🗓️</span>Upcoming bookings</div>
                <div class="lw-stat-number"><?php echo $upcomingCount; ?></div>
                <div class="lw-stat-note">Confirmed future reservations</div>
            </div>
            <div class="lw-stat">
                <div class="lw-stat-top"><span class="lw-stat-icon">✓</span>Confirmed bookings</div>
                <div class="lw-stat-number"><?php echo $confirmedCount; ?></div>
                <div class="lw-stat-note">Bookings with confirmed status</div>
            </div>
            <div class="lw-stat">
                <div class="lw-stat-top"><span class="lw-stat-icon">◷</span>Past slots</div>
                <div class="lw-stat-number"><?php echo $pastCount; ?></div>
                <div class="lw-stat-note">Slots whose end time has passed</div>
            </div>
        </section>

        <section aria-labelledby="bookingListTitle">
            <div class="lw-section-head">
                <div>
                    <h2 id="bookingListTitle">Your reservations</h2>
                    <p>Booking details, status, and available actions.</p>
                </div>
                <a class="lw-secondary" href="dashboard.php">← Dashboard</a>
            </div>

            <?php if (count($bookings) > 0): ?>
                <div class="lw-booking-list">
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $statusClass = bookingStatusClass(
                            $booking["status"],
                            $booking["booking_has_passed"]
                        );
                        $statusLabel = bookingStatusLabel(
                            $booking["status"],
                            $booking["booking_has_passed"]
                        );
                        ?>
                        <article class="lw-booking-card">
                            <div class="lw-card-banner">
                                <div class="lw-machine-art" aria-hidden="true">🧺</div>
                                <div class="lw-machine-title">
                                    <h3><?php echo htmlspecialchars($booking["machine_name"]); ?></h3>
                                    <p>Reservation #<?php echo (int)$booking["id"]; ?></p>
                                </div>
                                <span class="lw-status <?php echo htmlspecialchars($statusClass); ?>">
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </span>
                            </div>
                            <div class="lw-card-body">
                                <div class="lw-booking-details">
                                    <div class="lw-detail">
                                        <span>Booking date</span>
                                        <strong><?php echo htmlspecialchars($booking["formatted_date"]); ?></strong>
                                    </div>
                                    <div class="lw-detail">
                                        <span>Time slot</span>
                                        <strong><?php echo htmlspecialchars($booking["formatted_start"] . " – " . $booking["formatted_end"]); ?></strong>
                                    </div>
                                </div>
                                <div class="lw-card-footer">
                                    <span class="lw-booking-id">Booking #<?php echo (int)$booking["id"]; ?></span>
                                    <?php if (
                                        strtolower($booking["status"]) === "confirmed"
                                        && !$booking["booking_has_passed"]
                                    ): ?>
                                        <a
                                            class="lw-cancel"
                                            href="cancel_booking.php?id=<?php echo (int)$booking["id"]; ?>"
                                            onclick="return confirm('Are you sure you want to cancel this booking?');"
                                        >Cancel booking</a>
                                    <?php else: ?>
                                        <span class="lw-no-action">No action available</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="lw-empty">
                    <div class="lw-empty-icon" aria-hidden="true">🧺</div>
                    <h3>No bookings yet</h3>
                    <p>You haven't made any laundry reservations. Find a suitable machine and one-hour slot to get started.</p>
                    <a class="lw-primary" href="dashboard.php#recommendation-search">Find a laundry slot →</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
