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
    "SELECT br.*, h.hospital_name, h.registration_number, h.id as hospital_id,
            bs.units_available as stock_available
     FROM blood_requests br
     JOIN hospitals h ON br.hospital_id = h.id
     LEFT JOIN blood_stocks bs ON br.blood_type = bs.blood_type
     WHERE br.id = :id"
);
$stmt->execute(['id' => $requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: blood_requests.php');
    exit();
}

// Check if request is already approved
if ($request['status'] !== 'pending') {
    $_SESSION['error'] = "This request has already been processed.";
    header('Location: view_blood_request.php?id=' . $requestId);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update blood request status ONLY - don't deduct stock yet
        $stmt = $pdo->prepare(
            "UPDATE blood_requests 
             SET status = 'approved',
                 admin_notes = :notes,
                 approved_by = :admin_id,
                 approved_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $stmt->execute([
            'notes' => $adminNotes,
            'admin_id' => $_SESSION['user_id'],
            'id' => $requestId
        ]);
        
        // Create transaction record for approval (not stock deduction)
        $stmt = $pdo->prepare(
            "INSERT INTO blood_transactions (
                blood_type, transaction_type, units, 
                source_destination, purpose, performed_by
            ) VALUES (
                :blood_type, 'approved', :units,
                :hospital, 'Blood request approved', :admin_id
            )"
        );
        $stmt->execute([
            'blood_type' => $request['blood_type'],
            'units' => $request['units_required'],
            'hospital' => $request['hospital_name'],
            'admin_id' => $_SESSION['user_id']
        ]);
        
        // DO NOT update blood stock here - wait for fulfillment
        
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
                :user_id, 'blood_request_approved', 
                'Blood Request Approved',
                :message,
                :metadata
            )"
        );
        
        $message = 'Your blood request #' . $request['request_number'] . ' for ' . 
                   $request['units_required'] . ' units of ' . $request['blood_type'] . ' has been approved.';
        
        $stmt->execute([
            'user_id' => $hospitalUserId,
            'message' => $message,
            'metadata' => json_encode([
                'request_number' => $request['request_number'],
                'request_id' => $requestId,
                'units_approved' => $request['units_required'],
                'blood_type' => $request['blood_type']
            ])
        ]);
        
        // Create admin notification
        $stmt = $pdo->prepare(
            "INSERT INTO admin_notifications (
                notification_type, title, message, related_id, related_type, priority
            ) VALUES (
                'blood_request_approved', 'Blood Request Approved', 
                :message,
                :request_id, 'blood_request', 'low'
            )"
        );
        
        $adminMessage = 'Blood request #' . $request['request_number'] . ' from ' . 
                       $request['hospital_name'] . ' has been approved.';
        
        $stmt->execute([
            'message' => $adminMessage,
            'request_id' => $requestId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Blood request approved successfully! " . 
                                      $request['units_required'] . " units of " . 
                                      $request['blood_type'] . " have been reserved.";
        header('Location: view_blood_request.php?id=' . $requestId);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $_SESSION['error'] = "Failed to approve request: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Approve Blood Request</h1>
    <p class="text-gray-600 mb-8">Review and approve blood supply request.</p>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Request Details -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Request Details</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Request Number</p>
                    <p class="font-mono font-bold text-lg"><?php echo $request['request_number']; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Hospital</p>
                    <p class="font-medium"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                    <p class="text-sm text-gray-500">Reg: <?php echo htmlspecialchars($request['registration_number']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Patient</p>
                    <p class="font-medium"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                    <p class="text-sm text-gray-500"><?php echo $request['patient_age']; ?>y, <?php echo $request['patient_sex']; ?></p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Blood Type</p>
                    <p class="font-bold text-3xl text-red-600"><?php echo $request['blood_type']; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Units Required</p>
                    <p class="font-bold text-4xl"><?php echo $request['units_required']; ?></p>
                    <p class="text-sm text-gray-500">units of blood</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Urgency Level</p>
                    <span class="px-3 py-1 rounded-full text-sm font-medium 
                        <?php echo $request['urgency_level'] === 'critical' ? 'bg-red-100 text-red-800' : 
                               ($request['urgency_level'] === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'); ?>">
                        <?php echo ucfirst($request['urgency_level']); ?>
                    </span>
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
    
    <!-- Stock Availability (Informational only) -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-4">
                <i class="ri-information-line text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-blue-800">Stock Information</h2>
                <p class="text-blue-600">
                    Current stock: <?php echo $request['stock_available']; ?> units of <?php echo $request['blood_type']; ?>
                </p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-700">Required Units</span>
                <span class="font-bold text-lg"><?php echo $request['units_required']; ?></span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-700">Available Stock</span>
                <span class="font-bold text-lg <?php echo $request['stock_available'] >= $request['units_required'] ? 'text-green-600' : 'text-yellow-600'; ?>">
                    <?php echo $request['stock_available']; ?>
                </span>
            </div>
            <div class="flex justify-between items-center font-medium">
                <span>Status</span>
                <?php if ($request['stock_available'] >= $request['units_required']): ?>
                <span class="text-green-600">✓ Sufficient stock available</span>
                <?php else: ?>
                <span class="text-yellow-600">⚠ Stock may be low</span>
                <?php endif; ?>
            </div>
            
            <?php if ($request['stock_available'] < $request['units_required']): ?>
            <div class="mt-4 p-3 bg-yellow-100 border border-yellow-200 rounded-lg">
                <p class="text-yellow-700 text-sm">
                    <i class="ri-alert-line mr-1"></i>
                    <strong>Note:</strong> There may be insufficient stock to fulfill this request.
                    Deficit: <?php echo $request['units_required'] - $request['stock_available']; ?> units.
                </p>
                <p class="text-yellow-600 text-sm mt-1">
                    You can still approve the request. Stock will be deducted when marked as fulfilled.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Approval Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Approval Details</h2>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">Admin Notes (Optional)</label>
                <textarea name="admin_notes" rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none"
                          placeholder="Add any notes or instructions regarding this approval..."></textarea>
                <p class="text-sm text-gray-500 mt-2">
                    These notes will be visible to the hospital and recorded in the audit trail.
                </p>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="confirm_stock" required 
                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <span class="ml-2 text-gray-700">
                        I approve this request for 
                        <span class="font-bold"><?php echo $request['units_required']; ?> units of <?php echo $request['blood_type']; ?></span>
                        for <?php echo htmlspecialchars($request['hospital_name']); ?>
                    </span>
                </label>
            </div>
            
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="view_blood_request.php?id=<?php echo $requestId; ?>" 
                   class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                    <i class="ri-check-line mr-2"></i>
                    Approve Request
                </button>
            </div>
            
            <p class="text-sm text-gray-500 mt-4">
                <i class="ri-information-line mr-1"></i>
                Approving this request will:
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li>Update the request status to "approved"</li>
                    <li>Reserve <?php echo $request['units_required']; ?> units of <?php echo $request['blood_type']; ?></li>
                    <li>Create an approval transaction record</li>
                    <li>Notify the hospital</li>
                    <li>Update the audit trail</li>
                    <li><strong>Note:</strong> Stock will be deducted when request is marked as fulfilled</li>
                </ul>
            </p>
        </form>
    </div>
    
    <!-- Additional Information -->
    <div class="mt-8 bg-gray-50 rounded-xl p-6">
        <h3 class="font-bold text-gray-900 mb-4">Approval Workflow</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Two-Step Process</h4>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-2">
                            <span class="text-xs font-bold">1</span>
                        </div>
                        <span><strong>Approve Request:</strong> Reserve blood units for the hospital</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">
                            <span class="text-xs font-bold">2</span>
                        </div>
                        <span><strong>Mark as Fulfilled:</strong> After delivery, deduct stock and complete request</span>
                    </li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Emergency Contacts</h4>
                <div class="space-y-2">
                    <div class="flex items-center">
                        <i class="ri-phone-line text-gray-400 mr-2"></i>
                        <span class="text-sm">Blood Bank: 011-1234567</span>
                    </div>
                    <div class="flex items-center">
                        <i class="ri-mail-line text-gray-400 mr-2"></i>
                        <span class="text-sm">Email: bloodbank@bloodsync.com</span>
                    </div>
                    <div class="flex items-center">
                        <i class="ri-time-line text-gray-400 mr-2"></i>
                        <span class="text-sm">Available: 24/7 for emergencies</span>
                    </div>
                </div>
                <?php if ($request['urgency_level'] === 'critical'): ?>
                <div class="mt-4 p-3 bg-red-100 rounded-lg">
                    <p class="text-red-700 text-sm">
                        <i class="ri-alarm-warning-line mr-1"></i>
                        This is a <strong>CRITICAL</strong> request. Please ensure immediate processing.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>