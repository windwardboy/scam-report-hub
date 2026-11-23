<?php
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

include 'includes/db.php'; 

$pending_reports = []; 
$draft_reports = [];
$pending_comments = []; 
$pending_flags = []; // **** ADDED: Array for pending flags ****
$db_error = null;    

// --- Fetch Pending Reports ---
try {
    $sql_reports = "SELECT id, created_at, scam_type, title, contact_method_type, contact_method_details, description, status, slug FROM scams WHERE status = :status ORDER BY created_at DESC"; 
    $stmt_reports = $pdo->prepare($sql_reports);
    $stmt_reports->execute(['status' => 'pending']); 
    $pending_reports = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching pending reports: " . $e->getMessage());
    $db_error = "A database error occurred while retrieving reports.";
}

try {
    $sql_drafts = "SELECT id, created_at, updated_at, scam_type, title, contact_method_details, description, status FROM scams WHERE status = :status ORDER BY updated_at DESC";
    $stmt_drafts = $pdo->prepare($sql_drafts);
    $stmt_drafts->execute(['status' => 'draft']);
    $draft_reports = $stmt_drafts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching draft reports: " . $e->getMessage());
    $db_error = ($db_error ? $db_error."<br>" : "") . "Could not load draft reports.";
}

// --- Fetch Pending Comments ---
try {
    $sql_comments = "SELECT c.id, c.scam_id, c.name, c.comment, c.created_at, c.ip_address, s.title AS scam_title, s.slug AS scam_slug FROM comments c LEFT JOIN scams s ON c.scam_id = s.id WHERE c.status = 'pending' ORDER BY c.created_at ASC"; 
    $stmt_comments = $pdo->query($sql_comments); 
    $pending_comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching pending comments: " . $e->getMessage());
    $db_error = ($db_error ? $db_error."<br>" : "") . "Could not load pending comments.";
}

// --- **** ADDED: Fetch Pending Flags **** ---
try {
    $sql_flags = "SELECT f.id, f.scam_id, f.reason, f.details, f.reporter_ip, f.created_at,
                        s.title AS scam_title, s.slug AS scam_slug 
                 FROM flags f
                 LEFT JOIN scams s ON f.scam_id = s.id 
                 WHERE f.status = 'new' 
                 ORDER BY f.created_at ASC"; // Show oldest flags first
    $stmt_flags = $pdo->query($sql_flags); 
    $pending_flags = $stmt_flags->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching pending flags: " . $e->getMessage());
    $db_error = ($db_error ? $db_error."<br>" : "") . "Could not load pending flags.";
}
// --- **** End Fetch Pending Flags **** ---


// Helper function
function truncate_text($text, $length = 100, $suffix = '...') {
    if (is_null($text)) return ''; 
    $text = (string)$text;
    if (mb_strlen($text, 'UTF-8') > $length) { return htmlspecialchars(mb_substr($text, 0, $length, 'UTF-8'), ENT_QUOTES, 'UTF-8') . $suffix; }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); 
}

// Function to format flag reasons nicely
function format_flag_reason($reason) {
    $reason = str_replace('_', ' ', $reason);
    return ucwords($reason);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Content - Scam Report Hub Admin</title> 
    <meta name="robots" content="noindex, nofollow"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> 
    <style>
       .navbar-brand img { height: 55px; } .navbar-brand .text-logo { display: none !important; }
       .table td, .table th { vertical-align: middle; }
       .title-column { min-width: 150px; font-weight: 500; } 
       .description-column { max-width: 250px; } 
       .comment-column { max-width: 300px; white-space: normal !important; } 
       .details-column { max-width: 250px; white-space: normal !important; } 
       .nav-tabs .nav-link .badge { margin-left: 5px; } 
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Moderate Content</h1>

    <?php if ($db_error): ?> <div class="alert alert-danger"> <?php echo $db_error; ?> </div> <?php endif; ?>
    <?php if (isset($_SESSION['admin_feedback'])) { $feedback = $_SESSION['admin_feedback']; echo '<div class="alert alert-'.htmlspecialchars($feedback['type']).' alert-dismissible fade show" role="alert">'; echo htmlspecialchars($feedback['message']); echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'; echo '</div>'; unset($_SESSION['admin_feedback']); } ?>

    <ul class="nav nav-tabs mb-3" id="moderationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports-pane" type="button" role="tab" aria-controls="reports-pane" aria-selected="true">
                Pending Reports <span class="badge bg-secondary"><?php echo count($pending_reports); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments-pane" type="button" role="tab" aria-controls="comments-pane" aria-selected="false">
                Pending Comments <span class="badge bg-secondary"><?php echo count($pending_comments); ?></span>
            </button>
        </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="flags-tab" data-bs-toggle="tab" data-bs-target="#flags-pane" type="button" role="tab" aria-controls="flags-pane" aria-selected="false">
                Pending Flags <span class="badge bg-warning text-dark"><?php echo count($pending_flags); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts-pane" type="button" role="tab" aria-controls="drafts-pane" aria-selected="false">
                Drafts <span class="badge bg-secondary"><?php echo count($draft_reports); ?></span>
            </button>
        </li>
         </ul>
    <div class="tab-content" id="moderationTabsContent">

        <div class="tab-pane fade show active" id="reports-pane" role="tabpanel" aria-labelledby="reports-tab" tabindex="0">
            <h2 class="h4 mb-3">Pending Scam Reports</h2>
            <?php if (!$db_error && empty($pending_reports)): ?> <div class="alert alert-info" role="alert">No pending reports found.</div>
            <?php elseif (!empty($pending_reports)): ?>
                <div class="table-responsive"> <table class="table table-striped table-hover table-bordered"> <thead class="table-light"> <tr><th>ID</th><th>Submitted</th><th>Type</th><th class="title-column">Title</th><th>Contact Details</th><th class="description-column">Description</th><th>Status</th><th>Actions</th></tr></thead> <tbody>
                <?php foreach ($pending_reports as $report): ?> <tr> <td><?php echo htmlspecialchars($report['id']); ?></td> <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($report['created_at']))); ?></td> <td><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($report['scam_type']))); ?></td> <td class="title-column"><?php echo !empty($report['title']) ? htmlspecialchars($report['title']) : '<span class="text-muted fst-italic">(No Title)</span>'; ?></td> <td><?php echo htmlspecialchars($report['contact_method_details']); ?></td> <td class="description-column"><?php echo truncate_text($report['description'], 80); ?></td> <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars(ucfirst($report['status'])); ?></span></td> <td class="text-nowrap"> <form action="admin_action.php" method="POST" style="display: inline-block;"> <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($report['id']); ?>"> <input type="hidden" name="action" value="approve"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button> </form> <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;"> <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($report['id']); ?>"> <input type="hidden" name="action" value="reject"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="bi bi-x-lg"></i></button> </form> <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;" onsubmit="return confirm('Send this report to draft? It will be hidden from the public site until you approve it again.');"> <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($report['id']); ?>"> <input type="hidden" name="action" value="draft"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-sm btn-outline-secondary" title="Send to draft"><i class="bi bi-file-earmark"></i></button> </form> <a href="admin_edit_report.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn btn-sm btn-primary" style="margin-left: 2px;" title="Edit Report"><i class="bi bi-pencil-square"></i></a> <a href="admin_view_report.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn btn-sm btn-info" style="margin-left: 2px;" title="View Details"><i class="bi bi-eye-fill"></i></a> </td> </tr> <?php endforeach; ?> </tbody> </table> </div>
            <?php endif; ?>
        </div> <div class="tab-pane fade" id="comments-pane" role="tabpanel" aria-labelledby="comments-tab" tabindex="0">
             <h2 class="h4 mb-3">Pending Comments</h2>
             <?php if (!$db_error && empty($pending_comments)): ?> <div class="alert alert-info" role="alert">No pending comments found.</div>
             <?php elseif (!empty($pending_comments)): ?>
                 <div class="table-responsive"> <table class="table table-striped table-hover table-bordered"> <thead class="table-light"> <tr><th>ID</th><th>On Report</th><th>Name</th><th class="comment-column">Comment</th><th>Submitted</th><th>IP Address</th><th>Actions</th></tr></thead> <tbody>
                 <?php foreach ($pending_comments as $comment): ?> <tr> <td><?php echo htmlspecialchars($comment['id']); ?></td> <td> <a href="admin_view_report.php?id=<?php echo htmlspecialchars($comment['scam_id']); ?>" title="<?php echo htmlspecialchars($comment['scam_title'] ?? 'View Report'); ?>"> <?php echo htmlspecialchars(truncate_text($comment['scam_title'] ?? 'Report #'.$comment['scam_id'], 30)); ?></a> </td> <td><?php echo !empty($comment['name']) ? htmlspecialchars($comment['name']) : '<span class="text-muted fst-italic">Anonymous</span>'; ?></td> <td class="comment-column"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></td> <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($comment['created_at']))); ?></td> <td><?php echo htmlspecialchars($comment['ip_address'] ?? 'N/A'); ?></td> <td class="text-nowrap"> <form action="admin_action.php" method="POST" style="display: inline-block;"> <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars($comment['id']); ?>"> <input type="hidden" name="action" value="approve_comment"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-sm btn-success" title="Approve Comment"><i class="bi bi-check-lg"></i></button> </form> <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;"> <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars($comment['id']); ?>"> <input type="hidden" name="action" value="reject_comment"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-sm btn-warning text-dark" title="Reject Comment"><i class="bi bi-hand-thumbs-down-fill"></i></button> </form> <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;" onsubmit="return confirm('Are you sure you want to PERMANENTLY DELETE this comment?');"> <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars($comment['id']); ?>"> <input type="hidden" name="action" value="delete_comment"> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"> <button type="submit" class="btn btn-sm btn-danger" title="Delete Comment"><i class="bi bi-trash-fill"></i></button> </form> </td> </tr> <?php endforeach; ?> </tbody> </table> </div>
             <?php endif; ?>
        </div> <div class="tab-pane fade" id="flags-pane" role="tabpanel" aria-labelledby="flags-tab" tabindex="0">
             <h2 class="h4 mb-3">Pending Flags</h2>
             <?php if (!$db_error && empty($pending_flags)): ?>
                 <div class="alert alert-info" role="alert">No pending flags found.</div>
             <?php elseif (!empty($pending_flags)): ?>
                 <div class="table-responsive">
                     <table class="table table-striped table-hover table-bordered">
                         <thead class="table-light">
                             <tr>
                                 <th>ID</th>
                                 <th>Flagged Report</th>
                                 <th>Reason</th>
                                 <th class="details-column">Details</th>
                                 <th>Flagged On</th>
                                 <th>Flagger IP</th>
                                 <th>Actions</th>
                             </tr>
                         </thead>
                         <tbody>
                             <?php foreach ($pending_flags as $flag): ?>
                             <tr>
                                 <td><?php echo htmlspecialchars($flag['id']); ?></td>
                                 <td> <a href="admin_view_report.php?id=<?php echo htmlspecialchars($flag['scam_id']); ?>" title="<?php echo htmlspecialchars($flag['scam_title'] ?? 'View Report'); ?>">
                                          <?php echo htmlspecialchars(truncate_text($flag['scam_title'] ?? 'Report #'.$flag['scam_id'], 30)); ?>
                                      </a>
                                 </td>
                                 <td><?php echo htmlspecialchars(format_flag_reason($flag['reason'])); ?></td>
                                 <td class="details-column"><?php echo truncate_text($flag['details'], 100); // Show excerpt of details ?></td>
                                 <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($flag['created_at']))); ?></td>
                                 <td><?php echo htmlspecialchars($flag['reporter_ip'] ?? 'N/A'); ?></td>
                                 <td class="text-nowrap">
                                      <a href="admin_view_report.php?id=<?php echo htmlspecialchars($flag['scam_id']); ?>" class="btn btn-sm btn-info" title="View Flagged Report"><i class="bi bi-eye-fill"></i></a>
                                      
                                      <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;">
                                          <input type="hidden" name="flag_id" value="<?php echo htmlspecialchars($flag['id']); ?>">
                                          <input type="hidden" name="action" value="review_flag">
                                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                          <button type="submit" class="btn btn-sm btn-secondary" title="Mark as Reviewed"><i class="bi bi-bookmark-check-fill"></i></button>
                                      </form>
                                     
                                      <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;" 
                                            onsubmit="return confirm('Are you sure you want to DELETE this flag (the report itself will NOT be deleted)?');">
                                          <input type="hidden" name="flag_id" value="<?php echo htmlspecialchars($flag['id']); ?>">
                                          <input type="hidden" name="action" value="delete_flag">
                                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                          <button type="submit" class="btn btn-sm btn-danger" title="Delete Flag"><i class="bi bi-trash-fill"></i></button>
                                      </form>
                                 </td>
                             </tr>
                             <?php endforeach; ?>
                         </tbody>
                     </table>
                 </div>
             <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="drafts-pane" role="tabpanel" aria-labelledby="drafts-tab" tabindex="0">
            <h2 class="h4 mb-3">Draft Reports</h2>
            <p class="text-muted">Drafts are hidden from the public site. Approve a draft to publish it, or return it to the pending queue.</p>
            <?php if (!$db_error && empty($draft_reports)): ?>
                <div class="alert alert-info" role="alert">No draft reports found.</div>
            <?php elseif (!empty($draft_reports)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Updated</th>
                                <th>Type</th>
                                <th class="title-column">Title</th>
                                <th>Contact Details</th>
                                <th class="description-column">Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($draft_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['id']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($report['updated_at']))); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($report['scam_type']))); ?></td>
                                <td class="title-column"><?php echo !empty($report['title']) ? htmlspecialchars($report['title']) : '<span class="text-muted fst-italic">(No Title)</span>'; ?></td>
                                <td><?php echo htmlspecialchars($report['contact_method_details']); ?></td>
                                <td class="description-column"><?php echo truncate_text($report['description'], 80); ?></td>
                                <td><span class="badge bg-secondary">Draft</span></td>
                                <td class="text-nowrap">
                                    <form action="admin_action.php" method="POST" style="display: inline-block;">
                                        <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form action="admin_action.php" method="POST" style="display: inline-block; margin-left: 2px;">
                                        <input type="hidden" name="scam_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                                        <input type="hidden" name="action" value="pending">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" class="btn btn-sm btn-warning text-dark" title="Return to pending"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                    <a href="admin_edit_report.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn btn-sm btn-primary" style="margin-left: 2px;" title="Edit Report"><i class="bi bi-pencil-square"></i></a>
                                    <a href="admin_view_report.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn btn-sm btn-info" style="margin-left: 2px;" title="View Details"><i class="bi bi-eye-fill"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        </div> </div><?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash;
    if (!hash) return;
    var trigger = document.querySelector('[data-bs-target="' + hash + '"]');
    if (trigger && window.bootstrap) {
        bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
});
</script>

</body>
</html>