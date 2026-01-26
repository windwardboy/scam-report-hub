<?php // START OF report.php
session_start(); 

// --- CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = 'fallback-token-error-' . time(); 
        error_log("CSRF token generation failed: " . $e->getMessage());
    }
}
$csrf_token = $_SESSION['csrf_token'];
// --- End CSRF Token Generation ---

// Retrieve session data and unset it immediately
$error_message = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
$system_error = $_SESSION['system_error'] ?? null; unset($_SESSION['system_error']);
$form_data = $_SESSION['form_data'] ?? []; unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Submit a Scam Report | Scam Report Hub</title>
    <meta name="description" content="Submit details of any scam (phone, email, website, in-person, etc.) anonymously to Scam Report Hub. Help warn others and prevent fraud.">
    <meta name="keywords" content="report scam, submit scam, scam reporting, scam alert, fraud report, scam database, scam check, community safety, scam awareness">
    <meta name="author" content="Scam Report Hub">
    
    <link rel="canonical" href="https://www.scamreporthub.com/report.php"> 
    
    <meta property="og:title" content="Submit a Scam Report | Scam Report Hub">
    <meta property="og:description" content="Submit details of any scam (phone, email, website, in-person, etc.) anonymously to Scam Report Hub. Help warn others and prevent fraud.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.scamreporthub.com/report.php"> 
    <meta property="og:image" content="https://www.scamreporthub.com/images/og-image.jpg"> 
    <meta property="og:site_name" content="Scam Report Hub">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Submit a Scam Report | Scam Report Hub">
    <meta name="twitter:description" content="Submit details of any scam (phone, email, website, in-person, etc.) anonymously to Scam Report Hub. Help warn others and prevent fraud.">
    <meta name="twitter:image" content="https://www.scamreporthub.com/images/og-image.jpg"> 
    <link rel="icon" type="image/png" href="https://www.scamreporthub.com/favicon.png?v=2">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/klaro@0.7.22/dist/klaro.min.css" />

    <style>
        @media (max-width: 767.98px) { body { font-size: 1.1rem; } h1 { font-size: 2rem; } .btn { padding: 0.5rem 1rem; font-size: 1rem; } }
        .navbar-brand img { height: 55px; }
        .navbar-brand .text-logo { display: none !important; }
        label.required::after { content: " *"; } 
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5 mb-5"> 

        <?php if ($error_message): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"> <strong>Please correct the following errors:</strong><br> <?php echo $error_message; ?> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> </div> <?php endif; ?>
        <?php if ($system_error): ?> <div class="alert alert-warning alert-dismissible fade show" role="alert"> <strong>Notice:</strong><br> <?php echo htmlspecialchars($system_error); ?> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> </div> <?php endif; ?>

        <h1 class="mb-4">Report a Scam</h1>
        <p class="mb-4">Help protect others by sharing details about a scam you encountered. Fields marked with * are required.</p>

        <form action="submit_report.php" method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="mb-4"> 
                <label for="scam_type" class="form-label required">Scam Type *</label>
                <select name="scam_type" id="scam_type" class="form-select" required> <option value="">-- Select Type --</option> <?php $types = [ 'phishing' => 'Phishing', 'fake_online_store' => 'Fake Online Store', 'investment_scam' => 'Investment Scam', 'tech_support_scam' => 'Tech Support Scam', 'loan_grant_scam' => 'Loan or Grant Scam', 'romance_scam' => 'Romance Scam', 'job_scam' => 'Job Scam', 'fake_legal_threat' => 'Fake Legal Threat', 'delivery_shipping_scam' => 'Delivery/Shipping Scam', 'crypto_scam' => 'Crypto Scam', 'other' => 'Other' ]; $current_type = $form_data['scam_type'] ?? ''; foreach ($types as $value => $label) { $selected = ($value === $current_type) ? 'selected' : ''; echo "<option value=\"".htmlspecialchars($value)."\" $selected>" . htmlspecialchars($label) . "</option>"; } ?> </select>
            </div>

            <div class="mb-4"> 
                <label for="title" class="form-label required">Report Title *</label>
                <input type="text" name="title" id="title" class="form-control" maxlength="255" placeholder="e.g., Fake Amazon Delivery SMS, PayPal Invoice Phishing Email" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                <div class="form-text">Enter a short, descriptive title (e.g., the subject line, the company name used, the key topic). (Max 255 characters)</div>
            </div>
            
            <div class="mb-4"> 
                <label for="description" class="form-label required">Description *</label>
                <textarea name="description" id="description" class="form-control" rows="5" required><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                <div class="form-text">Please provide as much detail as possible (what happened, amounts involved, dates). <strong>Avoid sharing highly sensitive personal information like passwords or full credit card numbers.</strong></div>
            </div>

            <div class="mb-4"> 
                <label for="contact_method_type" class="form-label required">How were you contacted? *</label>
                <select name="contact_method_type" id="contact_method_type" class="form-select" required> <option value="">-- Select Method --</option> <?php $methods = [ 'email' => 'Email', 'phone' => 'Phone Call / Voicemail', 'website' => 'Website / Online Form', 'social_media' => 'Social Media (Facebook, Instagram, etc.)', 'sms_message' => 'SMS / Text Message', 'in_person' => 'In Person', 'mail' => 'Physical Mail', 'other' => 'Other' ]; $current_method = $form_data['contact_method_type'] ?? ''; foreach ($methods as $value => $label) { $selected = ($value === $current_method) ? 'selected' : ''; echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>"; } ?> </select>
            </div>

            <div class="mb-4"> 
                <label for="contact_method_details" class="form-label required">Contact Details (Email, Phone #, Website URL, Username, etc.) *</label>
                <input type="text" name="contact_method_details" id="contact_method_details" class="form-control" required placeholder="e.g., scammer@example.com, 1-800-555-SCAM, https://fake-site.com" value="<?php echo htmlspecialchars($form_data['contact_method_details'] ?? ''); ?>">
                <div class="form-text">Enter the specific detail associated with the contact method. This is crucial for others to identify the scam.</div>
            </div>

            <div class="mb-4"> 
                <label for="date_reported" class="form-label required">Approximate Date Scam Occurred *</label>
                <input type="date" name="date_reported" id="date_reported" class="form-control" required value="<?php echo htmlspecialchars($form_data['date_reported'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="mb-4"> 
                <label for="evidence" class="form-label">Upload Evidence (optional)</label>
                <input type="file" name="evidence" id="evidence" class="form-control">
                <div class="form-text">Screenshots, emails, chat logs, etc. (Max 5MB. Allowed types: jpg, png, gif, webp, pdf, txt)</div>
            </div>

            <div class="mb-4"> 
                <label for="user_email" class="form-label">Your Email (Optional)</label>
                <input type="email" name="user_email" id="user_email" class="form-control" placeholder="your.email@example.com" value="<?php echo htmlspecialchars($form_data['user_email'] ?? ''); ?>">
                <div class="form-text">If provided, we will send you a private link to edit or delete your report later. <strong>Your email will NOT be published.</strong></div>
            </div>

            <div class="mb-4"> 
                 <label class="form-label required">Verification *</label> 
                 <div class="g-recaptcha" data-sitekey="6LezL_0qAAAAADRTSeSS1i8oxGk9Zc0K19erL7HM"></div>
             </div>

            <button type="submit" class="btn btn-primary">Submit Report</button>
        </form>
        
    </div> <?php include 'includes/footer.php'; // This includes Klaro JS and Config ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script 
    type="text/plain" 
    data-klaro-type="text/javascript" 
    data-name="googleRecaptcha" 
    src="https://www.google.com/recaptcha/api.js" 
    async defer>
</script>
</body>
</html> <?php // END OF report.php ?>