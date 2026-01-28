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
    "SELECT br.*, h.hospital_name, h.registration_number, h.contact_person, h.contact_phone, h.id as hospital_id,
            bs.units_available as current_stock
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

// Check if request is approved - ONLY allow fulfillment of approved requests
if ($request['status'] !== 'approved') {
    $_SESSION['error'] = "Only approved requests can be marked as fulfilled. Current status: " . $request['status'];
    header('Location: view_blood_request.php?id=' . $requestId);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryMethod = $_POST['delivery_method'] ?? '';
    $deliveryNotes = $_POST['delivery_notes'] ?? '';
    $actualUnits = $_POST['actual_units'] ?? $request['units_required'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First, get existing admin notes
        $stmt = $pdo->prepare("SELECT admin_notes FROM blood_requests WHERE id = :id");
        $stmt->execute(['id' => $requestId]);
        $existingNotes = $stmt->fetchColumn();
        
        // Prepare new notes
        $newNotes = ($existingNotes ? $existingNotes . "\n\n" : '') . 
                   "Fulfillment Details:\n" .
                   "Delivery Method: " . $deliveryMethod . "\n" .
                   "Units Delivered: " . $actualUnits . "\n" .
                   "Notes: " . $deliveryNotes;
        
        // Update blood request status to fulfilled
        $stmt = $pdo->prepare(
            "UPDATE blood_requests 
             SET status = 'fulfilled',
                 fulfilled_by = :admin_id,
                 fulfilled_at = CURRENT_TIMESTAMP,
                 admin_notes = :notes
             WHERE id = :id"
        );
        $stmt->execute([
            'admin_id' => $_SESSION['user_id'],
            'notes' => $newNotes,
            'id' => $requestId
        ]);
        
        // Update blood stock (deduct units) - This happens ONLY on fulfillment
        $stmt = $pdo->prepare(
            "UPDATE blood_stocks 
             SET units_available = units_available - :units,
                 units_used = units_used + :units,
                 last_updated = CURRENT_TIMESTAMP,
                 updated_by = :admin_id
             WHERE blood_type = :blood_type"
        );
        $stmt->execute([
            'units' => $actualUnits,
            'admin_id' => $_SESSION['user_id'],
            'blood_type' => $request['blood_type']
        ]);
        
        // Create fulfillment transaction with stock deduction
        $stmt = $pdo->prepare(
            "INSERT INTO blood_transactions (
                blood_type, transaction_type, units, 
                source_destination, purpose, performed_by, notes
            ) VALUES (
                :blood_type, 'used', :units,
                :hospital, 'Blood request fulfilled', :admin_id, :notes
            )"
        );
        
        $transactionNotes = "Delivery method: " . $deliveryMethod . ". " . $deliveryNotes;
        
        $stmt->execute([
            'blood_type' => $request['blood_type'],
            'units' => $actualUnits,
            'hospital' => $request['hospital_name'],
            'admin_id' => $_SESSION['user_id'],
            'notes' => $transactionNotes
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
                :user_id, 'blood_request_fulfilled', 
                'Blood Request Fulfilled',
                :message,
                :metadata
            )"
        );
        
        $message = 'Your blood request #' . $request['request_number'] . ' has been fulfilled and delivered. ' . 
                   $actualUnits . ' units of ' . $request['blood_type'] . ' have been dispatched.';
        
        $stmt->execute([
            'user_id' => $hospitalUserId,
            'message' => $message,
            'metadata' => json_encode([
                'request_number' => $request['request_number'],
                'request_id' => $requestId,
                'units_delivered' => $actualUnits,
                'delivery_method' => $deliveryMethod
            ])
        ]);
        
        // Create admin notification
        $stmt = $pdo->prepare(
            "INSERT INTO admin_notifications (
                notification_type, title, message, related_id, related_type, priority
            ) VALUES (
                'blood_request_fulfilled', 'Blood Request Fulfilled', 
                :message,
                :request_id, 'blood_request', 'low'
            )"
        );
        
        $adminMessage = 'Blood request #' . $request['request_number'] . ' has been fulfilled. ' . 
                       $actualUnits . ' units of ' . $request['blood_type'] . ' deducted from stock.';
        
        $stmt->execute([
            'message' => $adminMessage,
            'request_id' => $requestId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Blood request marked as fulfilled successfully! " . 
                                      $actualUnits . " units of " . $request['blood_type'] . " deducted from stock.";
        header('Location: view_blood_request.php?id=' . $requestId);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $_SESSION['error'] = "Failed to mark request as fulfilled: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Mark Request as Fulfilled</h1>
    <p class="text-gray-600 mb-8">Confirm delivery and complete the blood request. This will deduct stock from inventory.</p>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stock Alert if low -->
    <?php if ($request['current_stock'] < $request['units_required']): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
        <div class="flex items-center">
            <i class="ri-alert-line text-yellow-600 text-2xl mr-4"></i>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-800 text-lg">Low Stock Alert</h3>
                <p class="text-yellow-700">
                    Current stock: <?php echo $request['current_stock']; ?> units of <?php echo $request['blood_type']; ?><br>
                    Required: <?php echo $request['units_required']; ?> units<br>
                    <span class="font-medium">Deficit: <?php echo $request['units_required'] - $request['current_stock']; ?> units</span>
                </p>
                <p class="text-yellow-600 text-sm mt-2">
                    You can still mark as fulfilled, but stock will go negative. Consider adjusting stock first.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Request Details -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Request to be Fulfilled</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Request Number</p>
                    <p class="font-mono font-bold text-lg"><?php echo $request['request_number']; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Hospital</p>
                    <p class="font-medium"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                    <p class="text-sm text-gray-500">Contact: <?php echo htmlspecialchars($request['contact_person']); ?></p>
                    <p class="text-sm text-gray-500">Phone: <?php echo htmlspecialchars($request['contact_phone']); ?></p>
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
                    <p class="text-gray-500 text-sm">Approved Units</p>
                    <p class="font-bold text-4xl"><?php echo $request['units_required']; ?></p>
                    <p class="text-sm text-gray-500">units of blood</p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Approved Date</p>
                    <p class="font-medium"><?php echo date('M d, Y', strtotime($request['approved_at'])); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Current Stock</p>
                    <p class="font-bold text-xl <?php echo $request['current_stock'] >= $request['units_required'] ? 'text-green-600' : 'text-yellow-600'; ?>">
                        <?php echo $request['current_stock']; ?> units
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fulfillment Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Fulfillment Details</h2>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">Delivery Method *</label>
                <select name="delivery_method" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Select delivery method</option>
                    <option value="Hospital Pickup">Hospital Pickup</option>
                    <option value="Blood Bank Delivery">Blood Bank Delivery</option>
                    <option value="Emergency Courier">Emergency Courier</option>
                    <option value="Scheduled Transport">Scheduled Transport</option>
                    <option value="Other">Other (specify in notes)</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">Units Delivered *</label>
                <input type="number" name="actual_units" required
                       value="<?php echo $request['units_required']; ?>"
                       min="1" max="<?php echo $request['units_required'] * 2; ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <p class="text-sm text-gray-500 mt-2">
                    Approved units: <?php echo $request['units_required']; ?>. 
                    Enter actual units delivered.
                </p>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">Delivery Notes (Optional)</label>
                <textarea name="delivery_notes" rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                          placeholder="Enter any notes about the delivery, recipient details, or special instructions..."></textarea>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="confirm_delivery" required 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-gray-700">
                        I confirm that 
                        <span class="font-bold"><?php echo $request['units_required']; ?> units of <?php echo $request['blood_type']; ?></span>
                        have been delivered to <?php echo htmlspecialchars($request['hospital_name']); ?>
                    </span>
                </label>
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="confirm_stock" required 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-gray-700">
                        I understand that 
                        <span class="font-bold"><?php echo $request['units_required']; ?> units of <?php echo $request['blood_type']; ?></span>
                        will be deducted from the blood stock inventory
                    </span>
                </label>
            </div>
            
            <?php if ($request['current_stock'] < $request['units_required']): ?>
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="confirm_low_stock" required 
                           class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                    <span class="ml-2 text-yellow-700">
                        I acknowledge that stock is low and this will result in negative inventory.
                        Deficit: <?php echo $request['units_required'] - $request['current_stock']; ?> units.
                    </span>
                </label>
            </div>
            <?php endif; ?>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="notify_hospital" checked 
                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <span class="ml-2 text-gray-700">
                        Send fulfillment confirmation to the hospital
                    </span>
                </label>
            </div>
            
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="view_blood_request.php?id=<?php echo $requestId; ?>" 
                   class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <i class="ri-check-double-line mr-2"></i>
                    Mark as Fulfilled
                </button>
            </div>
            
            <p class="text-sm text-gray-500 mt-4">
                <i class="ri-information-line mr-1"></i>
                Marking this request as fulfilled will:
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li>Update the request status to "fulfilled"</li>
                    <li>Deduct <?php echo $request['units_required']; ?> units of <?php echo $request['blood_type']; ?> from stock</li>
                    <li>Record delivery details</li>
                    <li>Notify the hospital of fulfillment</li>
                    <li>Create a stock transaction record</li>
                    <li>Update the audit trail</li>
                </ul>
            </p>
        </form>
    </div>
    
    <!-- Stock Impact Summary -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="font-bold text-blue-900 mb-4">Stock Impact Summary</h3>
        <div class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg p-4 text-center">
                <p class="text-gray-500 text-sm">Current Stock</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $request['current_stock']; ?></p>
                <p class="text-sm text-gray-500">units of <?php echo $request['blood_type']; ?></p>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <p class="text-gray-500 text-sm">To Be Deducted</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $request['units_required']; ?></p>
                <p class="text-sm text-gray-500">units</p>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <p class="text-gray-500 text-sm">Remaining Stock</p>
                <p class="text-2xl font-bold <?php echo ($request['current_stock'] - $request['units_required']) >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $request['current_stock'] - $request['units_required']; ?>
                </p>
                <p class="text-sm text-gray-500">units after fulfillment</p>
            </div>
        </div>
        
        <?php if (($request['current_stock'] - $request['units_required']) < 0): ?>
        <div class="p-4 bg-red-100 border border-red-200 rounded-lg">
            <h4 class="font-medium text-red-800 mb-2">⚠ Stock Will Go Negative</h4>
            <p class="text-red-700 text-sm">
                After fulfillment, stock of <?php echo $request['blood_type']; ?> will be negative.
                Consider adding stock first or adjusting the fulfillment amount.
            </p>
        </div>
        <?php elseif (($request['current_stock'] - $request['units_required']) < 10): ?>
        <div class="p-4 bg-yellow-100 border border-yellow-200 rounded-lg">
            <h4 class="font-medium text-yellow-800 mb-2">⚠ Low Stock Warning</h4>
            <p class="text-yellow-700 text-sm">
                After fulfillment, stock of <?php echo $request['blood_type']; ?> will be critically low.
                Consider restocking soon.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>