<?php

/* LaundryWise file overview: Utility for generating or inspecting a password hash for secure password storage. Avoid exposing generated credentials publicly. */


/* Explanation: Creates a secure password hash for storage instead of saving the original password. */
echo password_hash("test123", PASSWORD_DEFAULT);

?>