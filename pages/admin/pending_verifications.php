<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();
require_once '../includes/header.php';
require_once 'admin_nav.php';

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query conditions
$conditions = [];
$params = [];

if ($filter === 'pending') {
    $conditions[] = "u.verification_status = 'pending'";
} elseif ($filter === 'approved') {
    $conditions[] = "u.verification_status = 'approved'";
} elseif ($filter === 'rejected') {
    $conditions[] = "u.verification_status = 'rejected'";
}

if (!empty($search)) {
    $conditions[] = "(d.first_name ILIKE :search OR d.last_name ILIKE :search OR u.email ILIKE :search OR d.nic ILIKE :search)";
    $params['search'] = "%{$search}%";
}

// Count total records
try {
    $countResult = Database::query("
        SELECT COUNT(*) as total
        FROM users u
        JOIN donors d ON u.id = d.user_id
        WHERE u.role = 'donor'
    ", $params);
    $totalRecords = $countResult->fetch()['total'];
    
    // Get donors with verification details
    $donors = Database::fetchAll("
        SELECT 
            u.id as user_id,
            u.email,
            u.verification_status,
            u.verified_at,
            u.verified_by,
            d.*,
            COALESCE((
                SELECT jsonb_agg(jsonb_build_object(
                    'id', mr.id,
                    'title', mr.title,
                    'report_date', mr.report_date,
                    'status', mr.status
                ))
                FROM medical_reports mr
                WHERE mr.donor_id = d.id
                LIMIT 5
            ), '[]'::jsonb) as medical_reports
        FROM users u
        JOIN donors d ON u.id = d.user_id
        WHERE u.role = 'donor'
        ORDER BY 
            CASE u.verification_status 
                WHEN 'pending' THEN 1
                WHEN 'rejected' THEN 2
                ELSE 3
            END,
            d.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    
    // Get verification stats
    $verificationStats = Database::fetchAll("
        SELECT 
            u.verification_status,
            COUNT(*) as count
        FROM users u
        WHERE u.role = 'donor'
        GROUP BY u.verification_status
    ");
    
    // Calculate pagination
    $totalPages = ceil($totalRecords / $limit);
    
} catch (Exception $e) {
    error_log('Pending verification error: ' . $e->getMessage());
    $donors = [];
    $totalRecords = 0;
    $totalPages = 1;
    $verificationStats = [];
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Donor Verifications</h1>
        <p class="text-gray-600">Manage and verify donor accounts</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <?php 
        $statsData = [
            'all' => ['title' => 'Total Donors', 'color' => 'blue', 'icon' => 'user-line', 'count' => $totalRecords],
            'pending' => ['title' => 'Pending', 'color' => 'yellow', 'icon' => 'time-line', 'count' => 0],
            'approved' => ['title' => 'Verified', 'color' => 'green', 'icon' => 'user-check-line', 'count' => 0],
            'rejected' => ['title' => 'Rejected', 'color' => 'red', 'icon' => 'user-unfollow-line', 'count' => 0]
        ];
        
        foreach ($verificationStats as $stat) {
            if (isset($statsData[$stat['verification_status']])) {
                $statsData[$stat['verification_status']]['count'] = $stat['count'];
            }
        }
        
        foreach ($statsData as $key => $stat): ?>
        <a href="?filter=<?php echo $key; ?>" 
           class="block <?php echo $filter === $key ? 'transform scale-105' : ''; ?>">
            <div class="bg-white border <?php echo $filter === $key ? 'border-' . $stat['color'] . '-300 shadow-md' : 'border-gray-200'; ?> rounded-xl p-6 hover:shadow-md transition-all">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-<?php echo $stat['color']; ?>-100 text-<?php echo $stat['color']; ?>-600 flex items-center justify-center mr-4">
                        <i class="ri-<?php echo $stat['icon']; ?> text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm"><?php echo $stat['title']; ?></p>
                        <h3 class="text-3xl font-bold text-gray-900"><?php echo $stat['count']; ?></h3>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <!-- Filter Tabs -->
            <div class="flex space-x-2">
                <a href="?filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    All
                </a>
                <a href="?filter=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-4 py-2 rounded-lg <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Pending
                </a>
                <a href="?filter=approved<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-4 py-2 rounded-lg <?php echo $filter === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Verified
                </a>
                <a href="?filter=rejected<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="px-4 py-2 rounded-lg <?php echo $filter === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Rejected
                </a>
            </div>

            <!-- Search -->
            <form method="GET" class="flex items-center space-x-2">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search donors..." 
                           class="border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-blue-500">
                    <i class="ri-search-line absolute right-3 top-3 text-gray-400"></i>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Search
                </button>
                <?php if (!empty($search)): ?>
                <a href="?filter=<?php echo $filter; ?>" class="text-gray-600 hover:text-gray-800">
                    Clear
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Donors Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Donor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact Info
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Medical Reports
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($donors)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="ri-user-search-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg">No donors found</p>
                            <?php if (!empty($search)): ?>
                            <p class="text-sm mt-2">Try adjusting your search or filter</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($donors as $donor): 
                        $medicalReports = json_decode($donor['medical_reports'], true) ?: [];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <!-- Donor Info -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12">
                                    <?php if (!empty($donor['profile_picture'])): ?>
                                    <img class="h-12 w-12 rounded-full object-cover" 
                                         src="<?php echo htmlspecialchars($donor['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>">
                                    <?php else: ?>
                                    <div class="h-12 w-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <i class="ri-user-line text-xl"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        NIC: <?php echo htmlspecialchars($donor['nic']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        ID: D-<?php echo str_pad($donor['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Contact Info -->
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($donor['email']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($donor['contact_number'] ?? 'N/A'); ?></div>
                            <div class="text-xs text-gray-400">
                                <?php echo htmlspecialchars($donor['address'] ?? 'No address'); ?>
                            </div>
                        </td>

                        <!-- Medical Reports -->
                        <td class="px-6 py-4">
                            <?php if (!empty($medicalReports)): 
                                $latestReport = $medicalReports[0];
                                $hasPendingReport = false;
                                foreach ($medicalReports as $report) {
                                    if (isset($report['status']) && $report['status'] === 'pending') {
                                        $hasPendingReport = true;
                                        break;
                                    }
                                }
                            ?>
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">
                                    <?php echo count($medicalReports); ?> report(s)
                                </span>
                                <?php if ($hasPendingReport): ?>
                                <span class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                    Pending Review
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Latest: <?php echo date('M d, Y', strtotime($latestReport['report_date'])); ?>
                            </div>
                            <a href="../donor/medical_reports.php?donor_id=<?php echo $donor['id']; ?>" 
                               class="text-xs text-blue-600 hover:text-blue-800 mt-1 inline-block">
                                View Reports
                            </a>
                        </td>
                        <?php else: ?>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            No medical reports
                        </td>
                        <?php endif; ?>

                        <!-- Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $statusColor = '';
                            if ($donor['verification_status'] === 'pending') {
                                $statusColor = 'bg-yellow-100 text-yellow-800';
                            } elseif ($donor['verification_status'] === 'approved') {
                                $statusColor = 'bg-green-100 text-green-800';
                            } elseif ($donor['verification_status'] === 'rejected') {
                                $statusColor = 'bg-red-100 text-red-800';
                            } else {
                                $statusColor = 'bg-gray-100 text-gray-800';
                            }
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                <?php echo ucfirst($donor['verification_status']); ?>
                            </span>
                            <?php if ($donor['verified_at']): ?>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo date('M d, Y', strtotime($donor['verified_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <!-- View Details -->
                                <a href="donor_details.php?id=<?php echo $donor['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900" 
                                   title="View Details">
                                    <i class="ri-eye-line"></i>
                                </a>
                                
                                <?php if ($donor['verification_status'] === 'pending'): ?>
                                <!-- Approve -->
                                <button onclick="showVerificationModal('approve', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                                        class="text-green-600 hover:text-green-900"
                                        title="Approve">
                                    <i class="ri-check-line"></i>
                                </button>
                                
                                <!-- Reject -->
                                <button onclick="showVerificationModal('reject', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                                        class="text-red-600 hover:text-red-900"
                                        title="Reject">
                                    <i class="ri-close-line"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($donor['verification_status'] === 'approved'): ?>
                                <!-- Re-verify -->
                                <button onclick="showVerificationModal('reverify', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Re-verify">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($donor['verification_status'] === 'rejected'): ?>
                                <!-- Review Again -->
                                <button onclick="showVerificationModal('review', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="Review Again">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="border-t border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium"><?php echo min($offset + 1, $totalRecords); ?></span>
                    to <span class="font-medium"><?php echo min($offset + $limit, $totalRecords); ?></span>
                    of <span class="font-medium"><?php echo $totalRecords; ?></span> donors
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 border <?php echo $page === $i ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?> rounded-lg">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                    <span class="px-3 py-2 text-gray-500">...</span>
                    <a href="?page=<?php echo $totalPages; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        <?php echo $totalPages; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Verification Modal -->
<div id="verificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-xl bg-white">
        <div class="mb-6">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900"></h3>
            <p id="modalSubtitle" class="text-gray-600 mt-2"></p>
        </div>
        
        <form id="verificationForm" method="POST" action="process_verification.php">
            <input type="hidden" id="verificationAction" name="action">
            <input type="hidden" id="verificationUserId" name="user_id">
            
            <!-- Reason for rejection/review -->
            <div id="reasonField" class="mb-6 hidden">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="reason">
                    Reason <span class="text-red-500">*</span>
                </label>
                <textarea id="reason" 
                          name="reason" 
                          rows="3" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                          placeholder="Provide a reason for this action..."></textarea>
                <p class="text-gray-500 text-xs mt-1">This will be visible to the donor.</p>
            </div>
            
            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
                    Notes (Optional)
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="2" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                          placeholder="Internal notes..."></textarea>
                <p class="text-gray-500 text-xs mt-1">For internal use only.</p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" 
                        onclick="closeVerificationModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        id="submitBtn"
                        class="px-4 py-2 rounded-lg text-white font-medium">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showVerificationModal(action, userId, userName) {
    const modal = document.getElementById('verificationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const reasonField = document.getElementById('reasonField');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('verificationForm');
    
    document.getElementById('verificationAction').value = action;
    document.getElementById('verificationUserId').value = userId;
    
    switch(action) {
        case 'approve':
            modalTitle.textContent = 'Approve Donor Verification';
            modalSubtitle.textContent = 'Approve verification for ' + userName + '?';
            submitBtn.textContent = 'Approve';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium';
            reasonField.classList.add('hidden');
            break;
            
        case 'reject':
            modalTitle.textContent = 'Reject Donor Verification';
            modalSubtitle.textContent = 'Reject verification for ' + userName + '?';
            submitBtn.textContent = 'Reject';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium';
            reasonField.classList.remove('hidden');
            break;
            
        case 'reverify':
            modalTitle.textContent = 'Re-verify Donor';
            modalSubtitle.textContent = 'Mark ' + userName + ' for re-verification?';
            submitBtn.textContent = 'Re-verify';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white font-medium';
            reasonField.classList.remove('hidden');
            break;
            
        case 'review':
            modalTitle.textContent = 'Review Donor';
            modalSubtitle.textContent = 'Review ' + userName + '\'s verification?';
            submitBtn.textContent = 'Review';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium';
            reasonField.classList.add('hidden');
            break;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeVerificationModal() {
    const modal = document.getElementById('verificationModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('verificationForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('verificationModal');
    if (event.target === modal) {
        closeVerificationModal();
    }
}

// Form validation
document.getElementById('verificationForm').addEventListener('submit', function(e) {
    const action = document.getElementById('verificationAction').value;
    const reasonField = document.getElementById('reasonField');
    
    if (!reasonField.classList.contains('hidden')) {
        const reason = document.getElementById('reason').value.trim();
        if (!reason) {
            e.preventDefault();
            alert('Please provide a reason for this action.');
            document.getElementById('reason').focus();
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>