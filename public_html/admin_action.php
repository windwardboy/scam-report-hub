<?php
session_start(); 

// === Admin Authentication Check ===
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php'); exit(); 
}
// === End Authentication Check ===

// Include Config and Database
require_once '/home/sites/32b/b/b19bae1aa8/config.php'; // Adjust path if needed
include 'includes/db.php'; 

// Define default redirect target
$redirect_page = 'admin_moderate.php'; // Base redirect page

// === Method Check ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_page); exit();
}

// --- CSRF Token Verification ---
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token validation failed for admin action. IP: " . $_SERVER['REMOTE_ADDR']);
    $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid security token. Action aborted.'];
    header('Location: ' . $redirect_page); exit();
}
// --- End CSRF Token Verification ---

// --- Get Action ---
$action = $_POST['action'] ?? null;

// --- Define Allowed Actions ---
$report_actions = ['approve', 'reject', 'delete', 'draft', 'pending'];
$comment_actions = ['approve_comment', 'reject_comment', 'delete_comment'];
$flag_actions = ['review_flag', 'delete_flag']; // **** ADDED Flag Actions ****
$allowed_actions = array_merge($report_actions, $comment_actions, $flag_actions);

// --- Validate Action ---
if (empty($action) || !in_array($action, $allowed_actions)) {
    $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid Action specified.'];
    header('Location: ' . $redirect_page); exit();
}


// --- Process Actions based on Type ---

if (in_array($action, $report_actions)) {
    // === PROCESS REPORT ACTIONS ===
    $scam_id = filter_input(INPUT_POST, 'scam_id', FILTER_VALIDATE_INT); 
    if (!$scam_id || $scam_id <= 0) { $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid Report ID for report action.']; header('Location: ' . $redirect_page); exit(); }

    if ($action === 'approve' || $action === 'reject' || $action === 'draft' || $action === 'pending') {
        $status_for_action = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'draft' => 'draft',
            'pending' => 'pending',
        ];
        $new_status = $status_for_action[$action];
        if ($action === 'draft') {
            $redirect_page .= '#drafts-pane';
        }
        try {
            $sql = "UPDATE scams SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute([$new_status, $scam_id])) {
                $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Failed to update status for report #{$scam_id}."];
            } elseif ($stmt->rowCount() > 0) {
                $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Report #{$scam_id} marked as {$new_status}."];
            } else {
                $_SESSION['admin_feedback'] = ['type' => 'warning', 'message' => "Report #{$scam_id} was already {$new_status}."];
            }
        } catch (PDOException $e) {
            error_log("DB error update report status ID {$scam_id}: ".$e->getMessage());
            $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "DB error update status report #{$scam_id}."];
        }
    } elseif ($action === 'delete') {
        $evidence_path_relative = null; $delete_success = false;
        try { $pdo->beginTransaction(); $stmt_path = $pdo->prepare("SELECT evidence_path FROM scams WHERE id = ?"); $stmt_path->execute([$scam_id]); $evidence_path_relative = $stmt_path->fetchColumn(); $stmt_delete = $pdo->prepare("DELETE FROM scams WHERE id = ?"); $delete_success = $stmt_delete->execute([$scam_id]); if ($delete_success && $stmt_delete->rowCount() > 0) { $pdo->commit(); $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Report #{$scam_id} permanently deleted."]; if ($evidence_path_relative) { $evidence_path_absolute = __DIR__ . '/' . ltrim($evidence_path_relative, '/'); if (file_exists($evidence_path_absolute) && is_file($evidence_path_absolute)) { if (!@unlink($evidence_path_absolute)) { error_log("WARNING: Failed delete evidence file: ".$evidence_path_absolute." for report ID: ".$scam_id); $_SESSION['admin_feedback']['message'] .= " (Warn: Could not delete file)"; } } else { error_log("Notice: Evidence file not found: ".$evidence_path_absolute); } } } else { $pdo->rollBack(); error_log("DB error deleting report ID {$scam_id}."); $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Failed to delete report #{$scam_id}."]; } } catch (PDOException $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } error_log("DB exception delete report ID {$scam_id}: ".$e->getMessage()); $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "DB exception delete report #{$scam_id}."]; }
    }

} elseif (in_array($action, $comment_actions)) {
    // === PROCESS COMMENT ACTIONS ===
    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT); 
    if (!$comment_id || $comment_id <= 0) { $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid Comment ID for comment action.']; header('Location: ' . $redirect_page); exit(); }
    $redirect_page .= '#comments-pane'; // Set redirect anchor

    if ($action === 'approve_comment' || $action === 'reject_comment') {
        $new_status = ($action === 'approve_comment') ? 'approved' : 'rejected';
        try { $sql = "UPDATE comments SET status = ? WHERE id = ?"; $stmt = $pdo->prepare($sql); if ($stmt->execute([$new_status, $comment_id])) { $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Comment #{$comment_id} marked as {$new_status}."]; } else { $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Failed update status comment #{$comment_id}."]; } } catch (PDOException $e) { error_log("DB error update comment status ID {$comment_id}: ".$e->getMessage()); $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "DB error update status comment #{$comment_id}."]; }
    } elseif ($action === 'delete_comment') {
        try { $stmt_delete = $pdo->prepare("DELETE FROM comments WHERE id = ?"); if ($stmt_delete->execute([$comment_id]) && $stmt_delete->rowCount() > 0) { $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Comment #{$comment_id} permanently deleted."]; } else { error_log("DB error deleting comment ID {$comment_id}."); $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Failed to delete comment #{$comment_id}."]; } } catch (PDOException $e) { error_log("DB exception delete comment ID {$comment_id}: ".$e->getMessage()); $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "DB exception deleting comment #{$comment_id}."]; }
    }

} elseif (in_array($action, $flag_actions)) {
    // === **** ADDED: PROCESS FLAG ACTIONS **** ===
    $flag_id = filter_input(INPUT_POST, 'flag_id', FILTER_VALIDATE_INT); 
    if (!$flag_id || $flag_id <= 0) { 
        $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => 'Invalid Flag ID for flag action.'];
        header('Location: ' . $redirect_page); // Default redirect here
        exit(); 
    }
    
    // Set redirect target to include the flags tab anchor
    $redirect_page .= '#flags-pane'; 

    if ($action === 'review_flag') {
        // --- Handle Mark Flag as Reviewed ---
        try {
            $sql = "UPDATE flags SET status = 'reviewed' WHERE id = ?"; // Change status from 'new'
            $stmt = $pdo->prepare($sql);
             if ($stmt->execute([$flag_id])) {
                 $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Flag #{$flag_id} has been marked as reviewed."];
            } else {
                 $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Failed to update status for flag #{$flag_id}."];
            }
        } catch (PDOException $e) {
            error_log("Database error updating flag status ID {$flag_id}: " . $e->getMessage());
            $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Database error updating status for flag #{$flag_id}."];
        }

    } elseif ($action === 'delete_flag') {
        // --- Handle Flag Deletion ---
        try {
             $stmt_delete = $pdo->prepare("DELETE FROM flags WHERE id = ?");
             if ($stmt_delete->execute([$flag_id]) && $stmt_delete->rowCount() > 0) {
                 $_SESSION['admin_feedback'] = ['type' => 'success', 'message' => "Flag #{$flag_id} has been permanently deleted."];
             } else {
                 error_log("Database error deleting flag ID {$flag_id}. rowCount: " . $stmt_delete->rowCount());
                 $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Failed to delete flag #{$flag_id} from database."];
             }
        } catch (PDOException $e) {
            error_log("Database exception during flag deletion ID {$flag_id}: " . $e->getMessage());
            $_SESSION['admin_feedback'] = ['type' => 'danger', 'message' => "Database exception deleting flag #{$flag_id}."];
        }
    }
    // === **** END FLAG ACTIONS **** ===
}

// --- Final Redirect ---
header('Location: ' . $redirect_page);
exit();

?>