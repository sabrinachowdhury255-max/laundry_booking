<?php

/* LaundryWise file overview: Resident-facing page describing the booking rules and system policies presented by LaundryWise. */

/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Policies &amp; Rules | LaundryWise</title>
<link rel="stylesheet" href="css/style.css">
<style>
body{background:#f4f6ff;color:#182849;font-family:Arial,sans-serif}
.info-page{max-width:1000px;margin:28px auto;padding:0 18px}
.info-nav,.info-hero,.info-card{background:#fff;border:1px solid #e1e7f5;border-radius:20px;box-shadow:0 8px 24px rgba(30,55,100,.06)}
.info-nav{padding:16px 20px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
.info-nav a{color:#3f4f79;text-decoration:none;margin-right:12px;font-weight:700}
.info-hero{padding:30px;margin-bottom:18px;background:linear-gradient(125deg,#f0eaff,#eaf2ff)}
.info-hero h1{margin:0 0 8px;color:#193664}.info-hero p,.info-card p,.info-card li{line-height:1.65;color:#52617b}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
.info-card{padding:22px}.info-card h2{font-size:19px;margin-top:0;color:#193664}
</style>
<link rel="stylesheet" href="css/laundrywise-nav.css">
</head>
<body><main class="info-page">
<nav class="dashboard-nav lw-prototype-nav" aria-label="Main navigation">
<a class="lw-brand" href="dashboard.php" aria-label="LaundryWise home"><span class="lw-brand-icon" aria-hidden="true">▣</span><span class="lw-brand-name">LaundryWise</span></a>
<div class="lw-prototype-nav-links">
<a href="dashboard.php">Dashboard</a><a href="my_bookings.php">My Bookings</a><a href="booking_help.php">Booking Help</a><a href="policies.php">Policies &amp; Rules</a><a href="settings.php">My Account</a><a class="lw-nav-logout" href="logout.php">Logout</a>
</div></nav>
<section class="info-hero"><h1>Policies &amp; Rules</h1><p>Important limits and expectations when using LaundryWise.</p></section>
<div class="info-grid">
<section class="info-card"><h2>Booking duration</h2><p>Each reservation is one hour. Start times may be selected in 15-minute increments between 08:00 and 21:00, subject to the system's availability checks.</p></section>
<section class="info-card"><h2>Availability and conflicts</h2><p>A confirmed booking cannot overlap another confirmed booking for the same machine. Availability is based on digital booking records and machine status, not physical machine sensors.</p></section>
<section class="info-card"><h2>Recommendations</h2><p>LaundryWise filters out booked candidate times and uses a Gradient Boosting model trained on historical or simulated booking counts to estimate relative availability. The indicator is not a live queue count and does not guarantee physical availability.</p></section>
<section class="info-card"><h2>Resident responsibility</h2><p>Residents must check the booking details before confirming. Recommendations are suggestions only; the resident chooses whether to book an alternative.</p></section>
<section class="info-card"><h2>Cancellation</h2><p>Future confirmed bookings can be cancelled through My Bookings. After cancellation, the reservation status changes and the slot may become available again.</p></section>
<section class="info-card"><h2>Notifications and privacy</h2><p>Email notifications are controlled in My Account settings. Account and booking information is used to operate the prototype; model estimates should be treated as decision support, not certainty.</p></section>
<section class="info-card"><h2>System limitations</h2><p>LaundryWise does not control washing machines, monitor their physical operating state, process payments or detect queues. Recommendations depend on the quality and limitations of the available booking data.</p></section>
</div></main></body></html>