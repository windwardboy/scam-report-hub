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

$slug = $_GET['slug'] ?? '';
$scam = null; 
$comments = []; 
$error_message = null; 
$status_message = null; 
$status_message_type = 'info'; 
$show_full_report = false; 
$page_title = "Scam Report | Scam Report Hub"; 
$display_title = "Scam Report"; 

if (empty($slug)) {
    $error_message = "No report identifier provided."; http_response_code(400); 
} else {
    try {
        // Fetch necessary columns including timestamps
        $sql = "SELECT id, slug, scam_type, title, description, contact_method_type, contact_method_details, date_reported, evidence_path, status, created_at, updated_at FROM scams WHERE slug = ?"; 
        $stmt = $pdo->prepare($sql); $stmt->execute([$slug]); $scam = $stmt->fetch(PDO::FETCH_ASSOC); 

        if (!$scam) {
            $error_message = "Scam report not found."; http_response_code(404);
        } else {
            // Prepare display variables
            $report_title = $scam['title'] ?? null; $scam_type_raw = $scam['scam_type'] ?? ''; $scam_type_formatted = htmlspecialchars(ucfirst(str_replace('_', ' ', $scam_type_raw))); $display_title = !empty($report_title) ? htmlspecialchars($report_title) : $scam_type_formatted; 

            if ($scam['status'] === 'approved') {
                // Prepare data for approved display AND JSON-LD
                $show_full_report = true; 
                $page_title = $display_title . " | Scam Report Hub";
                $meta_description_content = !empty($report_title) ? $report_title : "Scam Report: " . $scam_type_formatted;
                $meta_description = htmlspecialchars(substr($meta_description_content . ". " . ($scam['description'] ?? ''), 0, 160)); 
                $canonical_url = "https://www.scamreporthub.com/scam/" . urlencode($slug); 
                $iso_date_created = $scam['created_at'] ? date('c', strtotime($scam['created_at'])) : null; 
                $iso_date_modified = $scam['updated_at'] ? date('c', strtotime($scam['updated_at'])) : $iso_date_created; 
                $json_ld_description = $scam['description'] ?? ''; 
                $json_ld_title = !empty($report_title) ? htmlspecialchars($report_title) : $scam_type_formatted;
                $base_keywords = array_filter([$scam['scam_type'], $scam['contact_method_type']]); 
                $detail_string = $scam['contact_method_details'] ?? ''; 
                $detail_keywords = array_filter(array_map('trim', preg_split('/[\s,]+/', $detail_string))); 
                $keywords_array = array_filter(array_merge($base_keywords, $detail_keywords)); 

                // Fetch Approved Comments
                try { 
                    $sql_comments = "SELECT name, comment, created_at FROM comments WHERE scam_id = ? AND status = 'approved' ORDER BY created_at DESC"; 
                    $stmt_comments = $pdo->prepare($sql_comments); 
                    $stmt_comments->execute([$scam['id']]); 
                    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC); 
                } catch (PDOException $e) { 
                    error_log("Error fetching comments for scam_id " . $scam['id'] . ": " . $e->getMessage()); 
                }

            } elseif ($scam['status'] === 'pending') {
                $status_message = "This report (\"" . htmlspecialchars($scam['title'] ?: 'Untitled') . "\") is currently awaiting moderator review..."; $status_message_type = 'info'; http_response_code(200); $page_title = "Report Pending Review | Scam Report Hub"; $display_title = htmlspecialchars($scam['title'] ?: 'Report Pending'); 
            } elseif ($scam['status'] === 'rejected') {
                $status_message = "This report (\"" . htmlspecialchars($scam['title'] ?: 'Untitled') . "\") was reviewed and rejected by moderators..."; $status_message_type = 'warning'; http_response_code(200); $page_title = "Report Rejected | Scam Report Hub"; $display_title = htmlspecialchars($scam['title'] ?: 'Report Rejected'); 
            } else { 
                $error_message = "This report has an unknown status..."; http_response_code(500); $page_title = "Report Status Error | Scam Report Hub"; $display_title = "Error"; 
            }
        }
    } catch (PDOException $e) { 
         error_log("Public View - Error fetching report slug $slug: " . $e->getMessage());
         $error_message = "A database error occurred..."; http_response_code(500); $page_title = "Database Error | Scam Report Hub"; $display_title = "Error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <?php if ($scam && $scam['status'] === 'approved'): ?>
        <meta name="description" content="<?php echo $meta_description; ?>"> 
        <meta name="keywords" content="scam report, online scams, fraud report, <?php echo htmlspecialchars($scam['scam_type']); ?>, <?php echo htmlspecialchars($scam['contact_method_details']); ?>, <?php echo htmlspecialchars($scam['title'] ?? ''); ?>"> 
        <meta name="author" content="Scam Report Hub"> 
        <link rel="canonical" href="<?php echo $canonical_url; ?>"> 
        <meta property="og:title" content="<?php echo $display_title; ?>"> 
        <meta property="og:description" content="<?php echo $meta_description; ?>"> 
        <meta property="og:type" content="article"> 
        <meta property="og:url" content="<?php echo $canonical_url; ?>">
        <meta property="og:image" content="https://www.scamreporthub.com/images/og-image.jpg"> 
        <meta property="og:site_name" content="Scam Report Hub"> 
        <meta name="twitter:card" content="summary_large_image"> 
        <meta name="twitter:title" content="<?php echo $display_title; ?>"> 
        <meta name="twitter:description" content="<?php echo $meta_description; ?>"> 
        <meta name="twitter:image" content="https://www.scamreporthub.com/images/og-image.jpg">
        <script type="application/ld+json"> { "@context": "https://schema.org", "@type": "Report", "name": <?php echo json_encode($json_ld_title); ?>, "url": <?php echo json_encode($canonical_url); ?>, <?php if ($iso_date_created): ?>"dateCreated": <?php echo json_encode($iso_date_created); ?>,<?php endif; ?> <?php if ($iso_date_modified): ?>"dateModified": <?php echo json_encode($iso_date_modified); ?>,<?php endif; ?> "description": <?php echo json_encode($json_ld_description); ?>, <?php if (!empty($keywords_array)): ?>"keywords": <?php echo json_encode(array_values($keywords_array)); ?>,<?php endif; ?> "author": { "@type": "Organization", "name": "Scam Report Hub", "url": "https://www.scamreporthub.com/" }, "publisher": { "@type": "Organization", "name": "Scam Report Hub", "url": "https://www.scamreporthub.com/", "logo": { "@type": "ImageObject", "url": "https://www.scamreporthub.com/images/logo.png" } } } </script>
    <?php else: ?>
         <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    
    <link rel="icon" type="image/png" href="https://www.scamreporthub.com/favicon.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> 
    
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/klaro@0.7.22/dist/klaro.min.css" />
    <style> /* Styles */ @media (max-width: 767.98px) { body{font-size:1.1rem;} h1{font-size:2rem;} .btn{padding:.5rem 1rem;font-size:1rem;} } .navbar-brand img{height:55px;} .navbar-brand .text-logo{display:none !important;} .scam-category-subheader{font-size:1.1rem;font-weight:500;} pre{background-color:#f8f9fa;padding:1em;border-radius:.25rem;white-space:pre-wrap;word-wrap:break-word;} .card-body .scam-category{margin-bottom:.75rem;} </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

<div class="container mt-5">

    <?php // --- Display Area --- ?>

    <?php if ($error_message): ?>
        <h1 class="mb-4"><?php echo $display_title ?: 'Error'; ?></h1> <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div> <a href="/browse.php" class="btn btn-primary">Browse Scams</a>
    <?php elseif ($status_message): ?>
         <h1 class="mb-4"><?php echo $display_title; ?></h1> <div class="alert alert-<?php echo $status_message_type; ?>" role="alert"> <h4 class="alert-heading"><?php echo ($scam['status'] === 'pending') ? 'Report Pending Review' : 'Report Rejected'; ?></h4> <p><?php echo $status_message; ?></p> </div> <a href="/browse.php" class="btn btn-primary mt-3">Browse Other Scams</a>
    <?php elseif ($scam && isset($show_full_report) && $show_full_report === true): ?>
    
        <?php // Success messages 
         if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?> <div class="alert alert-success alert-dismissible fade show" role="alert">✅ Report updated.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php elseif (isset($_GET['submitted']) && $_GET['submitted'] == '1'): ?> <div class="alert alert-success alert-dismissible fade show" role="alert">🎉 Report approved and published.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php elseif (isset($_SESSION['comment_feedback'])): $comment_feedback = $_SESSION['comment_feedback']; echo '<div class="alert alert-'.htmlspecialchars($comment_feedback['type']).' alert-dismissible fade show" role="alert">'; echo htmlspecialchars($comment_feedback['message']); echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['comment_feedback']); 
        elseif (isset($_SESSION['flag_feedback'])): $flag_feedback = $_SESSION['flag_feedback']; echo '<div class="alert alert-'.htmlspecialchars($flag_feedback['type']).' alert-dismissible fade show mt-3" role="alert">'; echo htmlspecialchars($flag_feedback['message']); echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['flag_feedback']); 
        endif; ?>


        <h1 class="mb-3"><?php echo $display_title; ?></h1> 
        <p class="lead text-muted scam-category-subheader mb-4"> <i class="bi bi-tag me-1"></i> Category: <?php echo $scam_type_formatted; ?> </p>
        <div class="row"> <div class="col-md-8"> <div class="mb-3"><strong>Contact Method:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $scam['contact_method_type']))); ?> – <?php echo htmlspecialchars($scam['contact_method_details']); ?></div> 
             <div class="mb-3"><strong>Approx Date:</strong> <?php echo !empty($scam['date_reported']) ? htmlspecialchars($scam['date_reported']) : 'N/A'; ?> (Submitted: <?php echo !empty($scam['created_at']) ? date('Y-m-d', strtotime($scam['created_at'])) : 'N/A'; ?>)</div> 
             <?php if (!empty($scam['evidence_path'])): ?> <div class="mb-4"> <strong>Evidence:</strong> <button class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#evidenceModal">View Evidence</button> </div> <?php endif; ?> 
             <div class="mb-3"> <h4 class="mb-3">Description</h4> <pre><?php echo nl2br(htmlspecialchars($scam['description'])); ?></pre> </div>
             <div class="mt-4 mb-4"> <small><a href="#" data-bs-toggle="modal" data-bs-target="#flagReportModal"><i class="bi bi-flag-fill"></i> Flag this report for review</a></small> </div>
        </div><div class="col-md-4"> <div class="p-3 mb-3 bg-light rounded"> <h5 class="fst-italic">Report Details</h5> <ul class="list-unstyled mb-0"> <li>ID: <?php echo htmlspecialchars($scam['id']); ?></li> <li>Status: <span class="badge bg-success"><?php echo htmlspecialchars(ucfirst($scam['status'])); ?></span></li> <li>Submitted: <?php echo !empty($scam['created_at']) ? date('Y-m-d H:i', strtotime($scam['created_at'])) : 'N/A'; ?></li> <li>Updated: <?php echo !empty($scam['updated_at']) ? date('Y-m-d H:i', strtotime($scam['updated_at'])) : 'N/A'; ?></li> </ul> </div>
        </div></div><?php // Evidence Modal ?>
        <?php if (!empty($scam['evidence_path'])): ?>
        <div class="modal fade" id="evidenceModal" tabindex="-1" aria-labelledby="evidenceModalLabel" aria-hidden="true"> 
           <div class="modal-dialog modal-lg modal-dialog-centered"> <div class="modal-content"> <div class="modal-header"> <h5 class="modal-title" id="evidenceModalLabel">Evidence File</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div> <div class="modal-body text-center"> <?php $evidence_web_path = '/' . ltrim($scam['evidence_path'], '/'); $safe_web_path = htmlspecialchars($evidence_web_path); $file_ext = strtolower(pathinfo($scam['evidence_path'], PATHINFO_EXTENSION)); if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { echo "<img src='$safe_web_path' alt='Evidence Image' class='img-fluid rounded'>"; } elseif ($file_ext === 'pdf') { echo "<p><a href='$safe_web_path' target='_blank' class='btn btn-primary mb-3'>Open PDF in new tab</a></p><iframe src='$safe_web_path' width='100%' height='600px' style='border: 1px solid #ccc;'></iframe>"; } else { echo "<p>File type ($file_ext) not supported for preview.</p><p><a href='$safe_web_path' target='_blank' class='btn btn-primary'>Download Evidence File</a></p>"; } ?> </div> <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> </div> </div> </div>
        </div>
        <?php endif; ?>

        <hr class="my-4"> 
        <section id="comments-section" class="mt-4">
            <h3 class="mb-4">Comments / Shared Experiences (<span id="comment-count"><?php echo count($comments); ?></span>)</h3>
            <div id="existing-comments" class="mb-4"> 
                <?php if (empty($comments)): ?> <p><em>No comments or shared experiences yet. Be the first!</em></p>
                <?php else: ?> <?php foreach ($comments as $comment): ?> <div class="card mb-3 shadow-sm"> <div class="card-body"> <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p> <p class="card-text mb-0"> <small class="text-muted"> Posted by <strong><?php echo !empty($comment['name']) ? htmlspecialchars($comment['name']) : 'Anonymous'; ?></strong> on <?php echo date('F j, Y \a\t g:ia', strtotime($comment['created_at'])); ?> </small> </p> </div> </div> <?php endforeach; ?> <?php endif; ?>
            </div> <hr> 
            <div class="card mb-4"> 
                <div class="card-header">Leave a Comment</div>
                <div class="card-body">
                    <?php if (isset($_SESSION['comment_feedback'])) { $comment_feedback = $_SESSION['comment_feedback']; echo '<div class="alert alert-'.htmlspecialchars($comment_feedback['type']).' alert-dismissible fade show" role="alert">'; echo htmlspecialchars($comment_feedback['message']); echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['comment_feedback']); } $comment_form_data = $_SESSION['comment_form_data'] ?? []; unset($_SESSION['comment_form_data']); ?>
                    <form action="/submit_comment.php" method="POST"> 
                        <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($scam['id']); ?>"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="mb-3"> <label for="comment_name" class="form-label">Your Name (Optional)</label> <input type="text" class="form-control" id="comment_name" name="name" maxlength="100" value="<?php echo htmlspecialchars($comment_form_data['name'] ?? ''); ?>"> <div class="form-text">Leave blank to post anonymously.</div> </div>
                        <div class="mb-3"> <label for="comment_text" class="form-label">Comment / Your Experience *</label> <textarea class="form-control" id="comment_text" name="comment" rows="4" required><?php echo htmlspecialchars($comment_form_data['comment'] ?? ''); ?></textarea> <div class="form-text">Share details or confirm if this happened to you.</div> </div>
                        <div class="mb-3"> <label class="form-label">Verification *</label> <div class="g-recaptcha" data-sitekey="6LezL_0qAAAAADRTSeSS1i8oxGk9Zc0K19erL7HM"></div> </div>
                        <button type="submit" class="btn btn-primary">Submit Comment</button>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; // End main display blocks ('approved' status) ?>

</div><?php if ($scam && $scam['status'] === 'approved'): ?>
<div class="modal fade" id="flagReportModal" tabindex="-1" aria-labelledby="flagReportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"> <div class="modal-content"> <form action="/submit_flag.php" method="POST"> <div class="modal-header"> <h5 class="modal-title" id="flagReportModalLabel">Report an Issue</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div> <div class="modal-body"> <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($scam['id']); ?>"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <div class="mb-3"> <label for="flag_reason" class="form-label">Reason for flagging *</label> <select class="form-select" name="reason" id="flag_reason" required> <option value="" selected disabled>-- Select a Reason --</option> <option value="inaccurate">Inaccurate Information</option> <option value="private_info">Contains Private/Sensitive Information</option> <option value="not_scam">Not a Scam / Misleading</option> <option value="spam_abuse">Spam / Abusive Content</option> <option value="other">Other (Please specify below)</option> </select> </div> <div class="mb-3"> <label for="flag_details" class="form-label">Details (Optional)</label> <textarea class="form-control" id="flag_details" name="details" rows="3" placeholder="Provide more details..."></textarea> </div> <div class="mb-3"> <label class="form-label">Verification *</label> <div class="g-recaptcha" data-sitekey="6LezL_0qAAAAADRTSeSS1i8oxGk9Zc0K19erL7HM"></div> </div> </div> <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> <button type="submit" class="btn btn-warning">Submit Flag</button> </div> </form> </div> </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; // This now includes Klaro JS and Config ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script 
    type="text/plain" 
    data-klaro-type="text/javascript" 
    data-name="googleRecaptcha" 
    src="https://www.google.com/recaptcha/api.js" 
    async defer>
</script>
</body>
</html>