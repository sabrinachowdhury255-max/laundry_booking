<?php

/* LaundryWise file overview: Resident dashboard that presents the main booking entry point and resident-facing information after login. */




/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();



require_once "config/database.php";



if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");

    exit();

}



$sql = "SELECT id, machine_name, machine_type, status

        FROM machines

        ORDER BY machine_type, id";



$result = $conn->query($sql);

// Dashboard metrics and booking searches use Copenhagen local time.
date_default_timezone_set("Europe/Copenhagen");
/* Explanation: Uses Denmark’s local timezone so booking date/time validation follows the residence’s local clock. */
$dashboardTimezone = new DateTimeZone("Europe/Copenhagen");
$dashboardNow = new DateTime("now", $dashboardTimezone);
$dashboardToday = $dashboardNow->format("Y-m-d");
$dashboardNowTime = $dashboardNow->format("H:i:s");
$dashboardNextWeek = (clone $dashboardNow)->modify("+7 days")->format("Y-m-d");

$upcomingBookingsCount = 0;
$upcomingSql = "SELECT COUNT(*) AS total
                FROM bookings
                WHERE user_id = ?
                  AND status = 'confirmed'
                  AND (booking_date > ? OR (booking_date = ? AND end_time > ?))";
$upcomingStmt = $conn->prepare($upcomingSql);
if ($upcomingStmt) {
    /* Explanation: Binds user-provided values as parameters, helping prevent SQL injection and keeping values separate from SQL syntax. */
    $upcomingStmt->bind_param("isss", $_SESSION["user_id"], $dashboardToday, $dashboardToday, $dashboardNowTime);
    if ($upcomingStmt->execute()) {
        $upcomingRow = $upcomingStmt->get_result()->fetch_assoc();
        $upcomingBookingsCount = (int)($upcomingRow["total"] ?? 0);
    }
    $upcomingStmt->close();
}

// Estimate overall demand for the next 7 days from confirmed bookings versus
// the number of hourly slots on active machines (08:00–21:00 start times).
$confirmedWeekBookings = 0;
$weekSql = "SELECT COUNT(*) AS total
            FROM bookings b
            INNER JOIN machines m ON m.id = b.machine_id
            WHERE b.status = 'confirmed'
              AND m.status = 'active'
              AND b.booking_date >= ?
              AND b.booking_date < ?";
$weekStmt = $conn->prepare($weekSql);
if ($weekStmt) {
    $weekStmt->bind_param("ss", $dashboardToday, $dashboardNextWeek);
    if ($weekStmt->execute()) {
        $weekRow = $weekStmt->get_result()->fetch_assoc();
        $confirmedWeekBookings = (int)($weekRow["total"] ?? 0);
    }
    $weekStmt->close();
}
// Machine arrays are populated below; demand level is calculated after the query loop.

// Allow residents to clear previously displayed recommendations.
if (isset($_GET["clear_recommendations"])) {
    unset($_SESSION["dashboard_recommendation_search"]);
    header("Location: dashboard.php#recommendation-search");
    exit();
}

// Recommendations are returned here by recommendations.php through the session.
$dashboard_recommendation_search = $_SESSION["dashboard_recommendation_search"] ?? null;
// Show search results for this response only, not indefinitely on later dashboard visits.
unset($_SESSION["dashboard_recommendation_search"]);



$washing_machines = [];

$dryers = [];



while ($machine = $result->fetch_assoc()) {



    if ($machine["machine_type"] === "Dryer") {

        $dryers[] = $machine;

    } else {

        $washing_machines[] = $machine;

    }

}

$activeMachineCount = count(array_filter(array_merge($washing_machines, $dryers), function ($machine) {
    return strtolower((string)$machine["status"]) === "active";
}));
$weeklySlotCapacity = $activeMachineCount * 14 * 7;
$weeklyOccupancyPercent = $weeklySlotCapacity > 0
    ? ($confirmedWeekBookings / $weeklySlotCapacity) * 100
    : 0;
if ($weeklyOccupancyPercent < 30) {
    $overallDemandLabel = "Low";
    $overallDemandClass = "low";
} elseif ($weeklyOccupancyPercent < 60) {
    $overallDemandLabel = "Moderate";
    $overallDemandClass = "moderate";
} else {
    $overallDemandLabel = "Busy";
    $overallDemandClass = "high";
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



    <title>LaundryWise Dashboard</title>



    <link rel="stylesheet" href="css/style.css">



    <style>



        /* Recommendation results shown on the resident dashboard */
        .lw-dashboard-results{margin:0 0 40px;padding:26px;border:1px solid #e2e8f0;border-radius:20px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.05);scroll-margin-top:20px}
        .lw-results-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}
        .lw-results-heading h2{margin:0 0 7px;color:#0f172a;font-size:24px}
        .lw-results-intro{margin:0;color:#64748b;line-height:1.55}
        .lw-results-clear{display:inline-flex;align-items:center;justify-content:center;padding:10px 13px;border:1px solid #bfdbfe;border-radius:10px;color:#1d4ed8;text-decoration:none;font-size:13px;font-weight:700;white-space:nowrap}
        .lw-results-clear:hover{background:#eff6ff}
        .lw-results-notice{margin:0 0 18px;padding:12px 14px;border:1px solid #dbeafe;border-radius:12px;background:#eff6ff;color:#1e40af;font-size:13px;line-height:1.55}
        .lw-results-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .lw-result-card{min-width:0;padding:20px;border:1px solid #e2e8f0;border-radius:16px;background:linear-gradient(180deg,#fff 0%,#fbfdff 100%);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
        .lw-result-card:hover{transform:translateY(-2px);border-color:#bfdbfe;box-shadow:0 12px 26px rgba(37,99,235,.08)}
        .lw-result-visual{display:flex;align-items:center;justify-content:center;min-height:155px;margin:-20px -20px 18px;border-radius:16px 16px 0 0;background:linear-gradient(135deg,#e9ddff 0%,#dceaff 48%,#f7e7ff 100%);overflow:hidden}
        .lw-result-visual svg{width:150px;height:140px;max-width:100%;filter:drop-shadow(0 9px 9px rgba(30,50,90,.18))}
        .lw-result-card-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:15px;flex-wrap:wrap}
        .lw-result-rank{color:#64748b;font-size:12px;font-weight:700}
        .lw-demand-badge{display:inline-flex;padding:6px 9px;border-radius:999px;font-size:11px;font-weight:700}
        .lw-demand-badge.low{background:#dcfce7;color:#166534}.lw-demand-badge.moderate{background:#fef3c7;color:#92400e}.lw-demand-badge.high{background:#fee2e2;color:#991b1b}
        .lw-result-card h3{margin:0 0 5px;color:#0f172a;font-size:19px}
        .lw-result-type{margin-bottom:18px;color:#64748b;font-size:13px}
        .lw-result-details{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
        .lw-result-details div{padding:11px;border-radius:10px;background:#f8fafc;min-width:0}
        .lw-result-details span{display:block;margin-bottom:5px;color:#64748b;font-size:11px}
        .lw-result-details strong{display:block;color:#0f172a;font-size:13px;overflow-wrap:anywhere}
        .lw-result-explanation{min-height:36px;margin:0 0 17px;color:#64748b;font-size:12px;line-height:1.5}
        .lw-result-book{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-size:13px;font-weight:700}
        .lw-result-book:hover{background:#1d4ed8}
        .lw-results-empty{padding:24px;border:1px dashed #cbd5e1;border-radius:14px;text-align:center;color:#334155}
        .lw-results-empty p{margin:7px 0 0;color:#64748b;font-size:13px}
        @media(max-width:760px){.lw-results-grid{grid-template-columns:1fr}.lw-results-heading{flex-direction:column}.lw-results-clear{width:100%;box-sizing:border-box}.lw-dashboard-results{padding:20px}}

        /* =========================================================

           ASSISTANT BUTTON

           ========================================================= */
        /* Resident overview cards: show overview first, recommendations only after a search */
        .lw-overview{margin:0 0 30px;padding:26px;border:1px solid rgba(220,218,250,.95);border-radius:22px;background:rgba(255,255,255,.84);box-shadow:0 12px 34px rgba(65,74,140,.07)}
        .lw-overview-stats-four{grid-template-columns:repeat(4,minmax(0,1fr))}
        .lw-overview-demand{display:inline-flex;align-items:center;padding:5px 11px;border-radius:999px;font-size:18px;line-height:1.2}
        .lw-overview-demand.low{background:#dcfce7;color:#166534}
        .lw-overview-demand.moderate{background:#fef3c7;color:#92400e}
        .lw-overview-demand.high{background:#fee2e2;color:#991b1b}
        .lw-overview-actions .lw-overview-action{cursor:default;text-decoration:none}
        .lw-overview-actions .lw-overview-action:hover{transform:none;box-shadow:none}
        @media(max-width:900px){.lw-overview-stats-four{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:560px){.lw-overview-stats-four,.lw-overview-actions-three{grid-template-columns:1fr}}
        .lw-overview-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:22px}
        .lw-overview-eyebrow{color:#536ff4;font-size:11px;font-weight:800;letter-spacing:.11em}
        .lw-overview-heading h2{margin:7px 0;color:#172f5b;font-size:clamp(24px,3vw,32px)}
        .lw-overview-heading p{margin:0;color:#64748b;line-height:1.55}
        .lw-overview-primary{display:inline-flex;align-items:center;gap:10px;padding:12px 17px;border-radius:12px;background:#2868e8;color:#fff;text-decoration:none;font-weight:800;white-space:nowrap}
        .lw-overview-primary:hover{background:#1d4ed8}
        .lw-overview-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:16px}
        .lw-overview-stat{display:flex;flex-direction:column;gap:9px;min-height:112px;padding:19px;border:1px solid #e1e7f2;border-radius:17px;background:#fff;color:#172f5b;text-decoration:none}
        .lw-overview-stat-label{color:#64748b;font-size:13px}
        .lw-overview-stat strong{font-size:30px;line-height:1.1;color:#122e58}
        .lw-overview-stat .lw-overview-stat-icon{font-size:25px}
        .lw-overview-stat-note{color:#64748b;font-size:12px;line-height:1.45}
        .lw-overview-stat-link:hover{border-color:#b7caff;background:#fbfcff}
        .lw-overview-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        .lw-overview-action{display:flex;align-items:flex-start;gap:12px;min-width:0;padding:19px;border:1px solid #e1e7f2;border-radius:17px;background:#fff;color:#172f5b;text-decoration:none}
        .lw-overview-action:hover{border-color:#b7caff;box-shadow:0 8px 20px rgba(37,99,235,.06)}
        .lw-overview-action-icon{display:flex;align-items:center;justify-content:center;flex:0 0 38px;width:38px;height:38px;border-radius:12px;background:#eef2ff;font-size:19px}
        .lw-overview-action strong{display:block;margin:2px 0 7px;font-size:15px}
        .lw-overview-action small{display:block;color:#64748b;font-size:12px;line-height:1.5}
        .lw-overview-arrow{margin-left:auto;color:#2563eb;font-size:18px}
        .lw-overview-info{cursor:default}
        @media(max-width:900px){.lw-overview-stats,.lw-overview-actions{grid-template-columns:1fr 1fr}.lw-overview-info{grid-column:1/-1}}
        @media(max-width:600px){.lw-overview{padding:18px}.lw-overview-heading{align-items:flex-start;flex-direction:column}.lw-overview-primary{width:100%;justify-content:center;box-sizing:border-box}.lw-overview-stats,.lw-overview-actions{grid-template-columns:1fr}.lw-overview-info{grid-column:auto}}

        /* Dashboard recommendation search */
        .lw-recommendation-panel{margin:0 0 38px;padding:28px;border:1px solid #dbeafe;border-radius:20px;background:linear-gradient(135deg,#fff 0%,#eff6ff 100%);box-shadow:0 10px 28px rgba(37,99,235,.07)}
        .lw-recommendation-eyebrow{display:inline-flex;gap:7px;margin-bottom:10px;color:#2563eb;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .lw-recommendation-panel h2{margin:0 0 8px;color:#0f172a;font-size:25px}
        .lw-recommendation-panel>p{margin:0 0 22px;color:#64748b;line-height:1.6}
        .lw-recommendation-form{display:grid;grid-template-columns:minmax(180px,1.2fr) repeat(3,minmax(130px,1fr)) auto;align-items:end;gap:13px}
        .lw-recommendation-field{display:flex;flex-direction:column;gap:7px;min-width:0}
        .lw-recommendation-field label{color:#334155;font-size:12px;font-weight:700}
        .lw-recommendation-field input,.lw-recommendation-field select{width:100%;min-height:46px;padding:11px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#0f172a;font:inherit}
        .lw-recommendation-field input:focus,.lw-recommendation-field select:focus{outline:3px solid rgba(37,99,235,.13);border-color:#2563eb}
        .lw-recommendation-submit{min-height:46px;padding:0 18px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:700;white-space:nowrap;cursor:pointer;transition:background .2s ease,transform .2s ease}
        .lw-recommendation-submit:hover{background:#1d4ed8;transform:translateY(-1px)}
        .lw-recommendation-note{margin-top:13px;color:#64748b;font-size:12px;line-height:1.5}
        @media(max-width:1000px){.lw-recommendation-form{grid-template-columns:repeat(2,minmax(0,1fr))}.lw-recommendation-submit{width:100%}}
        @media(max-width:560px){.lw-recommendation-panel{padding:20px}.lw-recommendation-panel h2{font-size:22px}.lw-recommendation-form{grid-template-columns:1fr}}
        /* Preferred time input for any minute of the hour */
        .lw-recommendation-panel{background:linear-gradient(135deg,#f4f0ff 0%,#eef6ff 100%);border-color:#d9d8fb}
        .lw-recommendation-panel h2{font-size:clamp(24px,3vw,34px);color:#153b70}
        .lw-booking-search-layout{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;align-items:start}
        .lw-booking-calendar,.lw-slot-picker{background:#fff;border:1px solid #e3eaf5;border-radius:18px;padding:20px;box-shadow:0 8px 22px rgba(29,78,216,.06)}
        .lw-booking-calendar label,.lw-slot-picker label{display:block;margin-bottom:10px;color:#173b6c;font-size:13px;font-weight:700}
        .lw-booking-calendar input,.lw-slot-picker select,.lw-slot-picker input[type="time"]{width:100%;min-height:48px;padding:12px;border:1px solid #cbdcf0;border-radius:12px;background:#f9fbff;color:#173b6c;font:inherit}
        .lw-slot-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px;margin-top:12px}
        .lw-slot-button{min-height:58px;border:1px solid #b8cce6;border-radius:13px;background:#f8fbff;color:#477db8;font-size:16px;font-weight:600;cursor:pointer;transition:.15s ease}
        .lw-slot-button:hover{border-color:#285a9c;background:#eef5ff}
        .lw-slot-button.is-selected{background:#173f78;color:#fff;border-color:#173f78;box-shadow:0 5px 12px rgba(23,63,120,.18)}
        .lw-slot-button:focus-visible{outline:3px solid #a5c8ff;outline-offset:2px}
        .lw-slot-button:disabled{opacity:.38;cursor:not-allowed;background:#eef1f6;border-color:#d8dee8;color:#8793a5;box-shadow:none}
        .lw-slot-button:disabled:hover{background:#eef1f6;border-color:#d8dee8}
        @media(max-width:1000px){.lw-booking-search-layout{grid-template-columns:repeat(2,minmax(0,1fr))}.lw-slot-picker{grid-column:1/-1}}
        .lw-slot-help{margin:12px 0 0;color:#64748b;font-size:12px;line-height:1.5}
        .lw-search-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
        .lw-search-clear{min-height:46px;padding:0 22px;border:0;border-radius:12px;background:#eef0f4;color:#263445;font-weight:700;cursor:pointer}
        .lw-recommendation-submit{min-height:46px;padding:0 24px;border-radius:12px;background:#2868e8}
        .lw-machine-search-button{display:none!important}
        .lw-machine-card-visual{display:flex;align-items:center;justify-content:center;min-height:145px;margin:0 0 14px;border-radius:14px;background:linear-gradient(145deg,#f1f5ff,#faf7ff);overflow:hidden}
        .lw-machine-card-visual svg{width:145px;height:135px;max-width:100%;filter:drop-shadow(0 8px 8px rgba(30,50,90,.14))}
        .lw-machine-status{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:800;text-transform:capitalize}
        .lw-machine-status.is-active{color:#087443;background:#d9fbe8}
        .lw-machine-status.is-inactive{color:#b42318;background:#fee4e2}
        .machine-card{overflow:hidden;border:1px solid #dfe6f1;border-radius:17px;background:#fff;padding:18px;box-shadow:0 7px 22px rgba(25,50,90,.06)}
        .machine-card h3{margin-top:0;color:#173b6c}
        .machine-card p{margin:8px 0;color:#34445a}
        .machine-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px}
        .machine-section{margin-top:30px}
        .lw-machine-inactive{opacity:.8}
        @media(max-width:560px){.lw-machine-card-visual{min-height:120px}.lw-machine-card-visual svg{height:115px}}
        @media(max-width:760px){.lw-booking-search-layout{grid-template-columns:1fr}.lw-slot-picker{grid-column:auto}.lw-slot-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.lw-search-actions{flex-direction:column}.lw-search-actions button{width:100%}}





        .lw-ai-button {



            position: fixed;



            right: 24px;

            bottom: 24px;



            width: 62px;

            height: 62px;



            border: 0;

            border-radius: 50%;



            background: #2563eb;



            color: white;



            font-size: 25px;



            cursor: pointer;



            box-shadow:

                0 8px 28px rgba(0, 0, 0, 0.22);



            z-index: 9999;



            transition: 0.2s ease;

        }





        .lw-ai-button:hover {



            transform: scale(1.06);



            box-shadow:

                0 10px 32px rgba(0, 0, 0, 0.26);

        }





        /* =========================================================

           CHAT WINDOW

           ========================================================= */



        .lw-ai-window {



            position: fixed;



            right: 24px;

            bottom: 98px;



            width: 390px;

            height: 610px;



            max-width: calc(100vw - 30px);

            max-height: calc(100vh - 120px);



            background: white;



            border-radius: 20px;



            border: 1px solid #e2e8f0;



            box-shadow:

                0 20px 60px rgba(15, 23, 42, 0.22);



            display: none;



            flex-direction: column;



            overflow: hidden;



            z-index: 9998;

        }





        .lw-ai-window.open {



            display: flex;



            animation:

                lwChatOpen 0.18s ease;

        }





        @keyframes lwChatOpen {



            from {



                opacity: 0;



                transform:

                    translateY(12px)

                    scale(0.98);

            }



            to {



                opacity: 1;



                transform:

                    translateY(0)

                    scale(1);

            }

        }





        /* =========================================================

           HEADER

           ========================================================= */



        .lw-ai-header {



            height: 68px;



            padding: 0 16px;



            background: #2563eb;



            color: white;



            display: flex;



            align-items: center;



            justify-content: space-between;

        }





        .lw-ai-header-left {



            display: flex;



            align-items: center;



            gap: 10px;

        }





        .lw-ai-avatar {



            width: 39px;

            height: 39px;



            border-radius: 50%;



            background:

                rgba(255, 255, 255, 0.18);



            display: flex;



            align-items: center;



            justify-content: center;



            font-size: 19px;

        }





        .lw-ai-name {



            font-size: 14px;



            font-weight: 600;

        }





        .lw-ai-status {



            margin-top: 2px;



            font-size: 10px;



            opacity: 0.82;

        }





        .lw-ai-header-actions {



            display: flex;



            gap: 4px;

        }





        .lw-ai-header-button {



            width: 34px;

            height: 34px;



            border: 0;



            border-radius: 8px;



            background:

                rgba(255, 255, 255, 0.12);



            color: white;



            cursor: pointer;



            font-size: 16px;

        }





        .lw-ai-header-button:hover {



            background:

                rgba(255, 255, 255, 0.22);

        }





        /* =========================================================

           CHAT BODY

           ========================================================= */



        .lw-ai-body {



            flex: 1;



            overflow-y: auto;



            padding: 18px;



            background: #f8fafc;

        }





        .lw-ai-welcome {



            margin-bottom: 20px;

        }





        .lw-ai-welcome h3 {



            margin: 0 0 6px;



            color: #0f172a;



            font-size: 19px;

        }





        .lw-ai-welcome p {



            margin: 0;



            color: #64748b;



            font-size: 13px;



            line-height: 1.55;

        }





        /* =========================================================

           TOPIC CARDS

           ========================================================= */



        .lw-ai-section-title {



            margin: 0 0 9px;



            color: #94a3b8;



            font-size: 10px;



            font-weight: 700;



            letter-spacing: 0.7px;



            text-transform: uppercase;

        }





        .lw-ai-topics {



            display: grid;



            grid-template-columns: 1fr 1fr;



            gap: 9px;



            margin-bottom: 20px;

        }





        .lw-ai-topic {



            padding: 13px;



            border: 1px solid #e2e8f0;



            border-radius: 13px;



            background: white;



            cursor: pointer;



            text-align: left;



            transition: 0.15s ease;

        }





        .lw-ai-topic:hover {



            border-color: #93c5fd;



            background: #eff6ff;



            transform: translateY(-1px);

        }





        .lw-ai-topic-icon {



            font-size: 19px;



            margin-bottom: 7px;

        }





        .lw-ai-topic-title {



            color: #1e293b;



            font-size: 12px;



            font-weight: 600;

        }





        .lw-ai-topic-text {



            margin-top: 3px;



            color: #94a3b8;



            font-size: 10px;



            line-height: 1.4;

        }





        /* =========================================================

           QUICK QUESTIONS

           ========================================================= */



        .lw-ai-quick {



            display: flex;



            flex-direction: column;



            gap: 7px;

        }





        .lw-ai-question {



            width: 100%;



            padding: 10px 12px;



            border: 1px solid #e2e8f0;



            border-radius: 10px;



            background: white;



            color: #334155;



            text-align: left;



            font-size: 12px;



            cursor: pointer;



            transition: 0.15s ease;

        }





        .lw-ai-question:hover {



            background: #eff6ff;



            border-color: #93c5fd;

        }





        .lw-ai-back {



            margin-bottom: 12px;



            border: 0;



            background: transparent;



            color: #2563eb;



            font-size: 12px;



            cursor: pointer;



            padding: 0;

        }





        /* =========================================================

           CHAT MESSAGES

           ========================================================= */



        .lw-ai-message {



            max-width: 88%;



            margin-bottom: 11px;



            padding: 10px 12px;



            border-radius: 13px;



            font-size: 13px;



            line-height: 1.5;



            white-space: pre-line;

        }





        .lw-ai-message.user {



            margin-left: auto;



            color: white;



            background: #2563eb;



            border-bottom-right-radius: 4px;

        }





        .lw-ai-message.bot {



            margin-right: auto;



            color: #334155;



            background: white;



            border: 1px solid #e2e8f0;



            border-bottom-left-radius: 4px;

        }





        .lw-ai-message.bot strong {



            color: #0f172a;

        }





        /* =========================================================

           TYPING

           ========================================================= */



        .lw-ai-typing {



            display: flex;



            gap: 4px;



            align-items: center;

        }





        .lw-ai-typing span {



            width: 5px;

            height: 5px;



            border-radius: 50%;



            background: #94a3b8;



            animation:

                lwTyping 1s infinite;

        }





        .lw-ai-typing span:nth-child(2) {



            animation-delay: 0.15s;

        }





        .lw-ai-typing span:nth-child(3) {



            animation-delay: 0.3s;

        }





        @keyframes lwTyping {



            0%, 60%, 100% {

                opacity: 0.3;

            }



            30% {

                opacity: 1;

            }

        }





        /* =========================================================

           INPUT

           ========================================================= */



        .lw-ai-input-area {



            padding: 12px;



            background: white;



            border-top: 1px solid #e2e8f0;

        }





        .lw-ai-form {



            display: flex;



            gap: 8px;

        }





        .lw-ai-input {



            flex: 1;



            min-width: 0;



            padding: 11px 12px;



            border: 1px solid #cbd5e1;



            border-radius: 11px;



            outline: none;



            font-size: 13px;

        }





        .lw-ai-input:focus {



            border-color: #2563eb;



            box-shadow:

                0 0 0 2px

                rgba(37, 99, 235, 0.08);

        }





        .lw-ai-send {



            width: 43px;



            border: 0;



            border-radius: 11px;



            background: #2563eb;



            color: white;



            cursor: pointer;



            font-size: 16px;

        }





        .lw-ai-send:hover {



            background: #1d4ed8;

        }





        /* =========================================================

           MOBILE

           ========================================================= */



        @media (max-width: 520px) {



            .lw-ai-window {



                right: 12px;

                bottom: 85px;



                width:

                    calc(100vw - 24px);



                height: 570px;

            }





            .lw-ai-button {



                right: 15px;

                bottom: 15px;

            }

        }



    

        /* Lavender prototype theme and compact pill navigation */
        html { min-height: 100%; background: #ded6ff; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: clamp(12px, 3vw, 34px);
            box-sizing: border-box;
            color: #202345;
            background:
                radial-gradient(ellipse at 8% 16%, rgba(255,255,255,.75) 0, rgba(255,255,255,0) 25%),
                radial-gradient(ellipse at 92% 22%, rgba(174,211,255,.7) 0, rgba(174,211,255,0) 24%),
                linear-gradient(135deg, #dcd3ff 0%, #e8eaff 48%, #f1e7ff 100%);
            background-attachment: fixed;
        }
        .container {
            width: min(1220px, 100%);
            margin: 0 auto;
            padding: clamp(14px, 2.5vw, 30px);
            box-sizing: border-box;
            border: 1px solid rgba(255,255,255,.9);
            border-radius: 30px;
            background: rgba(255,255,255,.32);
            box-shadow: 0 18px 55px rgba(87,74,158,.12);
            backdrop-filter: blur(10px);
        }
        .dashboard-nav.lw-prototype-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            width: min(100%, 1080px);
            margin: 0 auto 30px;
            padding: 10px 12px 10px 18px;
            box-sizing: border-box;
            border: 1px solid rgba(255,255,255,.88);
            border-radius: 999px;
            background: rgba(255,255,255,.88);
            box-shadow: 0 8px 26px rgba(73,70,135,.08);
        }
        .lw-brand { display:flex; align-items:center; gap:9px; color:#25234c; text-decoration:none; font-weight:800; white-space:nowrap; }
        .lw-brand-icon { display:grid; place-items:center; width:30px; height:30px; border-radius:9px; color:#fff; background:linear-gradient(135deg,#9a55ff,#6739e8); box-shadow:0 4px 10px rgba(116,69,226,.25); }
        .lw-brand-name { font-size:15px; letter-spacing:-.02em; }
        .lw-prototype-nav-links { display:flex; align-items:center; justify-content:flex-end; gap:clamp(8px,1.35vw,20px); flex-wrap:wrap; }
        .dashboard-nav.lw-prototype-nav .lw-prototype-nav-links a { color:#51418B; background:#F0EDFF; padding:10px 15px; border:1px solid transparent; border-radius:12px; text-decoration:none; font-size:12px; font-weight:700; transition:color .15s ease, background .15s ease; }
        .dashboard-nav.lw-prototype-nav .lw-prototype-nav-links a:hover, .dashboard-nav.lw-prototype-nav .lw-prototype-nav-links a:focus-visible { color:#35256F; background:#E2D9FF; border-color:#D1C4FF; }
        .dashboard-nav.lw-prototype-nav .lw-prototype-nav-links a.lw-nav-logout { padding:10px 17px; border-radius:999px; color:#fff; background:linear-gradient(135deg,#ad72ff,#7543ef); box-shadow:0 4px 10px rgba(117,67,239,.22); }
        .lw-ai-topics { grid-template-columns: 1fr; }
        .lw-ai-topic { width: 100%; }
        .dashboard-header { margin: 0 auto 28px; max-width:1080px; }
        .dashboard-header h1 { color:#202345; }
        .dashboard-header p { color:#626987; }
        .lw-recommendation-panel { box-shadow:0 15px 38px rgba(98,84,180,.09); }
        .machine-section > h2 { color:#25234c; }
        @media (max-width: 850px) {
            .dashboard-nav.lw-prototype-nav { border-radius:22px; align-items:flex-start; flex-direction:column; padding:14px 16px; }
            .lw-prototype-nav-links { justify-content:flex-start; gap:12px 16px; }
        }
        @media (max-width: 560px) {
            body { padding:8px; }
            .container { padding:12px; border-radius:22px; }
            .dashboard-nav.lw-prototype-nav .lw-prototype-nav-links a { font-size:11px; }
        }

    </style>



</head>





<body>





<div class="container">





    <!-- =========================================================

         NAVIGATION

         ========================================================= -->



    <nav class="dashboard-nav lw-prototype-nav" aria-label="Main navigation">
        <a class="lw-brand" href="dashboard.php" aria-label="LaundryWise home">
            <span class="lw-brand-icon" aria-hidden="true">▣</span>
            <span class="lw-brand-name">LaundryWise</span>
        </a>

        <div class="nav-links lw-prototype-nav-links">
            <a href="my_bookings.php">My Bookings</a>
            <a href="booking_help.php">Booking Help</a>
            <a href="policies.php">Policies &amp; Rules</a>
            <a href="settings.php">My Account</a>
            <a href="settings.php">Settings</a>
            <a class="lw-nav-logout" href="logout.php">Logout</a>
        </div>
    </nav>





    <!-- =========================================================

         WELCOME

         ========================================================= -->



    <div class="dashboard-header">



        <h1>



            Welcome,

            <?php

            echo htmlspecialchars(

                $_SESSION["name"]

            );

            ?>



        </h1>





        <p>



            Resident ID:



            <strong>



                <?php

                echo htmlspecialchars(

                    $_SESSION["resident_id"]

                );

                ?>



            </strong>



        </p>



    </div>






    <!-- Resident dashboard overview: no recommendations until a search is submitted -->
    <section class="lw-overview" aria-label="Resident dashboard overview">
        <div class="lw-overview-heading">
            <div>
                <span class="lw-overview-eyebrow">YOUR LAUNDRY AT A GLANCE</span>
                <h2>Resident Dashboard</h2>
                <p>Find a suitable laundry slot or manage your existing bookings.</p>
            </div>
            <a class="lw-overview-primary" href="#recommendation-search">Find a laundry slot <span aria-hidden="true">&#8594;</span></a>
        </div>

        <div class="lw-overview-stats lw-overview-stats-four">
            <article class="lw-overview-stat">
                <span class="lw-overview-stat-label">Active machines</span>
                <strong><?php echo $activeMachineCount; ?></strong>
                <span class="lw-overview-stat-note">Digital machine status</span>
            </article>
            <article class="lw-overview-stat">
                <span class="lw-overview-stat-label">Upcoming bookings</span>
                <strong><?php echo $upcomingBookingsCount; ?></strong>
                <span class="lw-overview-stat-note">Your confirmed future reservations</span>
            </article>
            <article class="lw-overview-stat">
                <span class="lw-overview-stat-label">Overall demand</span>
                <strong><span class="lw-overview-demand <?php echo htmlspecialchars($overallDemandClass); ?>"><?php echo htmlspecialchars($overallDemandLabel); ?></span></strong>
                <span class="lw-overview-stat-note">Next 7 days · based on confirmed bookings</span>
            </article>
            <article class="lw-overview-stat">
                <span class="lw-overview-stat-label">Booking window</span>
                <strong>7 days</strong>
                <span class="lw-overview-stat-note">Demand overview period</span>
            </article>
        </div>

        <div class="lw-overview-actions lw-overview-actions-three">
            <div class="lw-overview-action lw-overview-info">
                <span class="lw-overview-action-icon" aria-hidden="true">&#128705;</span>
                <span><strong>Find a laundry slot</strong><small>Choose a machine, date and one-hour time slot using the search below.</small></span>
            </div>
            <div class="lw-overview-action lw-overview-info">
                <span class="lw-overview-action-icon" aria-hidden="true">&#128203;</span>
                <span><strong>My bookings</strong><small>Use the My Bookings link in the header to view or manage reservations.</small></span>
            </div>
            <div class="lw-overview-action lw-overview-info">
                <span class="lw-overview-action-icon" aria-hidden="true">&#10024;</span>
                <span><strong>How recommendations work</strong><small>Alternatives appear after a search and are ranked by time proximity and predicted demand.</small></span>
            </div>
        </div>
    </section>

    <!-- Smart one-hour booking search -->
    <section class="lw-recommendation-panel" id="recommendation-search" aria-labelledby="lwRecommendationTitle">
        <div class="lw-recommendation-eyebrow">&#10024; Smart laundry suggestions</div>
        <h2 id="lwRecommendationTitle">Book a laundry slot</h2>
        <p>Choose a machine, date and one-hour slot. If your preferred slot is unavailable, LaundryWise will suggest alternatives ranked using predicted demand.</p>

        <form action="recommendations.php" method="get" id="lwRecommendationForm">
            <input type="hidden" name="return_to_dashboard" value="1">
            <input type="hidden" name="end_time" id="lwEndTime" required>

            <div class="lw-booking-search-layout">
                <div class="lw-slot-picker">
                    <label for="lwMachineId">1. Preferred machine</label>
                    <select id="lwMachineId" name="machine_id" required>
                        <option value="">Choose a washer or dryer</option>
                        <?php foreach (array_merge($washing_machines, $dryers) as $machine): ?>
                            <?php if (strtolower((string)$machine["status"]) !== "active") continue; ?>
                            <option value="<?php echo (int)$machine["id"]; ?>"><?php echo htmlspecialchars($machine["machine_name"] . " (" . $machine["machine_type"] . ")"); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="lw-slot-help">Choose an active washer or dryer.</p>
                </div>

                <div class="lw-booking-calendar">
                    <label for="lwPreferredDate">2. Choose a date</label>
                    <input id="lwPreferredDate" type="date" name="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                    <p class="lw-slot-help">Past dates cannot be selected.</p>
                </div>

                <div class="lw-slot-picker">
                    <label for="lwStartTime">3. Enter your preferred start time</label>
                    <input type="time" id="lwStartTime" name="start_time" min="08:00" max="21:00" step="60" required>
                    <p class="lw-slot-help" id="lwSelectedSlotText">Choose any valid time between 08:00 and 21:00. Each booking lasts one hour.</p>
                    <p class="lw-slot-help" id="lwPastSlotHelp" aria-live="polite">Past times today cannot be selected.</p>
                </div>
            </div>
            <div class="lw-search-actions">
                <button class="lw-search-clear" type="reset" id="lwClearSearch">Clear</button>
                <button class="lw-recommendation-submit" type="submit">Show results &#8594;</button>
            </div>
        </form>
        <div class="lw-recommendation-note">Search results show availability at the time checked. Recommendations do not reserve a machine or guarantee future availability.</div>
    </section>

    <script>
    (function () {
        const form = document.getElementById('lwRecommendationForm');
        if (!form) return;

        const startInput = document.getElementById('lwStartTime');
        const endInput = document.getElementById('lwEndTime');
        const dateInput = document.getElementById('lwPreferredDate');
        const machineSelect = document.getElementById('lwMachineId');
        const selectedText = document.getElementById('lwSelectedSlotText');
        const pastSlotHelp = document.getElementById('lwPastSlotHelp');

        function refreshTimeState() {
            const value = startInput.value;
            const now = new Date();
            const today = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            startInput.setCustomValidity('');
            endInput.value = '';
            if (!value) {
                selectedText.textContent = 'Choose any valid time between 08:00 and 21:00. Each booking lasts one hour.';
                return;
            }
            const parts = value.split(':').map(Number);
            const startMinutes = parts[0] * 60 + parts[1];
            const endMinutes = startMinutes + 60;
            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            if (startMinutes < 480 || endMinutes > 1320) {
                startInput.setCustomValidity('Choose a start time from 08:00 to 21:00 so the full one-hour booking ends by 22:00.');
                selectedText.textContent = 'The full one-hour booking must fit between 08:00 and 22:00.';
                return;
            }
            if (dateInput.value === today && startMinutes <= currentMinutes) {
                startInput.setCustomValidity('That time has already started or passed. Choose a future time.');
                selectedText.textContent = 'That time has already started or passed today.';
                return;
            }
            const endHour = Math.floor(endMinutes / 60);
            const endMinute = endMinutes % 60;
            endInput.value = String(endHour).padStart(2, '0') + ':' + String(endMinute).padStart(2, '0');
            selectedText.textContent = 'Selected slot: ' + value + '–' + endInput.value + ' (one hour).';
        }

        startInput.addEventListener('input', refreshTimeState);
        dateInput.addEventListener('change', refreshTimeState);
        form.addEventListener('submit', function (event) {
            refreshTimeState();
            if (!machineSelect.value || !dateInput.value || !startInput.value || !endInput.value || !startInput.checkValidity()) {
                event.preventDefault();
                if (!startInput.value) alert('Please choose your preferred start time.');
                else if (!startInput.checkValidity()) alert(startInput.validationMessage);
                else alert('Please choose an active machine, date and valid future start time.');
            }
        });
        form.addEventListener('reset', function () {
            window.setTimeout(function () {
                startInput.value = '';
                endInput.value = '';
                machineSelect.selectedIndex = 0;
                dateInput.value = '<?php echo date('Y-m-d'); ?>';
                refreshTimeState();
            }, 0);
        });
        refreshTimeState();
    })();
    </script>

    <?php if (is_array($dashboard_recommendation_search)): ?>
        <?php
        $search_date = $dashboard_recommendation_search["date"] ?? "";
        $search_start = $dashboard_recommendation_search["start_time"] ?? "";
        $search_end = $dashboard_recommendation_search["end_time"] ?? "";
        $search_machine_name = $dashboard_recommendation_search["machine_name"] ?? "your selected machine";
        $search_machine_type = $dashboard_recommendation_search["machine_type"] ?? "";
        $search_recommendations = $dashboard_recommendation_search["recommendations"] ?? [];
        $requested_slot_is_booked = !empty($dashboard_recommendation_search["requested_slot_is_booked"]);
        $original_booking_id = (int)($dashboard_recommendation_search["original_booking_id"] ?? 0);
        ?>
        <section class="lw-dashboard-results" id="recommendation-results" aria-labelledby="lwResultsTitle">
            <div class="lw-results-heading">
                <div>
                    <div class="lw-recommendation-eyebrow">&#10024; Your personalised suggestions</div>
                    <h2 id="lwResultsTitle">Recommended alternatives</h2>
                    <p class="lw-results-intro">For <?php echo htmlspecialchars($search_machine_name); ?> on <?php echo htmlspecialchars($search_date); ?>, <?php echo htmlspecialchars($search_start); ?>–<?php echo htmlspecialchars($search_end); ?>.</p>
                </div>
                <a class="lw-results-clear" href="dashboard.php?clear_recommendations=1#recommendation-search">New search</a>
            </div>

            <?php if ($requested_slot_is_booked): ?>
                <div class="lw-results-notice">Your preferred slot is already booked. These alternatives exclude slots that are currently booked.</div>
            <?php else: ?>
                <div class="lw-results-notice">Your preferred slot was not marked as booked when checked. These alternatives are ranked by time proximity and predicted demand. Recommendations do not reserve a machine.</div>
            <?php endif; ?>

            <?php if (!empty($search_recommendations)): ?>
                <div class="lw-results-grid">
                    <?php foreach ($search_recommendations as $index => $recommendation): ?>
                        <?php
                        $predicted_demand = (float)($recommendation["predicted_demand"] ?? 0);
                        if ($predicted_demand < 2) {
                            $demand_level = "Low expected demand";
                            $demand_class = "low";
                        } elseif ($predicted_demand < 4) {
                            $demand_level = "Moderate expected demand";
                            $demand_class = "moderate";
                        } else {
                            $demand_level = "High expected demand";
                            $demand_class = "high";
                        }
                        $booking_url = "book.php?" . http_build_query([
                            "machine_id" => $recommendation["machine_id"],
                            "date" => $recommendation["date"],
                            "start_time" => $recommendation["start_time"],
                            "end_time" => $recommendation["end_time"]
                        ]);
                        if ($original_booking_id) {
                            $booking_url .= "&replace_booking_id=" . urlencode((string)$original_booking_id);
                        }
                        ?>
                        <article class="lw-result-card">
                            <div class="lw-result-visual" role="img" aria-label="Washing machine illustration">
                                <svg viewBox="0 0 160 145" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <defs><linearGradient id="altWasherBody" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#cbd5e1"/></linearGradient><linearGradient id="altWasherGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#64748b"/><stop offset="1" stop-color="#172033"/></linearGradient></defs>
                                    <rect x="25" y="8" width="110" height="128" rx="15" fill="url(#altWasherBody)" stroke="#aeb9ca" stroke-width="2"/>
                                    <rect x="36" y="20" width="88" height="21" rx="5" fill="#eef2f7" stroke="#cbd5e1"/><circle cx="47" cy="30" r="4" fill="#8b5cf6"/><rect x="60" y="27" width="34" height="5" rx="2.5" fill="#cbd5e1"/><circle cx="113" cy="30" r="4" fill="#94a3b8"/>
                                    <circle cx="80" cy="84" r="39" fill="#e2e8f0" stroke="#9aa8bb" stroke-width="3"/><circle cx="80" cy="84" r="31" fill="url(#altWasherGlass)" stroke="#f8fafc" stroke-width="3"/><path d="M59 68 Q80 51 101 70 Q88 82 99 99 Q78 112 61 94 Q72 81 59 68Z" fill="#94a3b8" opacity=".55"/><circle cx="80" cy="84" r="7" fill="#cbd5e1" opacity=".8"/><path d="M59 60 Q75 51 91 58" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".65" fill="none"/>
                                </svg>
                            </div>
                            <div class="lw-result-card-top">
                                <span class="lw-result-rank">Option <?php echo (int)($index + 1); ?></span>
                                <span class="lw-demand-badge <?php echo htmlspecialchars($demand_class); ?>"><?php echo htmlspecialchars($demand_level); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($recommendation["machine_name"] ?? "Machine"); ?></h3>
                            <div class="lw-result-type"><?php echo htmlspecialchars($recommendation["machine_type"] ?? $search_machine_type); ?></div>
                            <div class="lw-result-details">
                                <div><span>Date</span><strong><?php echo htmlspecialchars($recommendation["date"] ?? $search_date); ?></strong></div>
                                <div><span>Time</span><strong><?php echo htmlspecialchars(($recommendation["start_time"] ?? "") . "–" . ($recommendation["end_time"] ?? "")); ?></strong></div>
                            </div>
                            <p class="lw-result-explanation">Ranked using time proximity and the existing Gradient Boosting demand prediction.</p>
                            <a class="lw-result-book" href="<?php echo htmlspecialchars($booking_url); ?>"><?php echo $original_booking_id ? "Switch to this option" : "Choose this option"; ?> <span aria-hidden="true">&#8594;</span></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="lw-results-empty"><strong>No alternatives found for this date.</strong><p>Try another date or choose a different machine of the same type.</p></div>
            <?php endif; ?>
            <p class="lw-recommendation-note">Recommendations are suggestions only. Choosing an option continues to the existing booking flow; availability must still be confirmed.</p>
        </section>
    <?php endif; ?>

    <!-- =========================================================

         WASHING MACHINES

         ========================================================= -->



    <section class="machine-section">



        <h2>

            Washing Machines

        </h2>





        <?php if (count($washing_machines) > 0): ?>



            <div class="machine-grid">



                <?php foreach ($washing_machines as $machine): ?>



                    <div class="machine-card <?php echo strtolower((string)$machine["status"]) === "active" ? "" : "lw-machine-inactive"; ?>">
                        <div class="lw-machine-card-visual" aria-label="Washing machine illustration" role="img">
                            <svg viewBox="0 0 160 145" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <defs><linearGradient id="washerBody" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#cbd5e1"/></linearGradient><linearGradient id="washerGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#64748b"/><stop offset="1" stop-color="#172033"/></linearGradient></defs>
                                <rect x="25" y="8" width="110" height="128" rx="15" fill="url(#washerBody)" stroke="#aeb9ca" stroke-width="2"/>
                                <rect x="36" y="20" width="88" height="21" rx="5" fill="#eef2f7" stroke="#cbd5e1"/>
                                <circle cx="47" cy="30" r="4" fill="#8b5cf6"/><rect x="60" y="27" width="34" height="5" rx="2.5" fill="#cbd5e1"/><circle cx="113" cy="30" r="4" fill="#94a3b8"/>
                                <circle cx="80" cy="84" r="39" fill="#e2e8f0" stroke="#9aa8bb" stroke-width="3"/><circle cx="80" cy="84" r="31" fill="url(#washerGlass)" stroke="#f8fafc" stroke-width="3"/>
                                <path d="M59 68 Q80 51 101 70 Q88 82 99 99 Q78 112 61 94 Q72 81 59 68Z" fill="#94a3b8" opacity=".55"/><circle cx="80" cy="84" r="7" fill="#cbd5e1" opacity=".8"/><path d="M59 60 Q75 51 91 58" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".65" fill="none"/>
                            </svg>
                        </div>

                        <h3>

                            <?php

                            echo htmlspecialchars(

                                $machine["machine_name"]

                            );

                            ?>

                        </h3>





                        <p>



                            <strong>

                                Type:

                            </strong>



                            <?php

                            echo htmlspecialchars(

                                $machine["machine_type"]

                            );

                            ?>



                        </p>





                        <p>



                            <strong>

                                Status:

                            </strong>



                            <span class="lw-machine-status <?php echo strtolower((string)$machine["status"]) === "active" ? "is-active" : "is-inactive"; ?>">
                                <?php echo htmlspecialchars(ucfirst((string)$machine["status"])); ?>
                            </span>



                        </p>
</div>



                <?php endforeach; ?>



            </div>



        <?php else: ?>



            <div class="empty-message">

                No washing machines have been registered.

            </div>



        <?php endif; ?>



    </section>





    <!-- =========================================================

         DRYERS

         ========================================================= -->



    <section class="machine-section">



        <h2>

            Dryers

        </h2>





        <?php if (count($dryers) > 0): ?>



            <div class="machine-grid">



                <?php foreach ($dryers as $machine): ?>



                    <div class="machine-card <?php echo strtolower((string)$machine["status"]) === "active" ? "" : "lw-machine-inactive"; ?>">
                        <div class="lw-machine-card-visual" aria-label="Dryer illustration" role="img">
                            <svg viewBox="0 0 160 145" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <defs><linearGradient id="dryerBody" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#cbd5e1"/></linearGradient><linearGradient id="dryerGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#64748b"/><stop offset="1" stop-color="#172033"/></linearGradient></defs>
                                <rect x="25" y="8" width="110" height="128" rx="15" fill="url(#dryerBody)" stroke="#aeb9ca" stroke-width="2"/>
                                <rect x="36" y="20" width="88" height="21" rx="5" fill="#eef2f7" stroke="#cbd5e1"/><circle cx="47" cy="30" r="4" fill="#8b5cf6"/><rect x="60" y="27" width="34" height="5" rx="2.5" fill="#cbd5e1"/><circle cx="113" cy="30" r="4" fill="#94a3b8"/>
                                <circle cx="80" cy="84" r="39" fill="#e2e8f0" stroke="#9aa8bb" stroke-width="3"/><circle cx="80" cy="84" r="31" fill="url(#dryerGlass)" stroke="#f8fafc" stroke-width="3"/>
                                <path d="M62 70 Q80 58 98 72 M61 83 Q80 72 99 85 M64 97 Q80 87 96 99" stroke="#cbd5e1" stroke-width="5" stroke-linecap="round" opacity=".55" fill="none"/><path d="M59 60 Q75 51 91 58" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".65" fill="none"/>
                            </svg>
                        </div>

                        <h3>

                            <?php

                            echo htmlspecialchars(

                                $machine["machine_name"]

                            );

                            ?>

                        </h3>





                        <p>



                            <strong>

                                Type:

                            </strong>



                            <?php

                            echo htmlspecialchars(

                                $machine["machine_type"]

                            );

                            ?>



                        </p>





                        <p>



                            <strong>

                                Status:

                            </strong>



                            <span class="lw-machine-status <?php echo strtolower((string)$machine["status"]) === "active" ? "is-active" : "is-inactive"; ?>">
                                <?php echo htmlspecialchars(ucfirst((string)$machine["status"])); ?>
                            </span>



                        </p>
</div>



                <?php endforeach; ?>



            </div>



        <?php else: ?>



            <div class="empty-message">

                No dryers have been registered.

            </div>



        <?php endif; ?>



    </section>



</div>





<!-- =============================================================

     LAUNDRYWISE ASSISTANT

     ============================================================= -->





<div

    id="lwAiWindow"

    class="lw-ai-window"

>





    <!-- HEADER -->



    <div class="lw-ai-header">



        <div class="lw-ai-header-left">



            <div class="lw-ai-avatar">

                ✨

            </div>



            <div>



                <div class="lw-ai-name">

                    LaundryWise Assistant

                </div>



                <div class="lw-ai-status">

                    Here to help with your laundry

                </div>



            </div>



        </div>





        <div class="lw-ai-header-actions">



            <button

                type="button"

                class="lw-ai-header-button"

                onclick="showHome()"

                title="Home"

            >

                ⌂

            </button>





            <button

                type="button"

                class="lw-ai-header-button"

                onclick="closeAssistant()"

                title="Close"

            >

                ×

            </button>



        </div>



    </div>





    <!-- CHAT -->



    <div

        id="lwAiBody"

        class="lw-ai-body"

    >



        <!-- HOME -->



        <div id="lwAiHome">





            <div class="lw-ai-welcome">



                <h3>

                    Hi there! 👋

                </h3>



                <p>

                    What can I help you with?

                    You can ask me about bookings,

                    available machines, quieter times,

                    or LaundryWise policies.

                </p>



            </div>





            <div class="lw-ai-section-title">

                What would you like help with?

            </div>





            <div class="lw-ai-topics">





                <button

                    type="button"

                    class="lw-ai-topic"

                    onclick="showTopic('smart')"

                >



                    <div class="lw-ai-topic-icon">

                        ✨

                    </div>



                    <div class="lw-ai-topic-title">

                        Smart Suggestions

                    </div>



                    <div class="lw-ai-topic-text">

                        Find quieter times and machines

                    </div>



                </button>























            </div>





            <div class="lw-ai-section-title">

                Popular

            </div>





            <div class="lw-ai-quick">



                <button

                    type="button"

                    class="lw-ai-question"

                    onclick="askQuestion('When should I do laundry today?')"

                >

                    🧺 When should I do laundry today?

                </button>





                <button

                    type="button"

                    class="lw-ai-question"

                    onclick="askQuestion('Is it busy tonight?')"

                >

                    🌙 Is it busy tonight?

                </button>





                <button

                    type="button"

                    class="lw-ai-question"

                    onclick="askQuestion('What happens if I cancel my booking?')"

                >

                    ❌ What happens if I cancel my booking?

                </button>



            </div>



        </div>





        <!-- TOPIC QUESTIONS -->



        <div

            id="lwAiTopic"

            style="display:none;"

        >



            <button

                type="button"

                class="lw-ai-back"

                onclick="showHome()"

            >

                ← Back to help

            </button>





            <div

                id="lwAiTopicTitle"

                class="lw-ai-section-title"

            ></div>





            <div

                id="lwAiQuestions"

                class="lw-ai-quick"

            ></div>



        </div>





        <!-- CHAT MESSAGES -->



        <div

            id="lwAiMessages"

            style="display:none;"

        ></div>



    </div>





    <!-- INPUT -->



    <div class="lw-ai-input-area">



        <form

            id="lwAiForm"

            class="lw-ai-form"

        >



            <input

                id="lwAiInput"

                class="lw-ai-input"

                type="text"

                placeholder="Ask me anything about laundry..."

                autocomplete="off"

            >





            <button

                type="submit"

                class="lw-ai-send"

                aria-label="Send"

            >

                ➤

            </button>



        </form>



    </div>



</div>





<!-- FLOATING BUTTON -->



<button

    id="lwAiButton"

    class="lw-ai-button"

    type="button"

    onclick="toggleAssistant()"

    aria-label="Open LaundryWise Assistant"

>

    ✨

</button>

<script>



function openAssistantTopic(topic) {
    const windowElement = document.getElementById("lwAiWindow");
    if (windowElement && !windowElement.classList.contains("open")) {
        windowElement.classList.add("open");
    }
    if (typeof showTopic === "function") {
        showTopic(topic);
    } else {
        window.setTimeout(function () {
            if (typeof showTopic === "function") showTopic(topic);
        }, 80);
    }
}

const aiWindow =

    document.getElementById(

        "lwAiWindow"

    );



const aiBody =

    document.getElementById(

        "lwAiBody"

    );



const aiHome =

    document.getElementById(

        "lwAiHome"

    );



const aiTopic =

    document.getElementById(

        "lwAiTopic"

    );



const aiTopicTitle =

    document.getElementById(

        "lwAiTopicTitle"

    );



const aiQuestions =

    document.getElementById(

        "lwAiQuestions"

    );



const aiMessages =

    document.getElementById(

        "lwAiMessages"

    );



const aiInput =

    document.getElementById(

        "lwAiInput"

    );



const aiForm =

    document.getElementById(

        "lwAiForm"

    );





/*

|--------------------------------------------------------------------------

| Topic questions

|--------------------------------------------------------------------------

*/



const topicData = {



    smart: {



        title: "✨ Smart Suggestions",



        questions: [



            "When should I do laundry today?",



            "When is it quiet today?",



            "When is the busiest time today?",



            "Is it busy tonight?",



            "What about tomorrow?",



            "Which machine should I use?",



            "Why did you recommend that time?"

        ]

    },





    booking: {



        title: "🧺 Booking Help",



        questions: [



            "How do I make a booking?",



            "What happens if I cancel my booking?",



            "What happens if I don't show up?",



            "Can I book an occupied time?",



            "Why can't I book this time?",



            "Can I change my booking?",



            "Where can I see my bookings?"

        ]

    },





    policy: {



        title: "📋 Policies & Rules",



        questions: [



            "What happens if I cancel my booking?",



            "What happens if I don't show up?",



            "Can I book an occupied time?",



            "Does LaundryWise guarantee machine availability?",



            "Does the recommendation automatically book for me?",



            "Can LaundryWise control the washing machine?",



            "How are recommendations made?"

        ]

    },





    account: {



        title: "👤 My Account",



        questions: [



            "Where can I see my bookings?",



            "How do I cancel a booking?",



            "Can I change my booking?",



            "How do I log out?",



            "How is my account protected?"

        ]

    }



};





/*

|--------------------------------------------------------------------------

| Open / close assistant

|--------------------------------------------------------------------------

*/



function toggleAssistant() {



    aiWindow.classList.toggle(

        "open"

    );





    if (

        aiWindow.classList.contains(

            "open"

        )

    ) {



        setTimeout(

            function () {



                aiInput.focus();



            },

            100

        );

    }

}





function closeAssistant() {



    aiWindow.classList.remove(

        "open"

    );

}





/*

|--------------------------------------------------------------------------

| Show home

|--------------------------------------------------------------------------

*/



function showHome() {



    aiHome.style.display =

        "block";



    aiTopic.style.display =

        "none";



    aiMessages.style.display =

        "none";



    aiBody.scrollTop = 0;

}





/*

|--------------------------------------------------------------------------

| Show topic

|--------------------------------------------------------------------------

*/



function showTopic(

    topic

) {



    const data =

        topicData[topic];





    if (!data) {

        return;

    }





    aiHome.style.display =

        "none";



    aiTopic.style.display =

        "block";



    aiMessages.style.display =

        "none";





    aiTopicTitle.textContent =

        data.title;





    aiQuestions.innerHTML =

        "";





    data.questions.forEach(

        function (question) {



            const button =

                document.createElement(

                    "button"

                );





            button.type =

                "button";





            button.className =

                "lw-ai-question";





            button.textContent =

                question;





            button.onclick =

                function () {



                    askQuestion(

                        question

                    );

                };





            aiQuestions.appendChild(

                button

            );

        }

    );





    aiBody.scrollTop = 0;

}





/*

|--------------------------------------------------------------------------

| Start chat

|--------------------------------------------------------------------------

*/



function startChat() {



    aiHome.style.display =

        "none";



    aiTopic.style.display =

        "none";



    aiMessages.style.display =

        "block";

}





/*

|--------------------------------------------------------------------------

| Add message

|--------------------------------------------------------------------------

*/



function addMessage(

    text,

    type

) {



    startChat();





    const message =

        document.createElement(

            "div"

        );





    message.className =

        "lw-ai-message " +

        type;





    message.textContent =

        text;





    aiMessages.appendChild(

        message

    );





    aiBody.scrollTop =

        aiBody.scrollHeight;

}





/*

|--------------------------------------------------------------------------

| Typing indicator

|--------------------------------------------------------------------------

*/



function showTyping() {



    startChat();





    const typing =

        document.createElement(

            "div"

        );





    typing.id =

        "lwAiTyping";





    typing.className =

        "lw-ai-message bot";





    typing.innerHTML = `

        <div class="lw-ai-typing">

            <span></span>

            <span></span>

            <span></span>

        </div>

    `;





    aiMessages.appendChild(

        typing

    );





    aiBody.scrollTop =

        aiBody.scrollHeight;

}





function removeTyping() {



    const typing =

        document.getElementById(

            "lwAiTyping"

        );





    if (typing) {



        typing.remove();



    }

}





/*

|--------------------------------------------------------------------------

| Ask suggested question

|--------------------------------------------------------------------------

*/



function askQuestion(

    question

) {



    /*

     * Clear the input before sending.

     */



    aiInput.value = "";





    sendQuestion(

        question

    );

}





/*

|--------------------------------------------------------------------------

| Send question

|--------------------------------------------------------------------------

*/



async function sendQuestion(

    question

) {



    question =

        question.trim();





    if (!question) {



        return;



    }





    /*

     * IMPORTANT:

     * Clear the input immediately after

     * reading the question.

     */



    aiInput.value = "";





    /*

     * Add user's message to chat.

     */



    addMessage(

        question,

        "user"

    );





    /*

     * Show typing indicator.

     */



    showTyping();





    try {



        const response =

            await fetch(

                "assistant.php",

                {

                    method:

                        "POST",



                    headers: {



                        "Content-Type":

                            "application/x-www-form-urlencoded"

                    },



                    body:

                        "question=" +

                        encodeURIComponent(

                            question

                        )

                }

            );





        const data =

            await response.json();





        removeTyping();





        addMessage(

            data.answer ||

            "Sorry, I couldn't find an answer right now.",

            "bot"

        );





    } catch (error) {



        removeTyping();





        addMessage(

            "Sorry, I couldn't connect right now. Please try again.",

            "bot"

        );

    }

}





/*

|--------------------------------------------------------------------------

| Form submission

|--------------------------------------------------------------------------

*/



aiForm.addEventListener(

    "submit",

    function (event) {



        event.preventDefault();





        /*

         * Get the current text first.

         */



        const question =

            aiInput.value.trim();





        /*

         * Do nothing if empty.

         */



        if (!question) {



            return;



        }





        /*

         * Clear the box BEFORE sending.

         */



        aiInput.value = "";





        /*

         * Send the question.

         */



        sendQuestion(

            question

        );



    }

);



</script>



</body>



</html>