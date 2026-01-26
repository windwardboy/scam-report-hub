<?php
session_start(); // Start the session

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// --- Configuration ---
$recaptcha_secret = RECAPTCHA_SECRET_KEY; 
$report_form_page = 'report.php'; 
$generic_error_page = 'report.php'; 

// --- CSRF Token Verification ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF token validation failed. IP: " . $_SERVER['REMOTE_ADDR']); 
        $_SESSION['error_message'] = "Invalid or missing security token. Please try submitting the form again.";
        $_SESSION['form_data'] = $_POST;
        unset($_SESSION['form_data']['csrf_token']);
        header('Location: ' . $report_form_page);
        exit();
    }
} else {
    header('Location: ' . $report_form_page);
    exit();
}
// --- End CSRF Token Verification ---

// --- reCAPTCHA Verification ---
if (!isset($_POST['g-recaptcha-response'])) {
    $_SESSION['error_message'] = "reCAPTCHA verification is missing. Please try again.";
    $_SESSION['form_data'] = $_POST; 
    header('Location: ' . $report_form_page);
    exit();
}
$recaptcha_response = $_POST['g-recaptcha-response'];

// (reCAPTCHA cURL/file_get_contents logic remains the same...)
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([ 'secret' => $recaptcha_secret, 'response' => $recaptcha_response, 'remoteip' => $_SERVER['REMOTE_ADDR'] ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch); $curl_error = curl_error($ch); curl_close($ch);
    if ($response === false) { error_log("reCAPTCHA cURL error: " . $curl_error); $_SESSION['system_error'] = "Could not verify reCAPTCHA."; header('Location: '.$generic_error_page); exit(); }
    $response_keys = json_decode($response, true);
} else {
    $url = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response"; 
    $response = @file_get_contents($url); 
    if ($response === false) { error_log("reCAPTCHA file_get_contents error."); $_SESSION['system_error'] = "Could not verify reCAPTCHA."; header('Location: '.$generic_error_page); exit(); }
    $response_keys = json_decode($response, true);
}
if (!$response_keys || !$response_keys['success']) {
    $error_codes = isset($response_keys['error-codes']) ? implode(', ', $response_keys['error-codes']) : 'N/A';
    error_log("reCAPTCHA verification failed. Response: " . $response . " Error Codes: " . $error_codes);
    $_SESSION['error_message'] = "reCAPTCHA verification failed. Please try again.";
    $_SESSION['form_data'] = $_POST; unset($_SESSION['form_data']['g-recaptcha-response']); 
    header('Location: ' . $report_form_page); exit();
}

// --- Input Validation ---
$required_fields = [
    'scam_type' => 'Scam Type', 'title' => 'Report Title', 'description' => 'Description',
    'contact_method_type' => 'Contact Method Type', 'contact_method_details' => 'Contact Method Details', 'date_reported' => 'Date Reported'
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
    if ($timestamp === false || $timestamp > time()) { $errors[] = "Invalid date."; }
}
$user_email = !empty($_POST['user_email']) ? trim($_POST['user_email']) : null;
if ($user_email && !filter_var($user_email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid optional email format."; }

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors); 
    $_SESSION['form_data'] = $_POST; 
    header('Location: ' . $report_form_page); exit();
}

// --- Limit Submission Rate --- 
$ip_address = $_SERVER['REMOTE_ADDR'];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE ip_address = ? AND timestamp > NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$ip_address]); $submission_count = $stmt->fetchColumn();
    if ($submission_count > 5) { $_SESSION['error_message'] = "Too many submissions."; header('Location: '.$report_form_page); exit(); }
} catch (PDOException $e) { error_log("DB error rate limit check: " . $e->getMessage()); $_SESSION['system_error'] = "DB error."; header('Location: '.$generic_error_page); exit(); }

// --- Secure File Upload Handling --- 
$evidence_path_for_db = null; 
if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
    // ... (Full secure file upload logic - unchanged) ...
     $upload_dir_relative = 'uploads/';
     $upload_dir_absolute = __DIR__ . '/' . $upload_dir_relative;
     $max_file_size = 5 * 1024 * 1024; 
     $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt'];
     $allowed_mime_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain' ];
     if (!is_dir($upload_dir_absolute)) { if (!is_writable(dirname($upload_dir_absolute))) { /* Error handling */ $_SESSION['system_error']="Server config error"; header('Location: '.$generic_error_page); exit(); } if (!mkdir($upload_dir_absolute, 0755, true)) { /* Error handling */ $_SESSION['system_error']="Server error"; header('Location: '.$generic_error_page); exit(); } $htaccess_content = "Options -Indexes\nphp_flag engine off\nRemoveHandler .php .phtml .php3 .cgi .pl .asp .aspx .shtml .shtm .fcgi .fpl\n<Files *>\n Deny from all\n</Files>\n<FilesMatch \"\\.(?i:".implode('|', $allowed_extensions).")$\">\n Allow from all\n</FilesMatch>"; if (@file_put_contents($upload_dir_absolute . '.htaccess', $htaccess_content) === false) { error_log("SECURITY WARNING: Failed to write .htaccess to upload directory: " . $upload_dir_absolute); } @chmod($upload_dir_absolute, 0755); } elseif (!is_writable($upload_dir_absolute)) { /* Error handling */ $_SESSION['system_error']="Server config error"; header('Location: '.$generic_error_page); exit(); }
     if ($_FILES['evidence']['size'] > $max_file_size) { /* Error handling */ $_SESSION['error_message']="File too large"; $_SESSION['form_data'] = $_POST; header('Location: '.$report_form_page); exit(); }
     $file_info = pathinfo($_FILES['evidence']['name']); $extension = strtolower($file_info['extension'] ?? ''); $mime_type = null; if (function_exists('finfo_open')) { $finfo = finfo_open(FILEINFO_MIME_TYPE); if ($finfo) { $mime_type = finfo_file($finfo, $_FILES['evidence']['tmp_name']); finfo_close($finfo); } else { error_log("Warning: finfo_open failed."); } } else { error_log("Warning: finfo extension not available."); }
     if (!in_array($extension, $allowed_extensions) || ($mime_type && !in_array($mime_type, $allowed_mime_types))) { /* Error handling */ $_SESSION['error_message']="Invalid file type"; $_SESSION['form_data'] = $_POST; header('Location: '.$report_form_page); exit(); }
     try { $safe_filename = bin2hex(random_bytes(16)) . '.' . $extension; } catch (Exception $e) { /* Error handling */ $_SESSION['system_error']="Server error"; header('Location: '.$generic_error_page); exit(); } $destination_path = $upload_dir_absolute . $safe_filename;
     if (is_uploaded_file($_FILES['evidence']['tmp_name'])) { if (move_uploaded_file($_FILES['evidence']['tmp_name'], $destination_path)) { $evidence_path_for_db = $upload_dir_relative . $safe_filename; @chmod($destination_path, 0644); } else { /* Error handling */ $_SESSION['system_error']="Could not save file."; header('Location: '.$generic_error_page); exit(); } } else { /* Error handling */ $_SESSION['system_error']="Invalid upload attempt."; header('Location: '.$generic_error_page); exit(); }
} elseif (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) { /* Handle other upload errors */ $_SESSION['system_error']="File upload error."; header('Location: '.$generic_error_page); exit(); }
// --- End Secure File Upload Handling ---


// --- Generate Edit Token --- 
try { $edit_token = bin2hex(random_bytes(32)); } catch (Exception $e) { /* Error handling */ $_SESSION['system_error']="Critical error."; header('Location: '.$generic_error_page); exit(); }


// --- Generate Unique Slug ---
// **** MODIFIED: Use the validated & trimmed title from validation section ****
if (empty($title)) { // Add a fallback just in case title was empty (shouldn't happen with validation)
    $title_for_slug_base = 'report'; 
} else {
    $title_for_slug_base = $title; // Use the title variable
}

// Basic sanitization - remove non-alphanumeric, replace spaces/multiple hyphens with single hyphen
$base_slug = strtolower($title_for_slug_base); // Use title here
// **** END OF MODIFICATION ****
$base_slug = preg_replace('/[^a-z0-9\s-]/', '', $base_slug); // Keep this sanitization
$base_slug = preg_replace('/[\s-]+/', '-', $base_slug); // Keep this sanitization
$base_slug = trim($base_slug, '-'); // Keep this sanitization
if (empty($base_slug)) { $base_slug = 'report'; } // Keep fallback
$base_slug = substr($base_slug, 0, 100); // Keep length limit

$unique_slug = $base_slug;
$counter = 1;
$max_attempts = 100; 
try {
    // Uniqueness check loop remains the same
    while (true) {
        $stmt_slug = $pdo->prepare("SELECT 1 FROM scams WHERE slug = ? LIMIT 1"); 
        $stmt_slug->execute([$unique_slug]);
        if ($stmt_slug->fetchColumn() === false) { break; }
        if ($counter >= $max_attempts) { /* Fallback slug generation */ $unique_slug = 'report-' . bin2hex(random_bytes(8)); $stmt_slug->execute([$unique_slug]); if ($stmt_slug->fetchColumn() !== false) { throw new Exception("Slug generation failed"); } break; }
        $unique_slug = $base_slug . '-' . $counter;
        $counter++;
    }
} catch (PDOException | Exception $e) { /* Error handling */ $_SESSION['system_error']="DB error preparing report."; header('Location: '.$generic_error_page); exit(); }

// --- Prepare Title & Description for DB (Store Raw) ---
$title_for_db = $title; // Use the already trimmed title from validation phase
$description_for_db = trim($_POST['description'] ?? ''); // Use the trimmed description

// --- Insert into Database ---
try {
    $pdo->beginTransaction(); 

    // INSERT statement includes 'title' column and corresponding '?' placeholder
    $stmt_insert = $pdo->prepare("INSERT INTO scams (
        scam_type, title, description, contact_method_type, contact_method_details,
        date_reported, evidence_path, slug, edit_token, user_email, ip_address, status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"); 

    // Execute array includes $title_safe
    $stmt_insert->execute([
        $_POST['scam_type'], $title_for_db, $description_for_db,  
        $_POST['contact_method_type'], $_POST['contact_method_details'], 
        $date_reported, $evidence_path_for_db, $unique_slug, $edit_token,
        $user_email, $ip_address, 'pending' 
    ]);

    $lastInsertId = $pdo->lastInsertId(); 

    // Track submission for rate limiting
    $stmt_track = $pdo->prepare("INSERT INTO submissions (ip_address, timestamp) VALUES (?, NOW())");
    $stmt_track->execute([$ip_address]);

    $pdo->commit(); 

} catch (PDOException $e) {
    $pdo->rollBack(); 
    error_log("Database error during report insertion: " . $e->getMessage() . " - SQL State: " . $e->getCode());
    $_SESSION['system_error'] = "A critical database error occurred while saving your report.";
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . $generic_error_page); 
    exit();
}

// --- Optional Email Notification --- 
if ($user_email) {
    // ... (Email sending logic remains the same, already includes title) ...
    $subject = "Your Scam Report Edit Link – Scam Report Hub";
    $site_url = 'https://www.scamreporthub.com'; 
    $edit_link = rtrim($site_url, '/') . "/edit.php?token=" . urlencode($edit_token);
    $view_link = rtrim($site_url, '/') . "/scam/" . urlencode($unique_slug); // Slug is now based on title
    $message = "Thank you for your submission to Scam Report Hub!\n\n";
    $message .= "Your report (Ref: $lastInsertId) is now pending review by our moderators.\n";
    $message .= "Report Title: " . $title . "\n"; 
    $message .= "Once approved, it will be visible at: $view_link\n\n";
    $message .= "You can use the private link below to modify or delete your report before or after approval:\n";
    $message .= $edit_link . "\n\n";
    $message .= "Please keep this link safe and do not share it. Anyone with this link can modify your report.\n\n";
    $message .= "— The Scam Report Hub Team\n";
    $message .= $site_url;
    $headers = "From: Scam Report Hub <no-reply@scamreporthub.com>\r\n" . "Reply-To: no-reply@scamreporthub.com\r\n" . "X-Mailer: PHP/" . phpversion() . "\r\n" . "MIME-Version: 1.0\r\n" . "Content-type: text/plain; charset=UTF-8"; 
    if (!@mail($user_email, $subject, $message, $headers)) { error_log("Failed to send edit link email to: " . $user_email . " for report ID: " . $lastInsertId); }
}

// --- Redirect to Success Page --- 
unset($_SESSION['form_data']);
unset($_SESSION['error_message']);
unset($_SESSION['system_error']);

header("Location: /index.php?submitted=1"); 
exit();

?>