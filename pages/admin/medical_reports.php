<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Get donor ID from query parameter
$donor_id = isset($_GET['donor_id']) ? intval($_GET['donor_id']) : 0;

if (!$donor_id) {
    $_SESSION['error'] = 'Donor ID is required.';
    header('Location: pending_verifications.php');
    exit;
}

// Get donor info for display
try {
    $donor = Database::fetch("
        SELECT d.*, u.email, u.verification_status
        FROM donors d
        JOIN users u ON d.user_id = u.id
        WHERE d.id = :id
    ", ['id' => $donor_id]);

    if (!$donor) {
        $_SESSION['error'] = 'Donor not found.';
        header('Location: pending_verifications.php');
        exit;
    }

    // Get pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Get search/filter parameters
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? 'all';
    $type_filter = $_GET['type'] ?? 'all';

    // Build query conditions
    $conditions = ["mr.donor_id = :donor_id"];
    $params = ['donor_id' => $donor_id];

    if (!empty($search)) {
        $conditions[] = "(mr.title ILIKE :search OR mr.notes ILIKE :search)";
        $params['search'] = "%{$search}%";
    }

    if ($status_filter !== 'all') {
        $conditions[] = "mr.status = :status";
        $params['status'] = $status_filter;
    }

    if ($type_filter !== 'all') {
        $conditions[] = "mr.report_type = :type";
        $params['type'] = $type_filter;
    }

    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Count total records
    $count_sql = "SELECT COUNT(*) as total FROM medical_reports mr $where_clause";
    $count_result = Database::query($count_sql, $params);
    $total_records = $count_result->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    // Get medical reports with admin info
    $reports = Database::fetchAll("
        SELECT 
            mr.*,
            u.email as reviewed_by_email,
            u2.email as uploaded_by_email
        FROM medical_reports mr
        LEFT JOIN users u ON mr.reviewed_by = u.id
        LEFT JOIN users u2 ON mr.uploaded_by = u2.id
        $where_clause
        ORDER BY mr.report_date DESC, mr.uploaded_at DESC
        LIMIT $limit OFFSET $offset
    ", $params);

    // Get stats
    $stats = Database::fetch("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM medical_reports 
        WHERE donor_id = :donor_id
    ", ['donor_id' => $donor_id]);

    // Get distinct report types for filter
    $report_types = Database::fetchAll("
        SELECT DISTINCT report_type 
        FROM medical_reports 
        WHERE donor_id = :donor_id 
        AND report_type IS NOT NULL 
        ORDER BY report_type
    ", ['donor_id' => $donor_id]);

} catch (Exception $e) {
    error_log('Medical reports error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error loading medical reports.';
    header('Location: donor_details.php?id=' . $donor_id);
    exit;
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header with Back Button -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Medical Reports</h1>
                <p class="text-gray-600">
                    <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?> 
                    (<?php echo $donor['blood_type']; ?>)
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="donor_details.php?id=<?php echo $donor_id; ?>" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-lg transition">
                    Back to Donor Details
                </a>
                <a href="pending_verifications.php" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-lg transition">
                    Back to Verifications
                </a>
            </div>
        </div>
    </div>

    <!-- Status Alert -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-4">
                    <i class="ri-file-text-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Reports</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-4">
                    <i class="ri-time-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Pending</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['pending']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-4">
                    <i class="ri-check-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Approved</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['approved']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-red-100 text-red-600 flex items-center justify-center mr-4">
                    <i class="ri-close-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Rejected</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['rejected']; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="space-y-4">
            <input type="hidden" name="donor_id" value="<?php echo $donor_id; ?>">
            
            <div class="flex flex-col md:flex-row md:items-center md:space-x-4 space-y-4 md:space-y-0">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search reports..." 
                               class="border border-gray-300 rounded-lg px-4 py-2 pr-10 w-full focus:outline-none focus:border-blue-500">
                        <i class="ri-search-line absolute right-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" 
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <select name="type" 
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach ($report_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['report_type']); ?>"
                                <?php echo $type_filter === $type['report_type'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $type['report_type'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg transition">
                        Filter
                    </button>
                    <a href="medical_reports.php?donor_id=<?php echo $donor_id; ?>" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-2 rounded-lg transition">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <?php if (empty($reports)): ?>
        <div class="p-12 text-center">
            <i class="ri-file-search-line text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No medical reports found</h3>
            <p class="text-gray-500">
                <?php if (!empty($search) || $status_filter !== 'all' || $type_filter !== 'all'): ?>
                Try adjusting your search or filter criteria.
                <?php else: ?>
                This donor hasn't uploaded any medical reports yet.
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Report Details
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type & Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Review Info
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reports as $report): 
                        $status_color = $report['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                       ($report['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                       'bg-yellow-100 text-yellow-800');
                    ?>
                    <tr class="hover:bg-gray-50">
                        <!-- Report Details -->
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($report['title']); ?>
                            </div>
                            <?php if ($report['notes']): ?>
                            <div class="text-sm text-gray-500 mt-1">
                                <?php echo nl2br(htmlspecialchars(substr($report['notes'], 0, 100))); ?>
                                <?php if (strlen($report['notes']) > 100): ?>...<?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- Type & Date -->
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                <?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($report['report_date'])); ?>
                            </div>
                            <div class="text-xs text-gray-400">
                                Uploaded: <?php echo date('M d, Y', strtotime($report['uploaded_at'])); ?>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                <?php echo ucfirst($report['status']); ?>
                            </span>
                        </td>

                        <!-- Review Info -->
                        <td class="px-6 py-4">
                            <?php if ($report['reviewed_by_email']): ?>
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($report['reviewed_by_email']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo $report['reviewed_at'] ? date('M d, Y', strtotime($report['reviewed_at'])) : ''; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-sm text-gray-400">Not reviewed</div>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4">
                            <div class="flex space-x-3">
                                <?php if ($report['file_path']): ?>
                                <a href="<?php echo htmlspecialchars($report['file_path']); ?>" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-900"
                                   title="View File">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($report['status'] === 'pending'): ?>
                                <button onclick="showReviewModal('approve', <?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['title']); ?>')"
                                        class="text-green-600 hover:text-green-900"
                                        title="Approve">
                                    <i class="ri-check-line"></i>
                                </button>
                                
                                <button onclick="showReviewModal('reject', <?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['title']); ?>')"
                                        class="text-red-600 hover:text-red-900"
                                        title="Reject">
                                    <i class="ri-close-line"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($report['status'] === 'approved' || $report['status'] === 'rejected'): ?>
                                <button onclick="showReviewModal('review', <?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['title']); ?>')"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Review Again">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="border-t border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium"><?php echo min($offset + 1, $total_records); ?></span>
                    to <span class="font-medium"><?php echo min($offset + $limit, $total_records); ?></span>
                    of <span class="font-medium"><?php echo $total_records; ?></span> reports
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?donor_id=<?php echo $donor_id; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?donor_id=<?php echo $donor_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>" 
                       class="px-3 py-2 border <?php echo $page === $i ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?> rounded-lg">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                    <span class="px-3 py-2 text-gray-500">...</span>
                    <a href="?donor_id=<?php echo $donor_id; ?>&page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        <?php echo $total_pages; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?donor_id=<?php echo $donor_id; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-xl bg-white">
        <div class="mb-6">
            <h3 id="reviewModalTitle" class="text-xl font-bold text-gray-900"></h3>
            <p id="reviewModalSubtitle" class="text-gray-600 mt-2"></p>
        </div>
        
        <form id="reviewForm" method="POST" action="process_report_review.php">
            <input type="hidden" id="reviewAction" name="action">
            <input type="hidden" id="reviewReportId" name="report_id">
            <input type="hidden" name="donor_id" value="<?php echo $donor_id; ?>">
            
            <!-- Reason for rejection/review -->
            <div id="reviewReasonField" class="mb-6 hidden">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="review_reason">
                    Reason <span class="text-red-500">*</span>
                </label>
                <textarea id="review_reason" 
                          name="reason" 
                          rows="3" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                          placeholder="Provide a reason for this action..."></textarea>
                <p class="text-gray-500 text-xs mt-1">This will be visible to the donor.</p>
            </div>
            
            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="review_notes">
                    Notes (Optional)
                </label>
                <textarea id="review_notes" 
                          name="notes" 
                          rows="2" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                          placeholder="Internal notes..."></textarea>
                <p class="text-gray-500 text-xs mt-1">For internal use only.</p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" 
                        onclick="closeReviewModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        id="reviewSubmitBtn"
                        class="px-4 py-2 rounded-lg text-white font-medium">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showReviewModal(action, reportId, reportTitle) {
    const modal = document.getElementById('reviewModal');
    const modalTitle = document.getElementById('reviewModalTitle');
    const modalSubtitle = document.getElementById('reviewModalSubtitle');
    const reasonField = document.getElementById('reviewReasonField');
    const submitBtn = document.getElementById('reviewSubmitBtn');
    
    document.getElementById('reviewAction').value = action;
    document.getElementById('reviewReportId').value = reportId;
    
    switch(action) {
        case 'approve':
            modalTitle.textContent = 'Approve Medical Report';
            modalSubtitle.textContent = 'Approve report: ' + reportTitle;
            submitBtn.textContent = 'Approve';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium';
            reasonField.classList.add('hidden');
            break;
            
        case 'reject':
            modalTitle.textContent = 'Reject Medical Report';
            modalSubtitle.textContent = 'Reject report: ' + reportTitle;
            submitBtn.textContent = 'Reject';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium';
            reasonField.classList.remove('hidden');
            break;
            
        case 'review':
            modalTitle.textContent = 'Review Medical Report';
            modalSubtitle.textContent = 'Review report: ' + reportTitle;
            submitBtn.textContent = 'Review';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white font-medium';
            reasonField.classList.remove('hidden');
            break;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('reviewForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reviewModal');
    if (event.target === modal) {
        closeReviewModal();
    }
}

// Form validation
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    const action = document.getElementById('reviewAction').value;
    const reasonField = document.getElementById('reviewReasonField');
    
    if (!reasonField.classList.contains('hidden')) {
        const reason = document.getElementById('review_reason').value.trim();
        if (!reason) {
            e.preventDefault();
            alert('Please provide a reason for this action.');
            document.getElementById('review_reason').focus();
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
