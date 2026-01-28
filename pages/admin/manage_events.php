<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin - only admins can manage events
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

require_once '../includes/header.php';
require_once 'admin_nav.php';

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$whereClause = "1=1";
$params = [];

if ($status !== 'all') {
    $whereClause .= " AND e.approval_status = ?";  // Changed to approval_status
    $params[] = $status;
}

if (!empty($search)) {
    $whereClause .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.organizer_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
try {
    $totalCount = Database::fetch(
        "SELECT COUNT(*) as total 
         FROM events e
         WHERE $whereClause",
        $params
    )['total'];
} catch (Exception $e) {
    error_log('Count events error: ' . $e->getMessage());
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $limit);

// Get events with pagination - Added registered_count and fixed column names
try {
    $events = Database::fetchAll(
        "SELECT e.*,
                (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registered_count
         FROM events e
         WHERE $whereClause
         ORDER BY e.created_at DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );
} catch (Exception $e) {
    error_log('Fetch events error: ' . $e->getMessage());
    $events = [];
}

// Get counts for filter tabs - Updated to use approval_status
try {
    $counts = Database::fetchAll(
        "SELECT 
            (SELECT COUNT(*) FROM events WHERE approval_status = 'pending') as pending,
            (SELECT COUNT(*) FROM events WHERE approval_status = 'approved') as approved,
            (SELECT COUNT(*) FROM events WHERE approval_status = 'rejected') as rejected,
            (SELECT COUNT(*) FROM events) as total",
        []
    )[0];
} catch (Exception $e) {
    error_log('Counts error: ' . $e->getMessage());
    $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manage Events</h1>
        <p class="text-gray-600">Approve, reject, or manage events in the system</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Events</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $counts['total']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-yellow-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-600 text-sm">Pending</p>
                    <h3 class="text-2xl font-bold text-yellow-800 mt-1"><?php echo $counts['pending']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <i class="ri-time-line text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-green-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-600 text-sm">Approved</p>
                    <h3 class="text-2xl font-bold text-green-800 mt-1"><?php echo $counts['approved']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <i class="ri-check-line text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-red-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-600 text-sm">Rejected</p>
                    <h3 class="text-2xl font-bold text-red-800 mt-1"><?php echo $counts['rejected']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-close-line text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
                <a href="?status=all" 
                   class="px-4 py-2 rounded-lg <?php echo $status === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    All (<?php echo $counts['total']; ?>)
                </a>
                <a href="?status=pending" 
                   class="px-4 py-2 rounded-lg <?php echo $status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Pending (<?php echo $counts['pending']; ?>)
                </a>
                <a href="?status=approved" 
                   class="px-4 py-2 rounded-lg <?php echo $status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Approved (<?php echo $counts['approved']; ?>)
                </a>
                <a href="?status=rejected" 
                   class="px-4 py-2 rounded-lg <?php echo $status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Rejected (<?php echo $counts['rejected']; ?>)
                </a>
            </div>

            <!-- Search and Create Button -->
            <div class="flex items-center gap-4">
                <form method="GET" class="flex items-center">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Search events..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <?php if ($status !== 'all'): ?>
                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                    <?php endif; ?>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="?status=<?php echo $status; ?>" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
                
                <a href="create_event.php" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center">
                    <i class="ri-add-line mr-2"></i> Create Event
                </a>
            </div>
        </div>
    </div>

    <!-- Events Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <?php if (empty($events)): ?>
            <div class="text-center py-12">
                <i class="ri-calendar-line text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No events found</p>
                <?php if (!empty($search)): ?>
                    <p class="text-gray-400 mt-2">Try a different search term</p>
                <?php elseif ($status !== 'all'): ?>
                    <p class="text-gray-400 mt-2">No <?php echo $status; ?> events found</p>
                <?php endif; ?>
                <a href="create_event.php" class="inline-block mt-4 px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    <i class="ri-add-line mr-2"></i> Create New Event
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organizer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($events as $event): 
                            $statusColor = match($event['approval_status']) {  // Changed to approval_status
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            
                            $statusIcon = match($event['approval_status']) {  // Changed to approval_status
                                'pending' => 'ri-time-line',
                                'approved' => 'ri-check-line',
                                'rejected' => 'ri-close-line',
                                default => 'ri-question-line'
                            };
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-start space-x-4">
                                    <?php if (!empty($event['image'])): 
                                        $image_src = $event['image'];
                                        if (!preg_match('/^(https?:|\/)/', $image_src)) {
                                            $image_src = '/' . ltrim($image_src, '/');
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                        alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                        class="w-16 h-16 rounded-lg object-cover">
                                    <?php else: ?>
                                        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                            <i class="ri-calendar-event-line text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($event['title']); ?></h4>
                                        <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?php echo htmlspecialchars($event['description'] ?? ''); ?></p>
                                        <div class="flex items-center mt-2 text-sm text-gray-600">
                                            <i class="ri-map-pin-line mr-1"></i>
                                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($event['organizer_name']); ?></p>
                                    <p class="text-sm text-gray-500">NIC: <?php echo htmlspecialchars($event['organizer_nic']); ?></p>
                                    <?php if (!empty($event['organizer_contact'])): ?>
                                    <p class="text-sm text-gray-500">Phone: <?php echo htmlspecialchars($event['organizer_contact']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Created: <?php echo date('M d, Y', strtotime($event['created_at'])); ?>
                                    </p>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo date('h:i A', strtotime($event['event_start_time'])); ?> -  <!-- Fixed field name -->
                                        <?php echo date('h:i A', strtotime($event['event_end_time'])); ?>  <!-- Fixed field name -->
                                    </p>
                                    <?php if (!empty($event['registration_deadline'])): ?>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Reg. until: <?php echo date('M d', strtotime($event['registration_deadline'])); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                    <i class="<?php echo $statusIcon; ?> mr-1"></i>
                                    <?php echo ucfirst($event['approval_status']); ?>  <!-- Changed to approval_status -->
                                </span>
                                <?php if ($event['approval_status'] === 'approved' && !empty($event['approved_by'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        By: <?php echo htmlspecialchars($event['approved_by']); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="showEventDetails(<?php echo htmlspecialchars(json_encode($event)); ?>);"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View Details">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    
                                    <?php if ($event['approval_status'] === 'pending'): ?>  <!-- Changed to approval_status -->
                                    <button onclick="approveEvent(<?php echo $event['id']; ?>);"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Approve">
                                        <i class="ri-check-line"></i>
                                    </button>
                                    
                                    <button onclick="showRejectForm(<?php echo $event['id']; ?>);"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Reject">
                                        <i class="ri-close-line"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['title'])); ?>');"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo min($offset + 1, $totalCount); ?></span> to 
                        <span class="font-medium"><?php echo min($offset + count($events), $totalCount); ?></span> of 
                        <span class="font-medium"><?php echo $totalCount; ?></span> events
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>"
                               class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-3 py-1 bg-blue-600 text-white rounded-lg"><?php echo $i; ?></span>
                            <?php elseif ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                                   class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="px-3 py-1 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>"
                               class="px-3 py-1 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
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

<!-- Event Details Modal -->
<div id="eventDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-start mb-6">
                <h3 class="text-2xl font-bold text-gray-900" id="modalTitle"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
            
            <div class="space-y-6">
                <!-- Event Image -->
                <div id="modalImage" class="rounded-lg overflow-hidden"></div>
                
                <!-- Event Details -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Event Information</h4>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="ri-calendar-line text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Date</p>
                                    <p class="font-medium" id="modalDate"></p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-time-line text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Time</p>
                                    <p class="font-medium" id="modalTime"></p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-map-pin-line text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Location</p>
                                    <p class="font-medium" id="modalLocation"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Organizer Information</h4>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="ri-user-line text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Organizer</p>
                                    <p class="font-medium" id="modalOrganizer"></p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-id-card-line text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-500">NIC</p>
                                    <p class="font-medium" id="modalNIC"></p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="ri-phone-line text-gray-400 mr-3"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <p class="font-medium" id="modalPhone"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Event Description -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700" id="modalDescription"></p>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600">Max Participants</p>
                        <p class="text-xl font-bold text-blue-800" id="modalMaxParticipants"></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-600">Registered</p>
                        <p class="text-xl font-bold text-green-800" id="modalRegistered"></p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm text-purple-600">Registration Deadline</p>
                        <p class="text-xl font-bold text-purple-800" id="modalDeadline"></p>
                    </div>
                </div>
                
                <!-- Status Info -->
                <div id="modalStatusInfo" class="bg-gray-50 rounded-lg p-4"></div>
            </div>
            
            <div class="mt-8 flex justify-end">
                <button onclick="closeModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Event Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <form id="rejectForm" method="POST" action="process_event.php">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Reject Event</h3>
                <p class="text-gray-600 mb-4">Please provide a reason for rejecting this event:</p>
                
                <input type="hidden" name="action" value="reject">
                <input type="hidden" id="rejectEventId" name="event_id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Rejection Reason</label>
                    <textarea name="rejection_reason" 
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Enter reason for rejection..."
                              required></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Reject Event
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Event Details Modal
function showEventDetails(event) {
    document.getElementById('modalTitle').textContent = event.title;
    
    // Set image or placeholder
    const modalImage = document.getElementById('modalImage');
    if (event.image) {
        modalImage.innerHTML = `<img src="${event.image}" alt="${event.title}" class="w-full h-48 object-cover rounded-lg">`;
    } else {
        modalImage.innerHTML = `<div class="w-full h-48 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
            <i class="ri-calendar-event-line text-6xl"></i>
        </div>`;
    }
    
    // Set other details
    document.getElementById('modalDate').textContent = new Date(event.event_date).toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
    document.getElementById('modalTime').textContent = `${formatTime(event.event_start_time)} - ${formatTime(event.event_end_time)}`;  // Fixed field names
    document.getElementById('modalLocation').textContent = event.location;
    document.getElementById('modalOrganizer').textContent = event.organizer_name;
    document.getElementById('modalNIC').textContent = event.organizer_nic;
    document.getElementById('modalPhone').textContent = event.organizer_contact || 'Not provided';
    document.getElementById('modalDescription').textContent = event.description || 'No description provided.';
    document.getElementById('modalMaxParticipants').textContent = event.target_donations || 'Unlimited';
    document.getElementById('modalRegistered').textContent = event.registered_count || 0;
    document.getElementById('modalDeadline').textContent = event.registration_deadline ? 
        new Date(event.registration_deadline).toLocaleDateString() : 'No deadline';
    
    // Set status info
    const statusInfo = document.getElementById('modalStatusInfo');
    let statusHTML = `<h4 class="font-medium text-gray-900 mb-2">Status Information</h4>`;
    
    if (event.approval_status === 'approved' && event.approved_by) {
        statusHTML += `<p class="text-green-700"><i class="ri-check-line mr-2"></i>Approved by ${event.approved_by} on ${new Date(event.approved_at).toLocaleDateString()}</p>`;
    } else if (event.approval_status === 'rejected' && event.rejected_by) {
        statusHTML += `<p class="text-red-700"><i class="ri-close-line mr-2"></i>Rejected by ${event.rejected_by} on ${new Date(event.rejected_at).toLocaleDateString()}</p>`;
        if (event.rejection_reason) {
            statusHTML += `<p class="text-gray-700 mt-2"><strong>Reason:</strong> ${event.rejection_reason}</p>`;
        }
    } else if (event.approval_status === 'pending') {
        statusHTML += `<p class="text-yellow-700"><i class="ri-time-line mr-2"></i>Pending approval</p>`;
    }
    
    statusInfo.innerHTML = statusHTML;
    
    // Show modal
    document.getElementById('eventDetailsModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('eventDetailsModal').classList.add('hidden');
}

// Reject Event Modal
function showRejectForm(eventId) {
    document.getElementById('rejectEventId').value = eventId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Approve Event
function approveEvent(eventId) {
    if (confirm('Are you sure you want to approve this event?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_event.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'approve';
        form.appendChild(actionInput);
        
        const eventIdInput = document.createElement('input');
        eventIdInput.type = 'hidden';
        eventIdInput.name = 'event_id';
        eventIdInput.value = eventId;
        form.appendChild(eventIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete Event
function deleteEvent(eventId, eventTitle) {
    if (confirm(`Are you sure you want to delete the event "${eventTitle}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_event.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const eventIdInput = document.createElement('input');
        eventIdInput.type = 'hidden';
        eventIdInput.name = 'event_id';
        eventIdInput.value = eventId;
        form.appendChild(eventIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Helper function to format time
function formatTime(timeString) {
    if (!timeString) return 'N/A';
    const time = new Date(`2000-01-01T${timeString}`);
    return time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

// Close modals on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
        closeRejectModal();
    }
});

// Close modals when clicking outside
document.getElementById('eventDetailsModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'eventDetailsModal') {
        closeModal();
    }
});

document.getElementById('rejectModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'rejectModal') {
        closeRejectModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>