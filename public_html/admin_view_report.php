<?php // START OF admin_view_report.php (Corrected & Complete)
session_start(); 

// === Admin Authentication Check ===
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php'); 
    exit(); 
}
// === End Authentication Check ===

// --- CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } 
    catch (Exception $e) { $_SESSION['csrf_token'] = 'fallback-token-error-'.time(); error_log("CSRF token gen failed: ".$e->getMessage()); }
}
$csrf_token = $_SESSION['csrf_token']; 
// --- End CSRF Token Generation ---

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// --- Get Report ID and Fetch Data ---
$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); 
$scam = null;
$page_error = ''; // Use empty string initially
$update_success_message = (isset($_GET['updated']) && $_GET['updated'] == '1') ? '✅ Report updated successfully.' : null;


if (!$report_id || $report_id <= 0) {
    $page_error = "Invalid Report ID provided."; http_response_code(400);
} else {
    try {
        // Select all columns needed for display and potential edits later
        $sql = "SELECT * FROM scams WHERE id = ?"; 
        $stmt = $pdo->prepare($sql); $stmt->execute([$report_id]); $scam = $stmt->fetch(PDO::FETCH_ASSOC); 
        if (!$scam) { $page_error = "No scam report found with ID: ".htmlspecialchars($report_id); http_response_code(404); }
    } catch (PDOException $e) {
        error_log("Admin View - Error fetching report ID $report_id: " . $e->getMessage());
        $page_error = "A database error occurred while retrieving the report."; http_response_code(500); $scam = null; 
    }
}
// --- End Data Fetching ---

// --- Prepare display variables ---
if ($scam) {
    $report_title = $scam['title'] ?? null; $scam_type_raw = $scam['scam_type'] ?? '';
    $scam_type_formatted = htmlspecialchars(ucfirst(str_replace('_', ' ', $scam_type_raw))); 
    $display_title = !empty($report_title) ? htmlspecialchars($report_title) : $scam_type_formatted; 
    $page_title = "Admin View: " . $display_title; 
    $current_status = htmlspecialchars(ucfirst($scam['status']));
    $status_badge_class = 'bg-secondary'; 
    if ($scam['status'] === 'pending') $status_badge_class = 'bg-warning text-dark';
    if ($scam['status'] === 'approved') $status_badge_class = 'bg-success';
    if ($scam['status'] === 'rejected') $status_badge_class = 'bg-danger';
} else {
    $page_title = "Admin View - Error";
    $display_title = "Error"; 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> | Scam Report Hub Admin</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* Navbar styles */
.navbar-brand img { height: 55px; }
.navbar-brand .text-logo { display: none !important; }
/* Other admin specific styles */
.detail-label { font-weight: bold; }
pre { background-color: #f8f9fa; padding: 1em; border-radius: 0.25rem; white-space: pre-wrap; word-wrap: break-word; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5 mb-5">

<?php // --- Display Feedback/Error Messages ---
     // Display admin action feedback from session first
     if (isset($_SESSION['admin_feedback'])) { 
         $feedback = $_SESSION['admin_feedback'];
         echo '<div class="alert alert-'.htmlspecialchars($feedback['type']).' alert-dismissible fade show" role="alert">';
         echo htmlspecialchars($feedback['message']);
         echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
         echo '</div>';
         unset($_SESSION['admin_feedback']); 
     } 
     // Then display update success message from GET param
     elseif ($update_success_message) { 
          echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
          echo $update_success_message;
          echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
     } 
     // Then display page load errors (invalid ID, not found, DB error)
     elseif (!empty($page_error)) { 
         echo '<h1 class="mb-4">Error</h1>';
         echo '<div class="alert alert-danger">'.htmlspecialchars($page_error).'</div>';
         echo '<a href="admin_moderate.php" class="btn btn-secondary">Back to Moderation List</a>';
         // Stop further output if major page load error
          include 'includes/footer.php'; echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>'; exit();
     }
    ?>

<?php if ($scam): // Only display report details if $scam was loaded successfully ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"> 
         <h1 class="mb-0 me-3"><?php echo $display_title; ?></h1> 
         <div> <a href="admin_edit_report.php?id=<?php echo htmlspecialchars($scam['id']); ?>" class="btn btn-sm btn-outline-primary me-2">
                  <i class="bi bi-pencil-square"></i> Edit Report
              </a>
              <a href="admin_moderate.php" class="btn btn-sm btn-outline-secondary">Back to List</a>
         </div>
    </div>

    <p class="lead text-muted mb-2">
        <i class="bi bi-tag me-1"></i> Category: <?php echo $scam_type_formatted; ?>
    </p>
     <p class="mb-4">
         <strong>Status:</strong> <span class="badge <?php echo $status_badge_class; ?>"><?php echo $current_status; ?></span>
     </p>
     <hr>

     <div class="row">
          <div class="col-md-8"> <h4 class="mb-3">Report Details</h4>
              <p><span class="detail-label">Contact Method:</span> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $scam['contact_method_type']))); ?></p>
              <p><span class="detail-label">Contact Detail Used:</span> <?php echo htmlspecialchars($scam['contact_method_details']); ?></p>
              <p><span class="detail-label">Approx. Date Occurred:</span> <?php echo htmlspecialchars($scam['date_reported']); ?></p>

              <h4 class="mt-4 mb-3">Description</h4>
              <pre><?php echo nl2br(htmlspecialchars($scam['description'])); ?></pre>

              <?php if (!empty($scam['evidence_path'])): ?>
              <div class="mt-4">
                   <h4 class="mb-3">Evidence</h4>
                   <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#evidenceModal">
                        <i class="bi bi-paperclip"></i> View Evidence File
                   </button>
              </div>
              <?php endif; ?>
          </div><div class="col-md-4"> <div class="card mb-4"> 
                   <div class="card-header">Admin Information</div> 
                   <div class="card-body"> 
                        <p><span class="detail-label">Report ID:</span> <?php echo htmlspecialchars($scam['id']); ?></p> 
                        <p><span class="detail-label">Submitted:</span> <?php echo htmlspecialchars($scam['created_at']); ?></p> 
                        <p><span class="detail-label">Last Updated:</span> <?php echo htmlspecialchars($scam['updated_at']); ?></p> 
                        <p><span class="detail-label">Submitter IP:</span> <?php echo htmlspecialchars($scam['ip_address'] ?? 'N/A'); ?></p> 
                        <p><span class="detail-label">Submitter Email:</span> <?php echo htmlspecialchars($scam['user_email'] ?? 'Not Provided'); ?></p> 
                        <p><span class="detail-label">Unique Slug:</span> <?php echo htmlspecialchars($scam['slug']); ?></p> 
                        <p><span class="detail-label">Edit Token:</span> <small><?php echo htmlspecialchars($scam['edit_token']); ?></small></p> 
                   </div> 
              </div>
              <?php if ($scam['status'] === 'pending'): ?> 
              <div class="card mb-4"> 
                   <div class="card-header">Moderation Actions</div> 
                   <div class="card-body text-center"> 
                       <form action="admin_action.php" method="POST" class="d-inline-block me-2"> <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($scam['id']); ?>"> <input type="hidden" name="action" value="approve"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-success">Approve Report</button> </form> 
                       <form action="admin_action.php" method="POST" class="d-inline-block"> <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($scam['id']); ?>"> <input type="hidden" name="action" value="reject"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-danger">Reject Report</button> </form> 
                   </div> 
              </div> 
              <?php endif; ?>
              <div class="card border-danger"> 
                   <div class="card-header bg-danger text-white">Delete Report</div> 
                   <div class="card-body text-center"> 
                       <p class="text-danger"><small>This action is permanent.</small></p> 
                       <form action="admin_action.php" method="POST" onsubmit="return confirm('Are you absolutely sure you want to permanently delete this scam report (ID: <?php echo $scam['id']; ?>)? This action cannot be undone.');"> 
                           <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($scam['id']); ?>"> 
                           <input type="hidden" name="action" value="delete"> 
                           <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> 
                           <button type="submit" class="btn btn-danger w-100"> <i class="bi bi-trash-fill"></i> Delete Permanently </button> 
                       </form> 
                   </div> 
              </div>
          </div></div><?php // Evidence Modal ?>
     <?php if (!empty($scam['evidence_path'])): ?>
     <div class="modal fade" id="evidenceModal" tabindex="-1" aria-labelledby="evidenceModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-lg modal-dialog-centered"> 
            <div class="modal-content"> 
                <div class="modal-header"> 
                    <h5 class="modal-title" id="evidenceModalLabel">Evidence File</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
                </div> 
                <div class="modal-body text-center"> 
                    <?php $evidence_web_path = '/' . ltrim($scam['evidence_path'], '/'); $safe_web_path = htmlspecialchars($evidence_web_path); $file_ext = strtolower(pathinfo($scam['evidence_path'], PATHINFO_EXTENSION)); if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { echo "<img src='$safe_web_path' alt='Evidence Image' class='img-fluid rounded'>"; } elseif ($file_ext === 'pdf') { echo "<p><a href='$safe_web_path' target='_blank' class='btn btn-primary mb-3'>Open PDF in new tab</a></p><iframe src='$safe_web_path' width='100%' height='600px' style='border: 1px solid #ccc;'></iframe>"; } else { echo "<p>File type ($file_ext) not supported.</p><p><a href='$safe_web_path' target='_blank' class='btn btn-primary'>Download File</a></p>"; } ?> 
                </div> 
                <div class="modal-footer"> 
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> 
                </div> 
            </div> 
        </div>
     </div>
     <?php endif; ?>

<?php endif; // End if($scam) ?>

</div><?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> <?php // END OF admin_view_report.php ?>