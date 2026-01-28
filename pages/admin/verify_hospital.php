<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireAdmin('../login.php');

$user = Auth::getUser();
$hospitalId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? ''; // 'approve' or 'reject'

if (!$hospitalId || !in_array($action, ['approve', 'reject'])) {
    header('Location: manage_hospitals.php');
    exit();
}

// Create database connection
$pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get hospital details
$stmt = $pdo->prepare(
    "SELECT h.*, u.email, u.verification_status 
     FROM hospitals h 
     JOIN users u ON h.user_id = u.id 
     WHERE h.id = :id"
);
$stmt->execute(['id' => $hospitalId]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    header('Location: manage_hospitals.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verificationNote = $_POST['verification_note'] ?? '';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update hospital verification status
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $isVerified = $action === 'approve' ? true : false;
        
        $stmt = $pdo->prepare(
            "UPDATE hospitals 
             SET is_verified = :is_verified, 
                 verification_status = :status,
                 verification_note = :note,
                 verified_at = CURRENT_TIMESTAMP,
                 verified_by = :verified_by 
             WHERE id = :id"
        );
        $stmt->execute([
            'is_verified' => $isVerified,
            'status' => $newStatus,
            'note' => $verificationNote,
            'verified_by' => $user['id'],
            'id' => $hospitalId
        ]);
        
        // Update user verification status
        $stmt = $pdo->prepare(
            "UPDATE users 
             SET is_verified = :is_verified, 
                 verification_status = :status,
                 verified_at = CURRENT_TIMESTAMP,
                 verified_by = :verified_by 
             WHERE id = (SELECT user_id FROM hospitals WHERE id = :id)"
        );
        $stmt->execute([
            'is_verified' => $isVerified,
            'status' => $newStatus,
            'verified_by' => $user['id'],
            'id' => $hospitalId
        ]);
        
        // Create notification for hospital
        $notificationMessage = $action === 'approve' 
            ? "Your hospital registration has been approved. You can now request blood supplies."
            : "Your hospital registration has been rejected. Reason: " . ($verificationNote ?: "Please contact support for more information.");
        
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, type, title, message) 
             VALUES (:user_id, 'verification', :title, :message)"
        );
        
        $title = $action === 'approve' ? 'Registration Approved' : 'Registration Rejected';
        
        $stmt->execute([
            'user_id' => $hospital['user_id'],
            'title' => $title,
            'message' => $notificationMessage
        ]);
        
        // Create admin notification for audit
        $stmt = $pdo->prepare(
            "INSERT INTO admin_notifications (notification_type, title, message, related_id, related_type) 
             VALUES ('hospital_verification', :title, :message, :hospital_id, 'hospital')"
        );
        
        $adminTitle = $action === 'approve' ? 'Hospital Approved' : 'Hospital Rejected';
        $adminMessage = "Hospital " . $hospital['hospital_name'] . " has been " . $newStatus . " by " . $user['email'];
        
        $stmt->execute([
            'title' => $adminTitle,
            'message' => $adminMessage,
            'hospital_id' => $hospitalId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Hospital " . $newStatus . " successfully!";
        header('Location: manage_hospitals.php');
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $_SESSION['error'] = "Failed to update hospital status: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        <?php echo ucfirst($action); ?> Hospital Registration
    </h1>
    <p class="text-gray-600 mb-8">Review hospital details before taking action.</p>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Hospital Details -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Hospital Information</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Hospital Name</p>
                    <p class="font-medium text-lg"><?php echo htmlspecialchars($hospital['hospital_name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Registration Number</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['registration_number']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Location</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['location']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">License Number</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['license_number'] ?: 'Not provided'); ?></p>
                    <?php if ($hospital['license_expiry']): ?>
                    <p class="text-sm <?php echo strtotime($hospital['license_expiry']) < time() ? 'text-red-500' : 'text-gray-500'; ?>">
                        Expires: <?php echo date('M d, Y', strtotime($hospital['license_expiry'])); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Contact Person</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_person']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Contact Email</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_email']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Contact Phone</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_phone']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Login Email</p>
                    <p class="font-medium"><?php echo htmlspecialchars($hospital['email']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Registration Date</p>
                    <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($hospital['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Current Status -->
    <div class="bg-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-50 border border-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-200 rounded-xl p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-full bg-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-100 text-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-600 flex items-center justify-center mr-4">
                <i class="ri-<?php echo $action === 'approve' ? 'check' : 'close'; ?>-line text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-800">
                    <?php echo ucfirst($action); ?> This Hospital
                </h2>
                <p class="text-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-600">
                    Current status: <span class="font-medium"><?php echo ucfirst($hospital['verification_status']); ?></span>
                </p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-4">
            <p class="text-gray-700">
                <?php if ($action === 'approve'): ?>
                    Approving this hospital will:
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Allow them to request blood supplies</li>
                        <li>Send them an approval notification</li>
                        <li>Grant access to hospital dashboard features</li>
                        <li>Enable blood request management</li>
                    </ul>
                <?php else: ?>
                    Rejecting this hospital will:
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Prevent them from requesting blood supplies</li>
                        <li>Send them a rejection notification</li>
                        <li>Require them to re-apply with corrections</li>
                        <li>Maintain record for audit purposes</li>
                    </ul>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <!-- Verification Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Verification Notes</h2>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">
                    <?php echo $action === 'approve' ? 'Approval Notes (Optional)' : 'Rejection Reason *'; ?>
                </label>
                <textarea name="verification_note" rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                          placeholder="Add notes about this decision..."
                          <?php echo $action === 'reject' ? 'required' : ''; ?>></textarea>
                <?php if ($action === 'reject'): ?>
                <p class="text-sm text-gray-500 mt-2">
                    Please provide a reason for rejection. This will be sent to the hospital.
                </p>
                <?php endif; ?>
            </div>
            
            <div class="flex justify-between items-center">
                <a href="manage_hospitals.php" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-600 hover:bg-<?php echo $action === 'approve' ? 'green' : 'red'; ?>-700 text-white font-medium rounded-lg transition">
                    <i class="ri-<?php echo $action === 'approve' ? 'check' : 'close'; ?>-line mr-2"></i>
                    <?php echo ucfirst($action); ?> Hospital
                </button>
            </div>
            
            <p class="text-sm text-gray-500 mt-4">
                <i class="ri-information-line mr-1"></i>
                This action will be logged in the audit trail and cannot be undone.
            </p>
        </form>
    </div>
    
    <!-- Additional Information -->
    <div class="mt-8 bg-gray-50 rounded-xl p-6">
        <h3 class="font-bold text-gray-900 mb-4">Additional Information</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Verification Checklist</h4>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <i class="ri-checkbox-circle-line text-green-500 mr-2"></i>
                        <span>Valid registration number</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-checkbox-circle-line text-green-500 mr-2"></i>
                        <span>Complete contact information</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-checkbox-circle-line text-green-500 mr-2"></i>
                        <span>Legitimate hospital location</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-checkbox-circle-<?php echo $hospital['license_number'] ? 'line text-green-500' : 'blank-line text-gray-400'; ?> mr-2"></i>
                        <span>Valid license documentation</span>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Next Steps After <?php echo $action === 'approve' ? 'Approval' : 'Rejection'; ?></h4>
                <ul class="space-y-2">
                    <?php if ($action === 'approve'): ?>
                    <li class="flex items-center">
                        <i class="ri-arrow-right-line text-gray-400 mr-2"></i>
                        <span>Hospital can request blood supplies</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-arrow-right-line text-gray-400 mr-2"></i>
                        <span>Hospital dashboard access enabled</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-arrow-right-line text-gray-400 mr-2"></i>
                        <span>Blood request notifications will be sent</span>
                    </li>
                    <?php else: ?>
                    <li class="flex items-center">
                        <i class="ri-arrow-right-line text-gray-400 mr-2"></i>
                        <span>Hospital notified via email</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-arrow-right-line text-gray-400 mr-2"></i>
                        <span>Hospital can reapply after corrections</span>
                    </li>
                    <li class="flex items-center">
                        <i class="ri-arrow-right-line text-gray-400 mr-2"></i>
                        <span>Record maintained for 90 days</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>