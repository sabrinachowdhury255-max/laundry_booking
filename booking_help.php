<?php

/* LaundryWise file overview: Resident help page explaining how to use the booking workflow and related system features. */

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
<title>Booking Help | LaundryWise</title>
<link rel="stylesheet" href="css/style.css">
<style>
body{background:#f4f6ff;color:#182849;font-family:Arial,sans-serif}
.info-page{max-width:1000px;margin:28px auto;padding:0 18px}
.info-nav,.info-hero,.info-card{background:#fff;border:1px solid #e1e7f5;border-radius:20px;box-shadow:0 8px 24px rgba(30,55,100,.06)}
.info-nav{padding:16px 20px;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
.info-nav a{color:#3f4f79;text-decoration:none;margin-right:12px;font-weight:700}
.info-hero{padding:30px;margin-bottom:18px;background:linear-gradient(125deg,#f0eaff,#eaf2ff)}
.info-hero h1{margin:0 0 8px;color:#193664}.info-hero p,.info-card p,.info-card li{line-height:1.65;color:#52617b}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
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
<section class="info-hero"><h1>Booking Help</h1><p>Guidance for searching, booking, changing and cancelling laundry time slots.</p></section>
<div class="info-grid">
<section class="info-card"><h2>Find a time</h2><p>On the dashboard, select an active machine, choose a date and enter a preferred start time between 08:00 and 21:00. Start times can be selected in 15-minute increments, such as 08:15 or 09:30. Each booking lasts one hour.</p></section>
<section class="info-card"><h2>Choose an alternative</h2><p>If the requested time overlaps a confirmed booking, LaundryWise checks other active machines of the same type and available one-hour periods. Suggestions are ranked by closeness to your requested time and an estimated availability indicator.</p></section>
<section class="info-card"><h2>Confirm a booking</h2><p>Recommendations are not bookings. Select the booking action for the alternative you want; the system checks availability again before confirming it.</p></section>
<section class="info-card"><h2>View or cancel</h2><p>Open My Bookings to review your reservations. You can cancel a future confirmed booking from that page. A cancelled booking is no longer treated as a confirmed reservation.</p></section>
<section class="info-card"><h2>Understand availability</h2><p>Availability refers to digital booking records and historical booking patterns. LaundryWise does not use physical sensors and cannot verify whether a machine is physically occupied or whether people are waiting in a queue.</p></section>
<section class="info-card"><h2>Need more help?</h2><p>Use the floating LaundryWise assistant for quick answers about booking steps, account features and system policies.</p></section>
</div></main></body></html>