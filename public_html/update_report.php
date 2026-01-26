<?php
session_start(); // Start the session

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// Define target pages for redirects 
$edit_form_page = 'edit.php'; 
$generic_error_page = 'edit.php'; 
$browse_page = 'browse.php'; 
$home_page = 'index.php'; // For redirect after successful deletion

// Check that the request is a POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    // Redirect without specific message or token if accessed wrongly
    header('Location: ' . $browse_page); 
    exit();
}

// --- CSRF Token Verification ---
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token validation failed during update/delete. IP: " . $_SERVER['REMOTE_ADDR']);
    $_SESSION['error_message'] = "Invalid or missing security token. Please try submitting the form again.";
    // Try redirecting back to edit form if token is available
    $edit_token_for_redirect = $_POST['edit_token'] ?? null;
    if ($edit_token_for_redirect) {
        $_SESSION['form_data'] = $_POST; // Preserve data only if redirecting back to edit
        unset($_SESSION['form_data']['csrf_token']);
        header('Location: ' . $edit_form_page . '?token=' . urlencode($edit_token_for_redirect));
    } else {
        // Cannot redirect back safely, go to browse page
        header('Location: ' . $browse_page); 
    }
    exit();
}
// Consider unsetting token for single-use if needed: unset($_SESSION['csrf_token']);
// --- End CSRF Token Verification ---

// --- Get Edit Token and Action ---
$token = $_POST['edit_token'] ?? ''; 
$action = $_POST['action'] ?? 'update'; // Default action is 'update' if not specified

if (empty($token)) {
    $_SESSION['system_error'] = 'Edit request is missing the required identifier.';
    header('Location: ' . $browse_page);
    exit();
}

// --- Fetch Existing Scam Data (Needed for both update and delete) ---
try {
    $stmt = $pdo->prepare("SELECT id, slug, evidence_path FROM scams WHERE edit_token = ?"); // Select only needed fields
    $stmt->execute([$token]);
    $scam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scam) {
        $_SESSION['system_error'] = 'Invalid or expired edit token.';
        header('Location: ' . $browse_page);
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error fetching scam by token in update_report.php: " . $e->getMessage());
    $_SESSION['system_error'] = "A database error occurred while verifying your request.";
    header('Location: ' . $generic_error_page . '?token=' . urlencode($token)); 
    exit();
}


// --- Process Action ---

if ($action === 'delete_by_token') {
    // --- Handle User Deletion Request ---
    $delete_success = false;
    $evidence_path_relative = $scam['evidence_path']; // Get path fetched earlier

    try {
        $pdo->beginTransaction();

        // Delete the database row using the UNIQUE edit_token
        $stmt_delete = $pdo->prepare("DELETE FROM scams WHERE edit_token = ?");
        $delete_success = $stmt_delete->execute([$token]);

        if ($delete_success && $stmt_delete->rowCount() > 0) {
            $pdo->commit();
            // Use a different session key for user feedback if desired
            $_SESSION['user_feedback'] = ['type' => 'success', 'message' => "Your report (ID: {$scam['id']}) has been permanently deleted."];

            // Try deleting file AFTER successful DB commit
            if ($evidence_path_relative) {
                $evidence_path_absolute = __DIR__ . '/' . ltrim($evidence_path_relative, '/');
                if (file_exists($evidence_path_absolute) && is_file($evidence_path_absolute)) {
                    if (!@unlink($evidence_path_absolute)) {
                         error_log("User Delete WARNING: Failed to delete evidence file: " . $evidence_path_absolute . " for deleted report ID: " . $scam['id']);
                         $_SESSION['user_feedback']['message'] .= " (Warning: Could not delete evidence file)"; // Append warning
                    }
                } else {
                     error_log("User Delete Notice: Evidence file not found on disk: " . $evidence_path_absolute . " for report ID: " . $scam['id']);
                }
            }
            // Redirect to homepage with success flag after delete
            header("Location: " . $home_page . "?deleted=1");
            exit();

        } else {
            // Deletion failed or no rows affected (token mismatch?)
            $pdo->rollBack();
            error_log("Database error deleting report via token: " . $token . ". rowCount: " . ($delete_success ? $stmt_delete->rowCount() : 'N/A'));
            $_SESSION['error_message'] = "Failed to delete report from database. Please try again.";
            header('Location: ' . $edit_form_page . '?token=' . urlencode($token)); // Go back to edit form
            exit();
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Database exception during user report deletion via token {$token}: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred during deletion. Please try again.";
        header('Location: ' . $edit_form_page . '?token=' . urlencode($token)); // Go back to edit form
        exit();
    }
    // --- End Deletion Handling ---

} else { 
    // --- Handle Update Request (Default Action) ---

    // --- Input Validation ---
    $required_fields = [ 'scam_type' => 'Scam Type', 'title' => 'Report Title', /* ... other fields ... */ 'date_reported' => 'Date Reported' ];
    // (Keep all the validation logic from the previous version here...)
    $required_fields = [ 'scam_type' => 'Scam Type', 'title' => 'Report Title', 'description' => 'Description', 'contact_method_type' => 'Contact Method Type', 'contact_method_details' => 'Contact Method Details', 'date_reported' => 'Date Reported' ]; $errors = []; foreach ($required_fields as $field => $label) { if (!isset($_POST[$field]) || trim($_POST[$field]) === '') { $errors[] = "$label is required."; } }
    $title = trim($_POST['title'] ?? ''); if (mb_strlen($title) > 255) { $errors[] = 'Report title cannot exceed 255 characters.'; }
    if (!empty($_POST['contact_method_type']) && $_POST['contact_method_type'] === 'email') { if (!filter_var($_POST['contact_method_details'], FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email address format."; } }
    $date_reported = $_POST['date_reported'] ?? null; if ($date_reported) { $timestamp = strtotime($date_reported); if ($timestamp === false || $timestamp > time()) { $errors[] = "Invalid date."; } }
    
    // (Keep File Upload Validation logic here...)
    $new_file_uploaded_and_valid = false; $validated_extension = null; if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) { $max_file_size = 5*1024*1024; $allowed_extensions = ['jpg','jpeg','png','gif','webp','pdf','txt']; $allowed_mime_types = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','text/plain']; if ($_FILES['evidence']['size'] > $max_file_size) { $errors[] = "File too large."; } else { $file_info = pathinfo($_FILES['evidence']['name']); $extension = strtolower($file_info['extension'] ?? ''); $mime_type = null; if (function_exists('finfo_open')) { $finfo = finfo_open(FILEINFO_MIME_TYPE); if($finfo){ $mime_type = finfo_file($finfo, $_FILES['evidence']['tmp_name']); finfo_close($finfo); } else { error_log("finfo_open failed in update."); } } else { error_log("finfo not available in update."); } if (!in_array($extension, $allowed_extensions) || ($mime_type && !in_array($mime_type, $allowed_mime_types))) { $errors[] = "Invalid file type."; } else { $new_file_uploaded_and_valid = true; $validated_extension = $extension; } } } elseif (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) { $upload_errors = [ /*...*/ ]; $error_code=$_FILES['evidence']['error']; $message = $upload_errors[$error_code] ?? 'Unknown upload error.'; error_log("Upload error update: $error_code"); $errors[] = "Upload error: $message"; }

    // If validation errors, redirect back
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST; 
        header('Location: ' . $edit_form_page . '?token=' . urlencode($token)); 
        exit();
    }

    // --- Secure File Upload Handling (If new file uploaded) ---
    $evidence_path_for_db = $scam['evidence_path']; // Default to old path
    if ($new_file_uploaded_and_valid) {
        // (Keep the full secure file upload logic here - moving file, deleting old one)
        // It should set $evidence_path_for_db to the *new* relative path if successful
        $upload_dir_relative = 'uploads/'; $upload_dir_absolute = __DIR__.'/'.$upload_dir_relative;
        if (!is_dir($upload_dir_absolute)) { if(!is_writable(dirname($upload_dir_absolute))){/*...*/ $_SESSION['system_error']="Server error"; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit(); } if(!mkdir($upload_dir_absolute, 0755, true)){/*...*/ $_SESSION['system_error']="Server error"; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit(); } $htaccess_content = "Options -Indexes\n..."; if(@file_put_contents($upload_dir_absolute.'.htaccess', $htaccess_content)===false){error_log("Failed writing htaccess update");} @chmod($upload_dir_absolute, 0755); } elseif(!is_writable($upload_dir_absolute)){/*...*/ $_SESSION['system_error']="Server error"; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit();}
        try { $safe_filename=bin2hex(random_bytes(16)).'.'.$validated_extension; } catch(Exception $e) {/*...*/ $_SESSION['system_error']="Server error"; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit(); } $destination_path=$upload_dir_absolute.$safe_filename;
        if(is_uploaded_file($_FILES['evidence']['tmp_name'])) { if(move_uploaded_file($_FILES['evidence']['tmp_name'], $destination_path)) { $new_evidence_path_relative=$upload_dir_relative.$safe_filename; @chmod($destination_path, 0644); $old_evidence_path_absolute=$scam['evidence_path'] ? (__DIR__.'/'.ltrim($scam['evidence_path'], '/')) : null; if($old_evidence_path_absolute && file_exists($old_evidence_path_absolute) && is_file($old_evidence_path_absolute)) { if(!@unlink($old_evidence_path_absolute)){error_log("Could not delete old evidence: ".$old_evidence_path_absolute);} } $evidence_path_for_db=$new_evidence_path_relative; } else {/*...*/ $_SESSION['system_error']="Could not save file."; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit(); } } else {/*...*/ $_SESSION['system_error']="Invalid upload."; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit(); }
    }
    // --- End File Handling ---

    // --- Prepare Title & Description for DB (Store Raw) ---
        $title_for_db = $title; // Use the already trimmed title from validation phase
        $description_for_db = trim($_POST['description'] ?? ''); // Use the trimmed description

    // --- Update the Report in Database ---
    try {
        $pdo->beginTransaction(); 
        $update_stmt = $pdo->prepare("UPDATE scams SET scam_type = ?, title = ?, description = ?, contact_method_type = ?, contact_method_details = ?, date_reported = ?, evidence_path = ?, updated_at = NOW() WHERE edit_token = ?");
        $success = $update_stmt->execute([ $_POST['scam_type'], $title_for_db, $description_for_db, $_POST['contact_method_type'], $_POST['contact_method_details'], $date_reported, $evidence_path_for_db, $token ]);
        if ($success) { $pdo->commit(); } 
        else { $pdo->rollBack(); error_log("DB execute error update token: ".$token); $_SESSION['system_error']="DB error saving changes."; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit(); }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("DB exception update: ".$e->getMessage()); $_SESSION['system_error']="DB error saving changes."; $_SESSION['form_data']=$_POST; header('Location: '.$generic_error_page.'?token='.urlencode($token)); exit();
    }

    // --- Redirect after successful UPDATE ---
    unset($_SESSION['form_data']); unset($_SESSION['error_message']); unset($_SESSION['system_error']);
    // Use status fetched BEFORE update to decide redirect
    if ($scam && $scam['status'] === 'approved') {
        header("Location: /scam/" . urlencode($scam['slug']) . "?updated=1");
    } else {
        header("Location: " . $edit_form_page . "?token=" . urlencode($token) . "&updated=1");
    }
    exit();
    // --- End Update Handling ---
} 
// Should not be reached if action was valid
header('Location: ' . $browse_page);
exit();

?>