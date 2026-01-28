<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireAdmin('../login.php');

$requestId = $_GET['id'] ?? null;

if (!$requestId) {
    header('Location: blood_requests.php');
    exit();
}

// Create database connection
$pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get blood request details
$stmt = $pdo->prepare(
    "SELECT br.*, h.hospital_name, h.registration_number
     FROM blood_requests br
     JOIN hospitals h ON br.hospital_id = h.id
     WHERE br.id = :id"
);
$stmt->execute(['id' => $requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: blood_requests.php');
    exit();
}

// Check if request is already processed
if ($request['status'] !== 'pending') {
    $_SESSION['error'] = "This request has already been processed.";
    header('Location: view_blood_request.php?id=' . $requestId);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rejectionReason = $_POST['rejection_reason'] ?? '';
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    if (empty($rejectionReason)) {
        $_SESSION['error'] = "Please provide a reason for rejection.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update blood request status
            $stmt = $pdo->prepare(
                "UPDATE blood_requests 
                 SET status = 'rejected',
                     admin_notes = CONCAT('Rejection Reason: ', :reason, '\n\n', :notes),
                     approved_by = :admin_id,
                     approved_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $stmt->execute([
                'reason' => $rejectionReason,
                'notes' => $adminNotes,
                'admin_id' => $_SESSION['user_id'],
                'id' => $requestId
            ]);
            
            // Create notification for hospital
            $stmt = $pdo->prepare(
                "SELECT user_id FROM hospitals WHERE id = :hospital_id"
            );
            $stmt->execute(['hospital_id' => $request['hospital_id']]);
            $hospitalUserId = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare(
                "INSERT INTO notifications (
                    user_id, type, title, message, metadata
                ) VALUES (
                    :user_id, 'blood_request_rejected', 
                    'Blood Request Rejected',
                    :message,
                    :metadata
                )"
            );
            
            $message = 'Your blood request #' . $request['request_number'] . 
                       ' has been rejected. Reason: ' . $rejectionReason;
            
            $stmt->execute([
                'user_id' => $hospitalUserId,
                'message' => $message,
                'metadata' => json_encode([
                    'request_number' => $request['request_number'],
                    'request_id' => $requestId,
                    'rejection_reason' => $rejectionReason
                ])
            ]);
            
            // Create admin notification
            $stmt = $pdo->prepare(
                "INSERT INTO admin_notifications (
                    notification_type, title, message, related_id, related_type, priority
                ) VALUES (
                    'blood_request_rejected', 'Blood Request Rejected', 
                    :message,
                    :request_id, 'blood_request', 'low'
                )"
            );
            
            $adminMessage = 'Blood request #' . $request['request_number'] . ' from ' . 
                           $request['hospital_name'] . ' has been rejected.';
            
            $stmt->execute([
                'message' => $adminMessage,
                'request_id' => $requestId
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success_message'] = "Blood request rejected successfully. Hospital has been notified.";
            header('Location: view_blood_request.php?id=' . $requestId);
            exit();
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            $_SESSION['error'] = "Failed to reject request: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Reject Blood Request</h1>
    <p class="text-gray-600 mb-8">Provide a reason for rejecting this blood supply request.</p>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Request Details -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Request to be Rejected</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Request Number</p>
                    <p class="font-mono font-bold"><?php echo $request['request_number']; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Hospital</p>
                    <p class="font-medium"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Patient</p>
                    <p class="font-medium"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Blood Type</p>
                    <p class="font-bold text-2xl text-red-600"><?php echo $request['blood_type']; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Units Required</p>
                    <p class="font-bold text-3xl"><?php echo $request['units_required']; ?></p>
                    <p class="text-sm text-gray-500">units of blood</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Required Date</p>
                    <p class="font-medium"><?php echo date('M d, Y', strtotime($request['required_date'])); ?></p>
                </div>
            </div>
        </div>
        
        <?php if ($request['reason']): ?>
        <div class="mt-6 pt-6 border-t border-gray-200">
            <p class="text-gray-500 text-sm mb-2">Reason for Request</p>
            <p class="text-gray-700 bg-gray-50 p-4 rounded-lg"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Rejection Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Rejection Details</h2>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">
                    Rejection Reason *
                    <span class="text-red-500">*</span>
                </label>
                <select name="rejection_reason" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                    <option value="">Select a reason for rejection</option>
                    <option value="Insufficient information">Insufficient information provided</option>
                    <option value="Incomplete patient details">Incomplete patient details</option>
                    <option value="Insufficient stock">Insufficient blood stock</option>
                    <option value="Duplicate request">Duplicate or similar request exists</option>
                    <option value="Hospital verification pending">Hospital verification pending</option>
                    <option value="Invalid blood type request">Invalid blood type requested</option>
                    <option value="Requested date too soon">Requested date is too soon</option>
                    <option value="Other">Other (specify in notes)</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">Additional Notes (Optional)</label>
                <textarea name="admin_notes" rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                          placeholder="Provide additional details or instructions..."></textarea>
                <p class="text-sm text-gray-500 mt-2">
                    These notes will be included in the rejection notification sent to the hospital.
                </p>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="confirm_rejection" required 
                           class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                    <span class="ml-2 text-gray-700">
                        I confirm that I want to reject this blood request from 
                        <span class="font-bold"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                    </span>
                </label>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="notify_hospital" checked 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-gray-700">
                        Send notification email to the hospital
                    </span>
                </label>
            </div>
            
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="view_blood_request.php?id=<?php echo $requestId; ?>" 
                   class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                    <i class="ri-close-line mr-2"></i>
                    Reject Request
                </button>
            </div>
            
            <p class="text-sm text-gray-500 mt-4">
                <i class="ri-information-line mr-1"></i>
                Rejecting this request will:
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li>Update the request status to "rejected"</li>
                    <li>Notify the hospital with the rejection reason</li>
                    <li>Record the rejection in the audit trail</li>
                    <li>Allow the hospital to submit a new request if needed</li>
                </ul>
            </p>
        </form>
    </div>
    
    <!-- Alternatives Section -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="font-bold text-blue-900 mb-4">Consider Alternatives Before Rejecting</h3>
        <div class="space-y-4">
            <div class="flex items-start">
                <i class="ri-lightbulb-line text-blue-600 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-medium text-blue-800">Partial Approval</h4>
                    <p class="text-blue-700 text-sm">
                        If stock is insufficient, consider approving a partial amount or suggesting alternative blood types.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start">
                <i class="ri-calendar-schedule-line text-blue-600 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-medium text-blue-800">Reschedule</h4>
                    <p class="text-blue-700 text-sm">
                        If the requested date is problematic, suggest an alternative date when stock will be available.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start">
                <i class="ri-customer-service-line text-blue-600 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-medium text-blue-800">Contact Hospital</h4>
                    <p class="text-blue-700 text-sm">
                        Consider contacting the hospital at <?php echo htmlspecialchars($request['contact_phone'] ?? 'N/A'); ?> 
                        to clarify or resolve issues before rejecting.
                    </p>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="view_blood_request.php?id=<?php echo $requestId; ?>" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="ri-arrow-left-line mr-1"></i>
                    Go back to review request details
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>