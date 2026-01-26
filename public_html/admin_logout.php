<?php
session_start(); // Start the session to access it

// === Clear specific session variables ===
// Unset all admin-specific session variables if you have more later
unset($_SESSION['is_admin']); 
// You could add other admin-related session variables here if needed
// unset($_SESSION['admin_username']); 

// === Destroy the session ===
// This removes all session data associated with the user's session ID
session_destroy();

// === Optional: Clear session cookie ===
// Helps ensure the old session ID is removed from the browser immediately,
// though session_destroy() usually handles invalidating it on the server.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// === Redirect to login page ===
header("Location: admin_login.php?logged_out=1"); // Add a flag for potential feedback
exit();
?>