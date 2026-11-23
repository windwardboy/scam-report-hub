<?php
session_start(); 

// --- CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } 
    catch (Exception $e) { $_SESSION['csrf_token'] = 'fallback-token-error-'.time(); error_log("CSRF token gen failed: ".$e->getMessage()); }
}
$csrf_token = $_SESSION['csrf_token']; 
// --- End CSRF Token Generation ---

include 'includes/db.php';

$token = $_GET['token'] ?? '';
$scam = null; 
$token_error = null; 
$page_message = null; 
$page_message_type = 'info'; 
$allow_editing = false; 

if (empty($token)) {
    $token_error = "No edit token provided. Please use the link from your email.";
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM scams WHERE edit_token = ?"); 
        $stmt->execute([$token]);
        $scam = $stmt->fetch(PDO::FETCH_ASSOC); 

        if (!$scam) {
            $token_error = "Invalid or expired edit token. Please use the correct link from your email.";
        } else {
            // Check Status
            if ($scam['status'] === 'rejected') {
                $page_message = "This report submission was reviewed and rejected by moderators. It can no longer be edited or viewed publicly.";
                $page_message_type = 'warning'; 
                $allow_editing = false; 
            } elseif ($scam['status'] === 'draft' || $scam['status'] === 'approved' || $scam['status'] === 'pending') {
                $allow_editing = true; 
            } else {
                 $token_error = "The report associated with this link has an unexpected status.";
                 $allow_editing = false;
            }
        }
    } catch (PDOException $e) {
         error_log("Database error fetching scam for edit: " . $e->getMessage());
         $token_error = "A database error occurred while loading the report. Please try again later.";
         $scam = null; 
         $allow_editing = false;
    }
}

// Retrieve session/GET data
$error_message = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
$system_error = $_SESSION['system_error'] ?? null; unset($_SESSION['system_error']);
$form_data = $_SESSION['form_data'] ?? []; unset($_SESSION['form_data']);
$update_success_message = (isset($_GET['updated']) && $_GET['updated'] == '1') ? '✅ Your scam report has been successfully updated.' : null;

// Helper function
function get_value($field_name, $form_data, $scam_data) {
    if (!empty($form_data) && isset($form_data[$field_name])) { return htmlspecialchars($form_data[$field_name]); }
    if (is_array($scam_data) && isset($scam_data[$field_name])) { return htmlspecialchars($scam_data[$field_name]); }
    return ''; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - Scam Report Hub</title>
    <meta name="robots" content="noindex, nofollow"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
     <style>
         label.required::after { content: " *"; }
         .navbar-brand img { height: 55px; }
         .navbar-brand .text-logo { display: none !important; }
         .delete-section { border-top: 1px solid #dee2e6; margin-top: 2rem; padding-top: 1.5rem; }
     </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">

    <h1 class="mb-4">Edit Your Scam Report</h1>

    <?php // Display various messages 
    if ($token_error): ?>
        <div class="alert alert-danger" role="alert"><strong>Access Denied:</strong> <?php echo htmlspecialchars($token_error); ?></div>
    <?php elseif ($page_message): ?>
        <div class="alert alert-<?php echo $page_message_type; ?>" role="alert"><?php echo htmlspecialchars($page_message); ?></div>
     <?php elseif ($update_success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo $update_success_message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Please correct the following errors:</strong><br><?php echo $error_message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php elseif ($system_error): ?>
         <div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Notice:</strong><br><?php echo htmlspecialchars($system_error); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>


    <?php // Conditionally display the form 
    if ($allow_editing): ?>
    
    <?php if (($scam['status'] ?? '') === 'draft'): ?>
        <div class="alert alert-secondary" role="alert">This report is saved as a draft and is hidden from the public site. You can still update it here. A moderator needs to approve it before it is published again.</div>
    <?php endif; ?>
    <p>Make corrections to your submitted report below. Fields marked with * are required.</p>

    <form action="update_report.php" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="edit_token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="mb-3"> <label for="scam_type" class="form-label required">Scam Type *</label> <select name="scam_type" id="scam_type" class="form-select" required> <option value="">-- Select Type --</option> <?php $types = [ 'phishing' => 'Phishing', 'fake_online_store' => 'Fake Online Store', 'investment_scam' => 'Investment Scam', 'tech_support_scam' => 'Tech Support Scam', 'loan_grant_scam' => 'Loan or Grant Scam', 'romance_scam' => 'Romance Scam', 'job_scam' => 'Job Scam', 'fake_legal_threat' => 'Fake Legal Threat', 'delivery_shipping_scam' => 'Delivery/Shipping Scam', 'crypto_scam' => 'Crypto Scam', 'other' => 'Other' ]; $db_value_scam_type = isset($scam['scam_type']) ? strtolower(trim($scam['scam_type'])) : null; $current_value = $form_data['scam_type'] ?? $db_value_scam_type ?? ''; foreach ($types as $value => $label) { $selected = ($value === $current_value) ? 'selected' : ''; echo "<option value=\"".htmlspecialchars($value)."\" $selected>".htmlspecialchars($label)."</option>"; } ?> </select> </div>
        <div class="mb-3"> <label for="title" class="form-label required">Report Title *</label> <input type="text" name="title" id="title" class="form-control" maxlength="255" placeholder="e.g., Fake Amazon Delivery SMS..." value="<?php echo get_value('title', $form_data, $scam); ?>" required> <div class="form-text">Enter a short, descriptive title (Max 255 characters)</div> </div>
        <div class="mb-3"> <label for="description" class="form-label required">Description *</label> <textarea name="description" id="description" class="form-control" rows="5" required><?php echo get_value('description', $form_data, $scam); ?></textarea> <div class="form-text">Provide details, avoid sensitive personal info.</div> </div>
        <div class="mb-3"> <label for="contact_method_type" class="form-label required">Contact Method Type *</label> <select name="contact_method_type" id="contact_method_type" class="form-select" required> <option value="">-- Select Method --</option> <?php $methods = [ 'email' => 'Email', 'phone' => 'Phone Call / Voicemail', 'website' => 'Website / Online Form', 'social_media' => 'Social Media', 'sms_message' => 'SMS / Text Message', 'in_person' => 'In Person', 'mail' => 'Physical Mail', 'other' => 'Other' ]; $db_value_contact_type = isset($scam['contact_method_type']) ? strtolower(trim($scam['contact_method_type'])) : null; $current_value = $form_data['contact_method_type'] ?? $db_value_contact_type ?? ''; foreach ($methods as $value => $label) { $selected = ($value === $current_value) ? 'selected' : ''; echo "<option value=\"".htmlspecialchars($value)."\" $selected>".htmlspecialchars($label)."</option>"; } ?> </select> </div>
        <div class="mb-3"> <label for="contact_method_details" class="form-label required">Contact Method Details *</label> <input type="text" name="contact_method_details" id="contact_method_details" class="form-control" required value="<?php echo get_value('contact_method_details', $form_data, $scam); ?>"> <div class="form-text">The specific email, phone #, URL, username, etc.</div> </div>
        <div class="mb-3"> <label for="date_reported" class="form-label required">Approximate Date Scam Occurred *</label> <input type="date" name="date_reported" id="date_reported" class="form-control" required value="<?php echo get_value('date_reported', $form_data, $scam); ?>" max="<?php echo date('Y-m-d'); ?>"> </div>
        <div class="mb-3"> <label for="user_email" class="form-label">Your Email (Optional)</label> <input type="email" name="user_email" id="user_email" class="form-control" placeholder="your.email@example.com" value="<?php echo get_value('user_email', $form_data, $scam); ?>"> <div class="form-text">Used only to send your private edit/delete link. <strong>Will NOT be published.</strong></div> </div>
        <div class="mb-3"> <label class="form-label">Current Evidence:</label><br> <?php if (!empty($scam['evidence_path'])): ?> <?php $clean_path = trim(str_replace('..', '', $scam['evidence_path'] ?? ''), '/'); $evidence_url = '/' . $clean_path; if (strpos($clean_path, '/') === 0 || strpos($clean_path, '..') !== false) { $evidence_url = '#invalid-path'; error_log("Invalid evidence path: ".$scam['evidence_path']); } ?> <a href="<?php echo htmlspecialchars($evidence_url); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View Current Evidence</a> <?php else: ?> <span class="text-muted">No file uploaded</span> <?php endif; ?> </div>
        <div class="mb-3"> <label for="evidence" class="form-label">Upload New Evidence (optional)</label> <input type="file" name="evidence" id="evidence" class="form-control"> <div class="form-text">Select a new file to replace the current one (Max 5MB: jpg, png, gif, webp, pdf, txt). Leave blank to keep the current file.</div> </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/scam/<?php echo urlencode($scam['slug'] ?? 'error'); ?>" class="btn btn-secondary ms-2">Cancel</a> 

    </form> <hr class="my-4"> 

    <div class="delete-section border p-3 rounded border-danger-subtle bg-danger-subtle"> 
         <h5 class="text-danger mb-3">Delete This Report</h5>
         <p><strong class="text-danger">Warning:</strong> This action is permanent and cannot be undone. Clicking delete will remove your report and any uploaded evidence immediately.</p>
        
        <form action="update_report.php" method="POST" id="delete-report-form"> 
            <input type="hidden" name="edit_token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> 
            <input type="hidden" name="action" value="delete_by_token"> 
            <button type="submit" class="btn btn-danger"> <i class="bi bi-trash"></i> Delete My Report Permanently </button>
        </form>
    </div>
    <?php else: // If editing is not allowed ?>
        <?php // Display link back home if no other message was shown
            if(empty($token_error) && empty($page_message) && empty($update_success_message) && empty($error_message) && empty($system_error)) {
                echo '<p><a href="/index.php" class="btn btn-primary">Return Home</a></p>';
            } elseif (empty($token_error)) { 
                 echo '<p class="mt-3"><a href="/index.php" class="btn btn-secondary">Return Home</a></p>';
            }
        ?>
    <?php endif; // End conditional form display ?>

</div><?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/edit-form.js" defer></script> 
</body>
</html>