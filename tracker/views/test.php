<?php
/**
 * Environment Connectivity Test
 * * This script serves as a baseline check to verify that the PHP interpreter
 * is correctly configured on the server and that error reporting is active.
 * This is a standard first step in the "Data Tier" setup to ensure 
 * connectivity issues are caught early.
 */

// 1. ERROR REPORTING INITIALIZATION
// These settings ensure that any configuration or syntax errors are 
// immediately visible in the browser, assisting with rapid debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. OUTPUT VERIFICATION
// A simple string output to confirm the Presentation Tier is reachable.
echo "PHP is working! Interpreter and server connectivity are successful.";
?>