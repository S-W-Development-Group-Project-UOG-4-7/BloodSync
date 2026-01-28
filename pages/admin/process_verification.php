<?php
// pages/admin/process_verification.php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Method not allowed');
}

// Get and validate input
$action = $_POST['action'] ?? '';
$user_id = intval($_POST['user_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
if (empty($action) || $user_id <= 0) {
    $_SESSION['error'] = 'Invalid request parameters';
    header('Location: pending_verifications.php');
    exit;
}

// Validate action
$validActions = ['approve', 'reject', 'reverify', 'review'];
if (!in_array($action, $validActions)) {
    $_SESSION['error'] = 'Invalid action';
    header('Location: pending_verifications.php');
    exit;
}

try {
    $current_admin_id = Auth::getUser()['id'];
    $current_time = date('Y-m-d H:i:s');
    
    switch ($action) {
        case 'approve':
            Database::query(
                "UPDATE users SET 
                 verification_status = 'approved',
                 verified_at = :verified_at,
                 verified_by = :verified_by,
                 verification_notes = :notes
                 WHERE id = :user_id",
                [
                    'verified_at' => $current_time,
                    'verified_by' => $current_admin_id,
                    'notes' => $notes,
                    'user_id' => $user_id
                ]
            );
            
            // Log the action
            logAction("approved donor verification", $user_id);
            $_SESSION['success'] = 'Donor verification approved successfully';
            break;
            
        case 'reject':
            if (empty($reason)) {
                $_SESSION['error'] = 'Reason is required for rejection';
                header('Location: pending_verifications.php');
                exit;
            }
            
            // Build the notes string for PostgreSQL
            $rejection_notes = 'Reason: ' . $reason;
            if (!empty($notes)) {
                $rejection_notes .= "\n\n" . $notes;
            }
            
            Database::query(
                "UPDATE users SET 
                 verification_status = 'rejected',
                 verified_at = :verified_at,
                 verified_by = :verified_by,
                 verification_notes = :verification_notes,
                 rejection_reason = :reason
                 WHERE id = :user_id",
                [
                    'verified_at' => $current_time,
                    'verified_by' => $current_admin_id,
                    'reason' => $reason,
                    'verification_notes' => $rejection_notes,
                    'user_id' => $user_id
                ]
            );
            
            // Log the action
            logAction("rejected donor verification", $user_id);
            $_SESSION['success'] = 'Donor verification rejected';
            break;
            
        case 'reverify':
            if (empty($reason)) {
                $_SESSION['error'] = 'Reason is required for re-verification';
                header('Location: pending_verifications.php');
                exit;
            }
            
            // Build the notes string for PostgreSQL
            $reverify_notes = 'Re-verification requested. Reason: ' . $reason;
            if (!empty($notes)) {
                $reverify_notes .= "\n\n" . $notes;
            }
            
            Database::query(
                "UPDATE users SET 
                 verification_status = 'pending',
                 verified_at = NULL,
                 verified_by = NULL,
                 verification_notes = :verification_notes,
                 rejection_reason = :reason
                 WHERE id = :user_id",
                [
                    'reason' => $reason,
                    'verification_notes' => $reverify_notes,
                    'user_id' => $user_id
                ]
            );
            
            // Log the action
            logAction("requested re-verification", $user_id);
            $_SESSION['success'] = 'Donor marked for re-verification';
            break;
            
        case 'review':
            // Build the notes string for PostgreSQL
            $review_notes = 'Review requested by admin.';
            if (!empty($notes)) {
                $review_notes .= " " . $notes;
            }
            
            Database::query(
                "UPDATE users SET 
                 verification_status = 'pending',
                 verified_at = NULL,
                 verified_by = NULL,
                 verification_notes = :verification_notes
                 WHERE id = :user_id",
                [
                    'verification_notes' => $review_notes,
                    'user_id' => $user_id
                ]
            );
            
            // Log the action
            logAction("requested review", $user_id);
            $_SESSION['success'] = 'Donor verification set to pending review';
            break;
    }
    
    // Optional: Send notification email to donor
    // sendVerificationNotification($user_id, $action, $reason);
    
} catch (Exception $e) {
    error_log('Verification processing error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while processing the verification';
}

// Get donor ID for redirect back to donor details page
try {
    $donor = Database::fetch("SELECT id FROM donors WHERE user_id = :user_id", ['user_id' => $user_id]);
    if ($donor) {
        header('Location: donor_details.php?id=' . $donor['id']);
    } else {
        header('Location: pending_verifications.php');
    }
} catch (Exception $e) {
    header('Location: pending_verifications.php');
}
exit;

/**
 * Log admin action
 */
function logAction($action, $donor_user_id) {
    try {
        Database::query(
            "INSERT INTO admin_logs (admin_id, action, target_user_id, created_at) 
             VALUES (:admin_id, :action, :target_user_id, :created_at)",
            [
                'admin_id' => Auth::getUser()['id'],
                'action' => $action,
                'target_user_id' => $donor_user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
    } catch (Exception $e) {
        error_log('Failed to log admin action: ' . $e->getMessage());
    }
}

/**
 * Send email notification to donor
 */
function sendVerificationNotification($user_id, $action, $reason = '') {
    try {
        // Get donor email
        $result = Database::query(
            "SELECT u.email, d.first_name, d.last_name 
             FROM users u 
             JOIN donors d ON u.id = d.user_id 
             WHERE u.id = :user_id",
            ['user_id' => $user_id]
        );
        
        $donor = $result->fetch();
        if (!$donor) return;
        
        $to = $donor['email'];
        $name = $donor['first_name'] . ' ' . $donor['last_name'];
        
        $subject = '';
        $message = '';
        
        switch ($action) {
            case 'approve':
                $subject = 'Account Verification Approved - BloodSync';
                $message = "Dear $name,\n\nYour donor account has been verified and approved. You can now access all donor features.\n\nThank you for joining BloodSync!";
                break;
                
            case 'reject':
                $subject = 'Account Verification Update - BloodSync';
                $message = "Dear $name,\n\nYour donor account verification has been rejected.\n\nReason: $reason\n\nPlease update your information and submit for verification again.";
                break;
                
            case 'reverify':
                $subject = 'Re-verification Required - BloodSync';
                $message = "Dear $name,\n\nYour donor account requires re-verification.\n\nReason: $reason\n\nPlease update your information and submit for verification again.";
                break;
        }
        
        // Use your email sending function
        // Mailer::send($to, $subject, $message);
        
    } catch (Exception $e) {
        error_log('Failed to send notification: ' . $e->getMessage());
    }
}
?>