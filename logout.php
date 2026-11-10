<?php

/* LaundryWise file overview: Ends the current user session and redirects the user away from protected pages. */


/* Explanation: Starts or resumes the session so the page can identify the signed-in resident and enforce access control. */
session_start();

session_unset();

session_destroy();

header("Location: login.php");

exit();

?>