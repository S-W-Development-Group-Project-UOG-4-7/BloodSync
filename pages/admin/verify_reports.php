<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = $_POST['report_id'] ?? null;
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'approve' or 'reject'
    $note = $_POST['note'] ?? '';
    $adminId = $user['user_id'];
    
    if ($reportId && $userId && $action) {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();
            
            // First, get the donor_id for this user
            $donorQuery = "SELECT id FROM donors WHERE user_id = :user_id";
            $donorStmt = $pdo->prepare($donorQuery);
            $donorStmt->execute(['user_id' => $userId]);
            $donor = $donorStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donor) {
                throw new Exception("Donor not found for this user");
            }
            
            $donorId = $donor['id'];
            
            // Determine status and verification values
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $isVerified = ($action === 'approve') ? true : false;
            
            // Update user verification status
            $sql = "UPDATE users 
                    SET is_verified = :is_verified, 
                        verification_status = :verification_status, 
                        verification_notes = :verification_notes,
                        verified_at = NOW(),
                        verified_by = :admin_id
                    WHERE id = :user_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'is_verified' => $isVerified,
                'verification_status' => $status,
                'verification_notes' => $note,
                'admin_id' => $adminId,
                'user_id' => $userId
            ]);
            
            // Update medical report status
            $sql = "UPDATE medical_reports 
                    SET status = :status, 
                        reviewed_by = :admin_id,
                        reviewed_at = NOW(),
                        notes = :notes
                    WHERE id = :report_id AND donor_id = :donor_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'admin_id' => $adminId,
                'notes' => $note,
                'report_id' => $reportId,
                'donor_id' => $donorId
            ]);
            
            // If approved, update donor's eligibility
            if ($action === 'approve') {
                $sql = "UPDATE donors SET is_eligible = TRUE WHERE id = :donor_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['donor_id' => $donorId]);
            } else {
                $sql = "UPDATE donors SET is_eligible = FALSE WHERE id = :donor_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['donor_id' => $donorId]);
            }
            
            // Create notification for user
            $notificationTitle = ($action === 'approve') 
                ? 'Account Verified Successfully' 
                : 'Medical Report Review Required';
            
            $notificationMessage = ($action === 'approve') 
                ? 'Your medical report has been approved and your account is now verified. You can now access all features of BloodSync.'
                : 'Your medical report requires attention: ' . $note;
            
            $sql = "INSERT INTO notifications (user_id, type, title, message, metadata) 
                    VALUES (:user_id, 'verification', :title, :message, :metadata)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'metadata' => json_encode([
                    'status' => $status,
                    'report_id' => $reportId,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ])
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = "Report " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
            header('Location: verify_reports.php');
            exit();
            
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

// Get pending medical reports - FIXED: Using report_date instead of created_at
$pendingReports = Database::fetchAll(
    "SELECT mr.*, 
            d.user_id, u.email, u.verification_status,
            d.first_name, d.last_name, d.blood_type, d.nic, d.contact_number,
            d.date_of_birth, d.city, u.is_verified
     FROM medical_reports mr
     JOIN donors d ON mr.donor_id = d.id
     JOIN users u ON d.user_id = u.id
     WHERE mr.status = 'pending' OR mr.status IS NULL
     ORDER BY mr.report_date DESC",  // CHANGED: created_at → report_date
    []
);

// Get recently reviewed reports - FIXED: Using report_date instead of created_at
$reviewedReports = Database::fetchAll(
    "SELECT mr.*, 
            d.user_id, u.email, u.verification_status,
            d.first_name, d.last_name, d.blood_type,
            au.email as reviewer_email
     FROM medical_reports mr
     JOIN donors d ON mr.donor_id = d.id
     JOIN users u ON d.user_id = u.id
     LEFT JOIN users au ON mr.reviewed_by = au.id
     WHERE mr.status IN ('approved', 'rejected')
     ORDER BY mr.reviewed_at DESC
     LIMIT 10",
    []
);

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Medical Report Verification</h1>
        <p class="text-gray-600">Review and verify donor medical reports</p>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-700 text-sm">Pending Reports</p>
                    <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo count($pendingReports); ?></h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <i class="ri-time-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-700 text-sm">Today's Reviews</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">
                        <?php 
                        $today = date('Y-m-d');
                        $todayCount = Database::fetch(
                            "SELECT COUNT(*) as count FROM medical_reports 
                             WHERE DATE(reviewed_at) = :today",
                            ['today' => $today]
                        )['count'];
                        echo $todayCount;
                        ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-red-700 text-sm">Total Verified</p>
                    <h3 class="text-3xl font-bold text-red-800 mt-2">
                        <?php
                        $verifiedCount = Database::fetch(
                            "SELECT COUNT(*) as count FROM users 
                             WHERE verification_status = 'approved' AND role = 'donor'",
                            []
                        )['count'];
                        echo $verifiedCount;
                        ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-user-check-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Pending Reports -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Pending Medical Reports</h2>
                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo count($pendingReports); ?> waiting
                    </span>
                </div>
                
                <?php if (empty($pendingReports)): ?>
                    <div class="text-center py-12">
                        <i class="ri-check-double-line text-5xl text-green-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">All Clear!</h3>
                        <p class="text-gray-600">No pending medical reports to review.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pendingReports as $report): ?>
                        <div class="border border-gray-200 rounded-xl p-6 hover:border-red-300 transition">
                            <!-- Donor Info -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">
                                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                    </h3>
                                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600">
                                        <span><i class="ri-mail-line mr-1"></i> <?php echo htmlspecialchars($report['email']); ?></span>
                                        <span><i class="ri-phone-line mr-1"></i> <?php echo htmlspecialchars($report['contact_number']); ?></span>
                                    </div>
                                    <div class="flex items-center mt-2">
                                        <span class="text-xs px-2 py-1 rounded <?php echo $report['is_verified'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $report['is_verified'] ? 'Verified' : 'Not Verified'; ?>
                                        </span>
                                        <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-800 ml-2">
                                            Age: <?php echo floor((time() - strtotime($report['date_of_birth'])) / 31556926); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium mb-2">
                                        <?php echo htmlspecialchars($report['blood_type']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        NIC: <?php echo htmlspecialchars($report['nic']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        City: <?php echo htmlspecialchars($report['city']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Report Details -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            <i class="ri-file-line mr-2"></i>
                                            <?php echo htmlspecialchars($report['title']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Report Date: <?php echo date('F j, Y', strtotime($report['report_date'])); ?> <!-- CHANGED: created_at → report_date -->
                                        </p>
                                        <?php if (!empty($report['report_type'])): ?>
                                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded mt-1 inline-block">
                                                <?php echo htmlspecialchars($report['report_type']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($report['file_path'])): ?>
                                    <a href="../admin/view_report.php?id=<?php echo $report['id']; ?>" 
                                       target="_blank"
                                       class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="ri-external-link-line mr-2"></i> View Report
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($report['notes'])): ?>
                                <div class="mt-3 p-3 bg-white rounded border border-gray-200">
                                    <p class="text-sm text-gray-700">
                                        <strong>Notes:</strong> <?php echo htmlspecialchars($report['notes']); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $report['user_id']; ?>">
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Review Notes (Optional)</label>
                                    <textarea name="note" rows="2" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                              placeholder="Add notes for the donor..."></textarea>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="submit" name="action" value="approve"
                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 rounded-lg transition flex items-center justify-center">
                                        <i class="ri-check-line mr-2"></i> Approve & Verify
                                    </button>
                                    
                                    <button type="button" 
                                            onclick="showRejectModal('<?php echo $report['id']; ?>', '<?php echo $report['user_id']; ?>', '<?php echo htmlspecialchars(addslashes($report['first_name'] . ' ' . $report['last_name'])); ?>')"
                                            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-3 rounded-lg transition flex items-center justify-center">
                                        <i class="ri-close-line mr-2"></i> Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Recent Activity -->
        <div>
            <!-- Recent Reviews -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Reviews</h2>
                
                <?php if (empty($reviewedReports)): ?>
                    <p class="text-gray-500 text-center py-4">No recent reviews</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($reviewedReports as $review): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($review['email']); ?>
                                    </p>
                                </div>
                                <span class="px-2 py-1 rounded text-sm font-medium 
                                    <?php echo $review['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($review['status']); ?>
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-700 mb-1">
                                <i class="ri-file-line mr-1"></i>
                                <?php echo htmlspecialchars($review['title']); ?>
                            </p>
                            
                            <?php if (!empty($review['reviewed_at'])): ?>
                            <p class="text-xs text-gray-500 mb-1">
                                Reviewed: <?php echo date('M j, Y', strtotime($review['reviewed_at'])); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($review['notes']): ?>
                                <p class="text-sm text-gray-600 mt-2">
                                    <i class="ri-chat-1-line mr-1"></i>
                                    <?php echo htmlspecialchars(substr($review['notes'], 0, 100)); ?>
                                    <?php echo strlen($review['notes']) > 100 ? '...' : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Stats -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Verification Stats</h2>
                <div class="space-y-3">
                    <?php
                    $stats = Database::fetchAll(
                        "SELECT 
                            COUNT(CASE WHEN u.verification_status = 'approved' THEN 1 END) as approved,
                            COUNT(CASE WHEN u.verification_status = 'rejected' THEN 1 END) as rejected,
                            COUNT(CASE WHEN u.verification_status = 'pending' THEN 1 END) as pending,
                            COUNT(CASE WHEN u.verification_status IS NULL THEN 1 END) as unsubmitted
                         FROM users u
                         JOIN donors d ON u.id = d.user_id
                         WHERE u.role = 'donor'",
                        []
                    )[0];
                    ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Approved Donors</span>
                        <span class="font-medium text-green-600"><?php echo $stats['approved']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Rejected</span>
                        <span class="font-medium text-red-600"><?php echo $stats['rejected']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Pending Review</span>
                        <span class="font-medium text-yellow-600"><?php echo $stats['pending']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">No Report Submitted</span>
                        <span class="font-medium text-gray-600"><?php echo $stats['unsubmitted']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Reject Medical Report</h3>
            <p class="text-gray-600 mb-6" id="rejectUserName"></p>
            
            <form id="rejectForm" method="POST">
                <input type="hidden" name="report_id" id="rejectReportId">
                <input type="hidden" name="user_id" id="rejectUserId">
                <input type="hidden" name="action" value="reject">
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Reason for Rejection *</label>
                    <textarea name="note" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                              placeholder="Explain why the report was rejected..."></textarea>
                    <p class="text-sm text-gray-500 mt-1">This will be shown to the donor</p>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-3 rounded-lg transition">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(reportId, userId, userName) {
    document.getElementById('rejectReportId').value = reportId;
    document.getElementById('rejectUserId').value = userId;
    document.getElementById('rejectUserName').textContent = 'Reject medical report for ' + userName + '?';
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Close modal on outside click
document.addEventListener('click', (e) => {
    if (e.target.id === 'rejectModal') {
        closeModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>