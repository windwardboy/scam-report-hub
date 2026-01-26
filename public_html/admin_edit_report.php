<?php
session_start(); // Start session first

// === Admin Authentication Check === (CRITICAL for this page)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php'); 
    exit(); 
}
// === End Authentication Check ===

// --- CSRF Token Generation --- (Needed for the form)
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } 
    catch (Exception $e) { $_SESSION['csrf_token'] = 'fallback-token-error-'.time(); error_log("CSRF token gen failed in admin_edit_report.php: ".$e->getMessage()); }
}
$csrf_token = $_SESSION['csrf_token']; 
// --- End CSRF Token Generation ---

include 'includes/db.php'; // Include DB

// --- Get Report ID and Fetch Data ---
$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); // Get ID from URL (?id=...)
$scam = null; 
$page_error = null; // General error message for this page

if (!$report_id || $report_id <= 0) {
    $page_error = "Invalid or missing Report ID.";
    http_response_code(400); 
} else {
    try {
        // Fetch report data by ID - Admin can edit any status
        $stmt = $pdo->prepare("SELECT * FROM scams WHERE id = ?"); 
        $stmt->execute([$report_id]);
        $scam = $stmt->fetch(PDO::FETCH_ASSOC); 

        if (!$scam) {
            $page_error = "Report not found for ID: " . htmlspecialchars($report_id);
            http_response_code(404);
        }
    } catch (PDOException $e) {
         error_log("Admin Edit - Error fetching report ID $report_id: " . $e->getMessage());
         $page_error = "A database error occurred while loading the report.";
         $scam = null; 
         http_response_code(500);
    }
}
// --- End Data Fetching ---

// Retrieve session data for potential validation errors from previous attempt
$error_message = $_SESSION['admin_edit_error'] ?? null; // Use a specific session key
unset($_SESSION['admin_edit_error']);
$form_data = $_SESSION['admin_edit_form_data'] ?? []; 
unset($_SESSION['admin_edit_form_data']);

// Helper function (same as before)
function get_value($field_name, $form_data, $scam_data) {
    if (!empty($form_data) && isset($form_data[$field_name])) { return htmlspecialchars($form_data[$field_name]); }
    if (is_array($scam_data) && isset($scam_data[$field_name])) { return htmlspecialchars($scam_data[$field_name]); }
    return ''; 
}

// Determine Page Title
$page_title = $page_error ? "Error" : "Edit Report #" . htmlspecialchars($report_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Scam Report Hub Admin</title>
    <meta name="robots" content="noindex, nofollow"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
         label.required::after { content: " *"; }
         .navbar-brand img { height: 55px; }
         .navbar-brand .text-logo { display: none !important; }
     </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">

    <h1 class="mb-4">Edit Scam Report (ID: <?php echo htmlspecialchars($report_id ?? ''); ?>)</h1>

    <?php // Display page errors (e.g., report not found) OR validation errors from session
    if ($page_error): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($page_error); ?></div>
        <a href="admin_moderate.php" class="btn btn-secondary">Back to Moderation List</a>
    <?php elseif ($error_message): // Validation errors from previous attempt ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please correct the following errors:</strong><br>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <?php // Only show form if the scam data was successfully loaded ($scam is not null) and no page error occurred
    if ($scam && !$page_error): ?> 
    
    <p>Modify the report details below. Changes will be saved directly.</p>

    <form action="admin_update_report.php" method="post" enctype="multipart/form-data" novalidate>
        
        <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($scam['id']); ?>"> 
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="mb-3">
            <label for="scam_type" class="form-label required">Scam Type *</label>
            <select name="scam_type" id="scam_type" class="form-select" required>
                <option value="">-- Select Type --</option>
                <?php
                $types = [ 'phishing' => 'Phishing', 'fake_online_store' => 'Fake Online Store', 'investment_scam' => 'Investment Scam', 'tech_support_scam' => 'Tech Support Scam', 'loan_grant_scam' => 'Loan or Grant Scam', 'romance_scam' => 'Romance Scam', 'job_scam' => 'Job Scam', 'fake_legal_threat' => 'Fake Legal Threat', 'delivery_shipping_scam' => 'Delivery/Shipping Scam', 'crypto_scam' => 'Crypto Scam', 'other' => 'Other' ];
                $db_value_scam_type = isset($scam['scam_type']) ? strtolower(trim($scam['scam_type'])) : null; 
                $current_value = $form_data['scam_type'] ?? $db_value_scam_type ?? ''; 
                foreach ($types as $value => $label) { $selected = ($value === $current_value) ? 'selected' : ''; echo "<option value=\"".htmlspecialchars($value)."\" $selected>".htmlspecialchars($label)."</option>"; }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="title" class="form-label required">Report Title *</label>
            <input type="text" name="title" id="title" class="form-control" maxlength="255" placeholder="e.g., Fake Amazon Delivery SMS..." value="<?php echo get_value('title', $form_data, $scam); ?>" required> 
            <div class="form-text">Enter a short, descriptive title (Max 255 characters)</div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label required">Description *</label>
            <textarea name="description" id="description" class="form-control" rows="5" required><?php echo get_value('description', $form_data, $scam); ?></textarea>
            <div class="form-text">Provide details, avoid sensitive personal info.</div>
        </div>

        <div class="mb-3">
            <label for="contact_method_type" class="form-label required">Contact Method Type *</label>
            <select name="contact_method_type" id="contact_method_type" class="form-select" required>
                <option value="">-- Select Method --</option>
                <?php
                $methods = [ 'email' => 'Email', 'phone' => 'Phone Call / Voicemail', 'website' => 'Website / Online Form', 'social_media' => 'Social Media', 'sms_message' => 'SMS / Text Message', 'in_person' => 'In Person', 'mail' => 'Physical Mail', 'other' => 'Other' ];
                $db_value_contact_type = isset($scam['contact_method_type']) ? strtolower(trim($scam['contact_method_type'])) : null;
                $current_value = $form_data['contact_method_type'] ?? $db_value_contact_type ?? '';
                foreach ($methods as $value => $label) { $selected = ($value === $current_value) ? 'selected' : ''; echo "<option value=\"".htmlspecialchars($value)."\" $selected>".htmlspecialchars($label)."</option>"; }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="contact_method_details" class="form-label required">Contact Method Details *</label>
            <input type="text" name="contact_method_details" id="contact_method_details" class="form-control" required value="<?php echo get_value('contact_method_details', $form_data, $scam); ?>">
            <div class="form-text">The specific email, phone #, URL, username, etc.</div>
        </div>

        <div class="mb-3">
            <label for="date_reported" class="form-label required">Approximate Date Scam Occurred *</label>
            <input type="date" name="date_reported" id="date_reported" class="form-control" required value="<?php echo get_value('date_reported', $form_data, $scam); ?>" max="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">User Email (Optional)</label>
            <input type="text" class="form-control" value="<?php echo get_value('user_email', $form_data, $scam); ?>" disabled readonly>
            <div class="form-text">This is the email provided by the user for edit links (if any). Cannot be changed here.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Evidence:</label><br>
            <?php if (!empty($scam['evidence_path'])): ?>
                <?php $clean_path = trim(str_replace('..', '', $scam['evidence_path'] ?? ''), '/'); $evidence_url = '/' . $clean_path; if (strpos($clean_path, '/') === 0 || strpos($clean_path, '..') !== false) { $evidence_url = '#invalid-path'; error_log("Invalid evidence path: ".$scam['evidence_path']); } ?>
                <a href="<?php echo htmlspecialchars($evidence_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View Current Evidence</a>
            <?php else: ?>
                <span class="text-muted">No file uploaded</span>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="evidence" class="form-label">Upload New Evidence (optional)</label>
            <input type="file" name="evidence" id="evidence" class="form-control">
            <div class="form-text">Select a new file to replace the current one (Max 5MB: jpg, ..., txt). Leave blank to keep the current file.</div>
        </div>

        <button type="submit" class="btn btn-primary">Save Admin Changes</button>
        <a href="admin_view_report.php?id=<?php echo htmlspecialchars($scam['id']); ?>" class="btn btn-secondary ms-2">Cancel</a> 

        </form>

    <?php endif; // End conditional form display ?>

</div><?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>