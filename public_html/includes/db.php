<?php

// Require the configuration file located outside the web root
// IMPORTANT: Make sure this absolute path is correct for your server!
// It should point to the 'config.php' file in the directory ABOVE public_html.
require_once '/home/sites/32b/b/b19bae1aa8/config.php';

// Set PDO options for better security and consistency
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error (you already had this)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulated prepared statements for security
];

// Construct the Data Source Name (DSN) using constants from config.php
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    // Create the PDO instance using constants from config.php and the options array
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // --- Production-Safe Error Handling ---

    // 1. Log the detailed, specific error for debugging purposes (check your PHP error logs)
    error_log("Database Connection Error: " . $e->getMessage());

    // 2. Display a generic, user-friendly error message.
    //    Avoid showing detailed errors (like $e->getMessage()) on a live site.
    //    This 'die()' is simple; you could later implement a redirect to a custom error page.
    http_response_code(503); // Service Unavailable
    die("Database connection unavailable. Please try again later.");

    // Original code was: die("Database connection failed: " . $e->getMessage());
    // This revealed potentially sensitive information in the error message.
}

// Note: The $pdo variable is now available for use in any script that includes 'includes/db.php'.

?>