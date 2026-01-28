<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eventId = $_POST['event_id'] ?? 0;
    
    if (empty($action) || empty($eventId)) {
        header('Location: manage_events.php?error=invalid_request');
        exit;
    }
    
    try {
        switch ($action) {
            case 'approve':
                // Approve the event
                $result = Database::execute(
                    "UPDATE events 
                     SET approval_status = 'approved', 
                         approved_by = ?, 
                         approved_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$user->id, $eventId]
                );
                
                // Create notification for event creator
                $event = Database::fetch(
                    "SELECT created_by, title FROM events WHERE id = ?",
                    [$eventId]
                );
                
                if ($event && $event['created_by']) {
                    Database::execute(
                        "INSERT INTO notifications (user_id, type, title, message, metadata) 
                         VALUES (?, 'event_approved', 'Event Approved', ?, ?)",
                        [
                            $event['created_by'],
                            "Your event '{$event['title']}' has been approved.",
                            json_encode(['event_id' => $eventId])
                        ]
                    );
                }
                
                header('Location: manage_events.php?success=event_approved');
                break;
                
            case 'reject':
                // Reject the event
                $rejectionReason = $_POST['rejection_reason'] ?? '';
                
                if (empty($rejectionReason)) {
                    header('Location: manage_events.php?error=reason_required');
                    exit;
                }
                
                $result = Database::execute(
                    "UPDATE events 
                     SET approval_status = 'rejected', 
                         rejection_reason = ?,
                         approved_by = ?, 
                         approved_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$rejectionReason, $user->id, $eventId]
                );
                
                // Create notification for event creator
                $event = Database::fetch(
                    "SELECT created_by, title FROM events WHERE id = ?",
                    [$eventId]
                );
                
                if ($event && $event['created_by']) {
                    Database::execute(
                        "INSERT INTO notifications (user_id, type, title, message, metadata) 
                         VALUES (?, 'event_rejected', 'Event Rejected', ?, ?)",
                        [
                            $event['created_by'],
                            "Your event '{$event['title']}' has been rejected. Reason: {$rejectionReason}",
                            json_encode(['event_id' => $eventId, 'reason' => $rejectionReason])
                        ]
                    );
                }
                
                header('Location: manage_events.php?success=event_rejected');
                break;
                
            case 'delete':
                // Check if event exists
                $event = Database::fetch(
                    "SELECT id FROM events WHERE id = ?",
                    [$eventId]
                );
                
                if (!$event) {
                    header('Location: manage_events.php?error=event_not_found');
                    exit;
                }
                
                // First delete related records (foreign key constraints)
                Database::execute("DELETE FROM event_requirements WHERE event_id = ?", [$eventId]);
                Database::execute("DELETE FROM event_category_mapping WHERE event_id = ?", [$eventId]);
                Database::execute("DELETE FROM event_registrations WHERE event_id = ?", [$eventId]);
                
                // Then delete the event
                $result = Database::execute(
                    "DELETE FROM events WHERE id = ?",
                    [$eventId]
                );
                
                if ($result > 0) {
                    // Create admin notification
                    Database::execute(
                        "INSERT INTO admin_notifications (notification_type, title, message, related_id, related_type, priority) 
                         VALUES ('event_deleted', 'Event Deleted', 'An event was deleted by administrator.', ?, 'event', 'medium')",
                        [$eventId]
                    );
                    
                    header('Location: manage_events.php?success=event_deleted');
                } else {
                    header('Location: manage_events.php?error=delete_failed');
                }
                break;
                
            default:
                header('Location: manage_events.php?error=invalid_action');
                break;
        }
        
        exit;
        
    } catch (Exception $e) {
        error_log('Process event error: ' . $e->getMessage());
        header('Location: manage_events.php?error=server_error');
        exit;
    }
} else {
    header('Location: manage_events.php');
    exit;
}