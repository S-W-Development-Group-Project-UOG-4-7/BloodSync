<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get report details - FIXED: Using reviewer's email since users table doesn't have name columns
$report = Database::fetch(
    "SELECT 
        mr.*,
        d.first_name,
        d.last_name,
        d.blood_type as donor_blood_type,
        d.nic,
        d.date_of_birth,
        d.contact_number,
        d.address,
        d.city,
        d.weight,
        d.last_donation_date,
        u.email,
        reviewer.email as reviewer_email,
        reviewer.email as reviewer_name  -- Using email since users table doesn't have name
     FROM medical_reports mr
     JOIN donors d ON mr.donor_id = d.id
     JOIN users u ON d.user_id = u.id
     LEFT JOIN users reviewer ON mr.reviewed_by = reviewer.id
     WHERE mr.id = :id",
    [':id' => $report_id]
);

if (!$report) {
    $_SESSION['error_message'] = 'Report not found.';
    header('Location: medical_reports.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $note = $_POST['note'] ?? '';
    
    if ($action === 'approve') {
        $status = 'approved';
        $message = 'Report approved successfully!';
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $message = 'Report rejected.';
    } elseif ($action === 'pending') {
        $status = 'pending';
        $message = 'Report marked as pending.';
    }
    
    $query = "UPDATE medical_reports SET 
              status = :status, 
              reviewed_by = :reviewed_by, 
              reviewed_at = CURRENT_TIMESTAMP,
              notes = CASE WHEN notes IS NULL OR notes = '' THEN :note ELSE notes || '\n' || :note END
              WHERE id = :id";
    
    Database::execute($query, [
        ':status' => $status,
        ':reviewed_by' => $user['id'],
        ':note' => "[" . date('Y-m-d H:i:s') . "] " . $user['email'] . ": " . $note,
        ':id' => $report_id
    ]);
    
    $_SESSION['success_message'] = $message;
    header('Location: medical_reports.php');
    exit();
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Medical Report Details</h1>
                <p class="text-gray-600">Review and manage this medical report</p>
            </div>
            <a href="medical_reports.php" class="text-gray-600 hover:text-gray-900">
                <i class="ri-arrow-left-line mr-1"></i> Back to Reports
            </a>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Report Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Report Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($report['title']); ?></h2>
                        <div class="flex items-center space-x-3 mt-2">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                <?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>
                            </span>
                            <span class="px-3 py-1 <?php 
                                echo $report['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                    ($report['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800'); 
                            ?> rounded-full text-sm font-medium">
                                <?php echo ucfirst($report['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Report Date</div>
                        <div class="font-medium text-gray-900"><?php echo date('F j, Y', strtotime($report['report_date'])); ?></div>
                    </div>
                </div>
                
                <!-- Report Notes -->
                <?php if (!empty($report['notes'])): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Report Notes</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <?php echo nl2br(htmlspecialchars($report['notes'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- File Preview -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Report File</h3>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mr-3">
                                    <i class="ri-file-text-line"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['file_path']); ?></p>
                                    <p class="text-sm text-gray-500">Uploaded: <?php echo date('F j, Y g:i A', strtotime($report['uploaded_at'])); ?></p>
                                </div>
                            </div>
                            <a href="<?php echo '../../uploads/medical_reports/' . htmlspecialchars($report['file_path']); ?>" 
                               target="_blank"
                               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                                <i class="ri-download-line mr-1"></i> View/Download
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Review History -->
                <?php if ($report['reviewed_by']): ?>
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Review History</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                    <i class="ri-user-line text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['reviewer_name'] ?? $report['reviewer_email']); ?></p>
                                    <p class="text-sm text-gray-600">Reviewed on <?php echo date('F j, Y g:i A', strtotime($report['reviewed_at'])); ?></p>
                                    <p class="text-sm text-gray-700 mt-2">Status changed to: 
                                        <span class="font-medium <?php echo $report['status'] === 'approved' ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo ucfirst($report['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Review Form -->
            <?php if ($report['status'] !== 'approved' && $report['status'] !== 'rejected'): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Review This Report</h2>
                    <form method="POST">
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-medium mb-2">Review Notes</label>
                            <textarea name="note" rows="4" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Add your review notes here..."></textarea>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" name="action" value="approve" 
                                    onclick="return confirm('Approve this report?');"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                                <i class="ri-check-line mr-2"></i> Approve Report
                            </button>
                            
                            <button type="submit" name="action" value="reject" 
                                    onclick="return confirm('Reject this report?');"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium">
                                <i class="ri-close-line mr-2"></i> Reject Report
                            </button>
                            
                            <button type="submit" name="action" value="pending" 
                                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg font-medium">
                                <i class="ri-time-line mr-2"></i> Mark as Pending
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column: Donor Info -->
        <div class="space-y-6">
            <!-- Donor Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Donor Information</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></p>
                        </div>
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                            <span class="text-xl font-bold"><?php echo htmlspecialchars($report['donor_blood_type']); ?></span>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">NIC</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['nic']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['email']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['contact_number']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Date of Birth</p>
                        <p class="font-medium text-gray-900"><?php echo date('F j, Y', strtotime($report['date_of_birth'])); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Weight</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['weight']); ?> kg</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Last Donation</p>
                        <p class="font-medium text-gray-900">
                            <?php echo $report['last_donation_date'] ? date('F j, Y', strtotime($report['last_donation_date'])) : 'Never'; ?>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Address</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($report['address']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($report['city']); ?></p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t">
                    <a href="../donor/dashboard.php?donor_id=<?php echo $report['donor_id']; ?>" 
                       target="_blank"
                       class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-3 rounded-lg font-medium">
                        <i class="ri-user-line mr-2"></i> View Donor Profile
                    </a>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="all_donors.php?search=<?php echo urlencode($report['nic']); ?>" 
                       class="flex items-center text-gray-700 hover:text-gray-900">
                        <i class="ri-user-search-line mr-2"></i> View All Reports from Donor
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($report['email']); ?>" 
                       class="flex items-center text-gray-700 hover:text-gray-900">
                        <i class="ri-mail-line mr-2"></i> Email Donor
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($report['contact_number']); ?>" 
                       class="flex items-center text-gray-700 hover:text-gray-900">
                        <i class="ri-phone-line mr-2"></i> Call Donor
                    </a>
                    <a href="medical_reports.php?blood_type=<?php echo urlencode($report['donor_blood_type']); ?>" 
                       class="flex items-center text-gray-700 hover:text-gray-900">
                        <i class="ri-drop-line mr-2"></i> View Same Blood Type Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>