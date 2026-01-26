<?php
session_start(); // Start session first

// === Admin Authentication Check === (CRITICAL)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // If not admin, redirect to login or show error
    http_response_code(403); // Forbidden
    die("Access Denied."); // Simple message, or redirect to login
}
// === End Authentication Check ===

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// Define redirect targets
$admin_list_page = 'admin_moderate.php'; // Fallback if ID is lost
// Edit form page determined dynamically using report_id

// === Method Check ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    header('Location: ' . $admin_list_page); 
    exit();
}

// --- CSRF Token Verification ---
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token validation failed for admin update. IP: " . $_SERVER['REMOTE_ADDR']);
    $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid security token. Update aborted.'];
    // Cannot reliably redirect back to edit form without ID, go to list
    header('Location: ' . $admin_list_page); 
    exit();
}
// --- End CSRF Token Verification ---

// --- Get Report ID (Crucial) ---
$report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT); 
if (!$report_id || $report_id <= 0) {
    $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid or missing Report ID for update.'];
    header('Location: ' . $admin_list_page); 
    exit();
}
// Define redirect back to specific edit page now that we have the ID
$edit_page_redirect = "admin_edit_report.php?id=" . $report_id; 

// --- Fetch Original Evidence Path (Needed if replacing file) ---
// We need this *before* updating the DB record in case upload fails later
$original_evidence_path = null;
try {
    $stmt_old_path = $pdo->prepare("SELECT evidence_path FROM scams WHERE id = ?");
    $stmt_old_path->execute([$report_id]);
    $original_evidence_path = $stmt_old_path->fetchColumn();
} catch (PDOException $e) {
     error_log("Admin Update - Error fetching original evidence path for ID {$report_id}: " . $e->getMessage());
     $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Database error preparing update.'];
     header('Location: ' . $edit_page_redirect); 
     exit();
}


// --- Input Validation --- (Similar to submit_report.php)
$required_fields = [
    'scam_type' => 'Scam Type', 'title' => 'Report Title', 'description' => 'Description', 
    'contact_method_type' => 'Contact Method Type', 'contact_method_details' => 'Contact Method Details', 
    'date_reported' => 'Date Reported'
];
$errors = [];
foreach ($required_fields as $field => $label) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') { $errors[] = "$label is required."; }
}
$title = trim($_POST['title'] ?? ''); 
if (mb_strlen($title) > 255) { $errors[] = 'Report title cannot exceed 255 characters.'; }
if (!empty($_POST['contact_method_type']) && $_POST['contact_method_type'] === 'email') {
    if (!filter_var($_POST['contact_method_details'], FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email address format."; }
}
$date_reported = $_POST['date_reported'] ?? null;
if ($date_reported) {
    $timestamp = strtotime($date_reported);
    if ($timestamp === false || $timestamp > time()) { $errors[] = "Invalid date (cannot be future)."; }
}

// File Upload Validation (similar to update_report.php user version)
$new_file_uploaded_and_valid = false; 
$validated_extension = null; 
if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
    // Basic checks first - adapt full validation if needed from submit_report
    $max_file_size = 5*1024*1024; $allowed_extensions = ['jpg','jpeg','png','gif','webp','pdf','txt'];
    if ($_FILES['evidence']['size'] > $max_file_size) { $errors[] = "Uploaded evidence file too large (Max 5MB)."; } 
    else { 
        $file_info = pathinfo($_FILES['evidence']['name']); $extension = strtolower($file_info['extension'] ?? '');
        if (!in_array($extension, $allowed_extensions)) { $errors[] = "Invalid evidence file type."; } 
        else { $new_file_uploaded_and_valid = true; $validated_extension = $extension; /* Add MIME check if needed */ } 
    }
} elseif (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) { $errors[] = "File upload error occurred."; error_log("Admin Update Upload Error: ".$_FILES['evidence']['error']); }


// If validation errors, redirect back
if (!empty($errors)) {
    $_SESSION['admin_edit_error'] = implode('<br>', $errors); // Use specific session key
    $_SESSION['admin_edit_form_data'] = $_POST; // Store submitted data 
    header('Location: ' . $edit_page_redirect); 
    exit();
}

// --- If validation passes, process file and update DB ---

$evidence_path_for_db = $original_evidence_path; // Keep existing path unless new file is uploaded

if ($new_file_uploaded_and_valid) {
    // --- Handle New File Upload ---
    $upload_dir_relative = 'uploads/';
    $upload_dir_absolute = __DIR__ . '/' . $upload_dir_relative;
    // Ensure directory exists and is writable (add checks from submit_report if needed)
    
    try { $safe_filename = bin2hex(random_bytes(16)) . '.' . $validated_extension; } 
    catch (Exception $e) { error_log("Admin Update: Rand bytes failed: ".$e->getMessage()); $_SESSION['admin_edit_error'] = 'Server error generating filename.'; header('Location: '.$edit_page_redirect); exit(); }
    $destination_path = $upload_dir_absolute . $safe_filename;

    if (is_uploaded_file($_FILES['evidence']['tmp_name'])) {
        if (move_uploaded_file($_FILES['evidence']['tmp_name'], $destination_path)) {
             $new_evidence_path_relative = $upload_dir_relative . $safe_filename;
             @chmod($destination_path, 0644); 
             
             // Delete OLD file only if move was successful
             if ($original_evidence_path) {
                 $old_evidence_path_absolute = __DIR__ . '/' . ltrim($original_evidence_path, '/');
                 if (file_exists($old_evidence_path_absolute) && is_file($old_evidence_path_absolute)) {
                     if (!@unlink($old_evidence_path_absolute)) { error_log("Admin Update WARNING: Could not delete old evidence file: " . $old_evidence_path_absolute); /* Maybe add notice? */ }
                 }
             }
             // Set path for DB to the NEW file
             $evidence_path_for_db = $new_evidence_path_relative; 
        } else { $_SESSION['admin_edit_error'] = 'Server error saving uploaded file.'; error_log("Admin Update: move_uploaded_file failed."); header('Location: '.$edit_page_redirect); exit(); }
    } else { $_SESSION['admin_edit_error'] = 'Invalid file upload detected.'; error_log("Admin Update: is_uploaded_file check failed."); header('Location: '.$edit_page_redirect); exit(); }
}
// --- End File Handling ---


// --- Prepare Data for DB Update ---
$title_for_db = $title; // Use trimmed title from validation
$description_for_db = trim($_POST['description'] ?? ''); // Use trimmed description

// --- Update Database ---
try {
    $sql_update = "UPDATE scams SET 
                      scam_type = ?, 
                      title = ?, 
                      description = ?, 
                      contact_method_type = ?, 
                      contact_method_details = ?, 
                      date_reported = ?, 
                      evidence_path = ?, 
                      updated_at = NOW() 
                   WHERE id = ?"; // Use ID for WHERE clause

    $stmt_update = $pdo->prepare($sql_update);
    
    // Execute update
    $success = $stmt_update->execute([
        $_POST['scam_type'],
        $title_for_db,
        $description_for_db,
        $_POST['contact_method_type'],
        $_POST['contact_method_details'],
        $date_reported,
        $evidence_path_for_db, // Use the potentially updated path
        $report_id // WHERE clause parameter
    ]);

    if ($success) {
        $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Report #{$report_id} updated successfully."];
        // Redirect back to the admin view page for this report
        header("Location: admin_view_report.php?id=" . $report_id . "&updated=1"); 
        exit();
    } else {
        // This case might not be reached if execute throws exception
        error_log("Admin Update: DB execute failed for ID {$report_id}.");
        $_SESSION['admin_edit_error'] = "Database error saving changes.";
        $_SESSION['admin_edit_form_data'] = $_POST;
        header('Location: ' . $edit_page_redirect); 
        exit();
    }

} catch (PDOException $e) {
    error_log("Admin Update: DB exception for ID {$report_id}: " . $e->getMessage());
    $_SESSION['admin_edit_error'] = "A critical database error occurred while saving changes.";
    $_SESSION['admin_edit_form_data'] = $_POST; // Preserve data
    header('Location: ' . $edit_page_redirect); 
    exit();
}

?>