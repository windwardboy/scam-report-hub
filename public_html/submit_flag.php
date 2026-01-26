<?php
session_start(); // Start session for CSRF and feedback messages

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// --- Initial Setup & Defaults ---
$browse_page = 'browse.php'; // Fallback redirect
$redirect_url = $browse_page; // Default redirect if things go wrong early
$scam_id = null; // Initialize scam_id

// 1. Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $browse_page); // Redirect non-POST requests away
    exit();
}

// --- CSRF Token Verification ---
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token validation failed for flag submission. IP: " . $_SERVER['REMOTE_ADDR']);
    $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'Security token mismatch. Flag not submitted.'];
    // Try to redirect back to original page if possible, otherwise browse
    $scam_id_temp = filter_input(INPUT_POST, 'scam_id', FILTER_VALIDATE_INT);
    if ($scam_id_temp) {
         try { /* Try to get slug for redirect */ $stmt_s = $pdo->prepare("SELECT slug FROM scams WHERE id = ?"); $stmt_s->execute([$scam_id_temp]); $s = $stmt_s->fetchColumn(); if ($s) $redirect_url="/scam/".urlencode($s); } catch(Exception $e){}
    } else { $redirect_url = $browse_page; }
    header('Location: ' . $redirect_url);
    exit();
}
// Optional: unset($_SESSION['csrf_token']); // If using single-use tokens

// --- Get Inputs ---
$scam_id = filter_input(INPUT_POST, 'scam_id', FILTER_VALIDATE_INT);
$reason = trim($_POST['reason'] ?? '');
$details = trim($_POST['details'] ?? '');

// --- Determine Redirect URL (based on scam_id) ---
if ($scam_id && $scam_id > 0) {
    try {
        $stmt_slug = $pdo->prepare("SELECT slug FROM scams WHERE id = ?");
        $stmt_slug->execute([$scam_id]);
        $slug = $stmt_slug->fetchColumn();
        if ($slug) {
            $redirect_url = "/scam/" . urlencode($slug); // Set specific redirect URL (no anchor needed for general feedback)
        } else {
             $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'Invalid Report ID specified for flag.'];
             header('Location: ' . $browse_page); 
             exit();
        }
    } catch (PDOException $e) {
         error_log("DB error fetching slug for flag redirect (scam_id: $scam_id): " . $e->getMessage());
         $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'Database error processing flag. Please try again.'];
         header('Location: ' . $browse_page); 
         exit();
    }
} else {
     $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'Invalid Report ID specified for flag.'];
     header('Location: ' . $browse_page); 
     exit();
}


// --- reCAPTCHA Verification ---
$recaptcha_secret = RECAPTCHA_SECRET_KEY; 
if (!isset($_POST['g-recaptcha-response'])) {
    $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'reCAPTCHA verification is missing.'];
    header('Location: ' . $redirect_url); 
    exit();
}
$recaptcha_response = $_POST['g-recaptcha-response'];

// (Use cURL to verify - assuming it's available as checked before)
$ch = curl_init(); curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify"); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['secret' => $recaptcha_secret,'response' => $recaptcha_response,'remoteip' => $_SERVER['REMOTE_ADDR']])); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); $response = curl_exec($ch); $curl_error = curl_error($ch); curl_close($ch);
if ($response === false) { error_log("Flag reCAPTCHA cURL error: ".$curl_error); $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'reCAPTCHA server error.']; header('Location: '.$redirect_url); exit(); }
$response_keys = json_decode($response, true);

if (!$response_keys || !$response_keys['success']) {
    $error_codes = isset($response_keys['error-codes']) ? implode(', ',$response_keys['error-codes']) : 'N/A'; error_log("Flag reCAPTCHA verification failed. Codes: ".$error_codes);
    $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'reCAPTCHA verification failed. Please try again.'];
    header('Location: ' . $redirect_url); 
    exit();
}

// --- Validate Reason and Details ---
$errors = [];
$allowed_reasons = ['inaccurate', 'private_info', 'not_scam', 'spam_abuse', 'other'];
if (empty($reason) || !in_array($reason, $allowed_reasons)) {
    $errors[] = "Please select a valid reason for flagging.";
}
if (mb_strlen($details) > 2000) { // Limit details length
    $errors[] = "Details cannot exceed 2000 characters.";
}

// If validation errors, redirect back
if (!empty($errors)) {
    $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
    // We don't store flag form data in session for repopulation - keep it simple
    header('Location: ' . $redirect_url); 
    exit();
}

// --- Check if related scam report still exists and is approved ---
// (Optional but good practice to avoid flagging non-approved/deleted reports)
try {
    $stmt_check = $pdo->prepare("SELECT status FROM scams WHERE id = ?");
    $stmt_check->execute([$scam_id]);
    $scam_status = $stmt_check->fetchColumn();
    if ($scam_status !== 'approved') {
         $_SESSION['flag_feedback'] = ['type' => 'warning', 'message' => 'Issues can only be reported for currently approved reports.'];
         header('Location: ' . $redirect_url); 
         exit();
    }
} catch (PDOException $e) {
    error_log("DB error checking scam status before flag insert (scam_id: $scam_id): " . $e->getMessage());
    $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'Database error verifying report status before flagging.'];
    header('Location: ' . $redirect_url); 
    exit();
}


// --- Prepare Data for DB ---
$details_safe = !empty($details) ? htmlspecialchars($details, ENT_QUOTES, 'UTF-8') : null;
$ip_address = $_SERVER['REMOTE_ADDR']; 

// --- Insert Flag into Database ---
try {
    $sql_insert = "INSERT INTO flags (scam_id, reason, details, reporter_ip, status, created_at) 
                   VALUES (?, ?, ?, ?, 'new', NOW())";
    $stmt_insert = $pdo->prepare($sql_insert);
    
    if ($stmt_insert->execute([$scam_id, $reason, $details_safe, $ip_address])) {
        // Success! Set feedback and redirect
        $_SESSION['flag_feedback'] = ['type' => 'success', 'message' => 'Thank you for your feedback. This report has been flagged for moderator review.'];
        header('Location: ' . $redirect_url);
        exit();
    } else {
        // Insert failed (unlikely with exceptions enabled, but include fallback)
        error_log("Flag insert failed for scam_id: " . $scam_id);
        $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'Failed to save flag due to a database error.'];
        header('Location: ' . $redirect_url);
        exit();
    }

} catch (PDOException $e) {
    error_log("Database exception during flag insertion for scam_id {$scam_id}: " . $e->getMessage());
    $_SESSION['flag_feedback'] = ['type' => 'danger', 'message' => 'A critical database error occurred while saving your flag.'];
    header('Location: ' . $redirect_url); 
    exit();
}

?>