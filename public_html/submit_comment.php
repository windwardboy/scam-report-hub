<?php
session_start(); // Start session for CSRF, feedback messages

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// Define default redirect (requires slug later)
$redirect_url = '/browse.php'; // Fallback redirect

// 1. Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url); 
    exit();
}

// 2. CSRF Token Verification
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token validation failed for comment submission. IP: " . $_SERVER['REMOTE_ADDR']);
    $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Security token validation failed. Please try submitting again.'];
    header('Location: /index.php'); 
    exit();
}

// --- Get Basic Inputs ---
$scam_id = filter_input(INPUT_POST, 'scam_id', FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');
$comment = trim($_POST['comment'] ?? ''); // Raw comment text

// --- Fetch Slug for Redirects ---
$slug = null;
if ($scam_id && $scam_id > 0) {
    try {
        $stmt_slug = $pdo->prepare("SELECT slug FROM scams WHERE id = ?");
        $stmt_slug->execute([$scam_id]);
        $slug = $stmt_slug->fetchColumn();
        if ($slug) {
            $redirect_url = "/scam/" . urlencode($slug) . "#comments-section"; 
        } else {
             error_log("Comment submission attempt for non-existent scam_id: " . $scam_id);
             $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Invalid report ID specified for comment.'];
             header('Location: /browse.php'); 
             exit();
        }
    } catch (PDOException $e) {
         error_log("DB error fetching slug for comment redirect (scam_id: $scam_id): " . $e->getMessage());
         $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Database error processing comment. Please try again.'];
         header('Location: /index.php'); 
         exit();
    }
} else {
     $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Invalid report ID specified for comment.'];
     header('Location: /browse.php'); 
     exit();
}

// --- reCAPTCHA Verification ---
$recaptcha_secret = RECAPTCHA_SECRET_KEY; 
if (!isset($_POST['g-recaptcha-response'])) {
    $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'reCAPTCHA verification is missing.'];
    $_SESSION['comment_form_data'] = $_POST; 
    header('Location: ' . $redirect_url); 
    exit();
}
$recaptcha_response = $_POST['g-recaptcha-response'];
// (cURL verification logic remains same...)
if (function_exists('curl_init')) { $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify"); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $recaptcha_secret,'response' => $recaptcha_response,'remoteip' => $_SERVER['REMOTE_ADDR']])); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); $response = curl_exec($ch); $curl_error = curl_error($ch); curl_close($ch); if ($response === false) { error_log("Comment reCAPTCHA cURL error: ".$curl_error); $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'reCAPTCHA server error.']; $_SESSION['comment_form_data'] = $_POST; header('Location: '.$redirect_url); exit(); } $response_keys = json_decode($response, true); } else { error_log("Comment reCAPTCHA FATAL: cURL not available."); $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Server configuration error (cURL).']; $_SESSION['comment_form_data'] = $_POST; header('Location: '.$redirect_url); exit(); }
if (!$response_keys || !$response_keys['success']) { $error_codes = isset($response_keys['error-codes']) ? implode(', ',$response_keys['error-codes']) : 'N/A'; error_log("Comment reCAPTCHA verification failed. Codes: ".$error_codes); $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'reCAPTCHA verification failed. Please try again.']; $_SESSION['comment_form_data'] = $_POST; unset($_SESSION['comment_form_data']['g-recaptcha-response']); header('Location: ' . $redirect_url); exit(); }

// --- Validate Other Inputs ---
$errors = [];
if (!empty($name) && mb_strlen($name) > 100) { $errors[] = "Name cannot exceed 100 characters."; }
if (empty($comment)) { $errors[] = "Comment field cannot be empty."; } 
elseif (mb_strlen($comment) > 5000) { $errors[] = "Comment is too long (maximum 5000 characters)."; }

if (!empty($errors)) {
    $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
    $_SESSION['comment_form_data'] = $_POST; 
    header('Location: ' . $redirect_url); 
    exit();
}

// --- Final Check: Ensure Scam report is still Approved ---
try {
    $stmt_status = $pdo->prepare("SELECT status FROM scams WHERE id = ?");
    $stmt_status->execute([$scam_id]); $current_status = $stmt_status->fetchColumn();
    if ($current_status !== 'approved') { error_log("Comment attempt on non-approved scam_id: ".$scam_id); $_SESSION['comment_feedback'] = ['type' => 'warning', 'message' => 'Comments can only be added to approved reports.']; header('Location: '.$redirect_url); exit(); }
} catch (PDOException $e) { error_log("DB error checking status pre-comment insert: ".$e->getMessage()); $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Database error verifying report status.']; header('Location: '.$redirect_url); exit(); }

// --- Prepare Name and IP for DB ---
$name_safe = !empty($name) ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : null; // Still sanitize name for display safety
$ip_address = $_SERVER['REMOTE_ADDR']; 

// --- Insert into Database ---
try {
    $sql_insert = "INSERT INTO comments (scam_id, name, comment, status, ip_address, created_at) 
                   VALUES (?, ?, ?, 'pending', ?, NOW())";
    $stmt_insert = $pdo->prepare($sql_insert);
    
    // **** MODIFIED: Use the raw $comment variable ****
    if ($stmt_insert->execute([$scam_id, $name_safe, $comment, $ip_address])) { 
    // **********************************************
        $_SESSION['comment_feedback'] = ['type' => 'success', 'message' => 'Your comment has been submitted successfully and is awaiting moderation.'];
        header('Location: ' . $redirect_url);
        exit();
    } else {
        error_log("Comment insert failed for scam_id: " . $scam_id);
        $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'Failed to save comment due to a database error.'];
        $_SESSION['comment_form_data'] = $_POST; 
        header('Location: ' . $redirect_url);
        exit();
    }
} catch (PDOException $e) {
    error_log("Database exception during comment insertion for scam_id {$scam_id}: " . $e->getMessage());
    $_SESSION['comment_feedback'] = ['type' => 'danger', 'message' => 'A critical database error occurred while saving your comment.'];
    $_SESSION['comment_form_data'] = $_POST; 
    header('Location: ' . $redirect_url); 
    exit();
}
?>