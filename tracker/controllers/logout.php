<?php
/**
 * Logout Controller
 * * Safely terminates the user's session and clears all permission data
 * to ensure Role-Based Access Control (RBAC) is maintained.
 */

// Initialize the session to access it
session_start();

/** * SECURITY BEST PRACTICE:
 * 1. session_unset() removes all global session variables.
 * 2. session_destroy() completely destroys the session data on the server.
 */
session_unset();
session_destroy();

/**
 * Redirect the user back to the landing page/login screen.
 * Path is adjusted to point to index.php in the root directory.
 */
header("Location: ../index.php");
exit;