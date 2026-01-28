<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');
$user = Auth::getUser();

require_once '../includes/header.php';
require_once 'admin_nav.php';

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    try {
        $appointmentId = $_GET['id'];
        
        switch ($_GET['action']) {
            case 'complete':
                Database::execute(
                    "UPDATE appointments SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                    ['id' => $appointmentId]
                );
                $_SESSION['success_message'] = "Appointment marked as completed!";
                break;
                
            case 'cancel':
                if (isset($_POST['cancellation_reason'])) {
                    Database::execute(
                        "UPDATE appointments SET 
                         status = 'cancelled', 
                         cancelled_by = :cancelled_by,
                         cancelled_at = CURRENT_TIMESTAMP,
                         cancellation_reason = :reason,
                         updated_at = CURRENT_TIMESTAMP 
                         WHERE id = :id",
                        [
                            'id' => $appointmentId,
                            'cancelled_by' => $user['id'],
                            'reason' => $_POST['cancellation_reason']
                        ]
                    );
                    
                    // Notify donor
                    $appointment = Database::fetch(
                        "SELECT a.*, u.email, d.first_name, d.last_name 
                         FROM appointments a
                         JOIN donors d ON a.donor_id = d.id
                         JOIN users u ON d.user_id = u.id
                         WHERE a.id = :id",
                        ['id' => $appointmentId]
                    );
                    
                    if ($appointment) {
                        $to = $appointment['email'];
                        $subject = "Appointment Cancelled - BloodSync";
                        $message = "Dear " . $appointment['first_name'] . ",\n\n";
                        $message .= "Your appointment scheduled for " . $appointment['appointment_date'] . " has been cancelled.\n";
                        $message .= "Reason: " . $_POST['cancellation_reason'] . "\n\n";
                        $message .= "Please contact support if you need to reschedule.\n\n";
                        $message .= "Best regards,\nBloodSync Team";
                        
                        mail($to, $subject, $message);
                    }
                    
                    $_SESSION['success_message'] = "Appointment cancelled successfully!";
                }
                break;
        }
        
        header('Location: manage_appointments.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating appointment: " . $e->getMessage();
    }
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "a.status = :status";
    $params['status'] = $status;
}

if ($date) {
    $where[] = "a.appointment_date = :date";
    $params['date'] = $date;
}

if ($search) {
    $where[] = "(d.first_name ILIKE :search OR d.last_name ILIKE :search OR d.nic ILIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get appointments
$sql = "SELECT a.*, 
        d.first_name, d.last_name, d.nic, d.blood_type, d.contact_number,
        CONCAT(d.first_name, ' ', d.last_name) as donor_name
        FROM appointments a
        JOIN donors d ON a.donor_id = d.id
        $whereClause
        ORDER BY a.appointment_date, a.appointment_time";
        
$appointments = Database::fetchAll($sql, $params);

// Get stats
$stats = Database::fetchAll(
    "SELECT 
        (SELECT COUNT(*) FROM appointments WHERE status = 'scheduled') as scheduled,
        (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed,
        (SELECT COUNT(*) FROM appointments WHERE status = 'cancelled') as cancelled,
        (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURRENT_DATE) as today,
        (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURRENT_DATE + INTERVAL '1 day') as tomorrow",
    []
)[0];
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Appointments</h1>
                <p class="text-gray-600">Schedule and manage medical report appointments</p>
            </div>
            <a href="create_appointment.php" 
               class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="ri-add-line mr-2"></i>New Appointment
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error_message']; ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-700 text-sm">Scheduled</p>
                    <h3 class="text-3xl font-bold text-blue-800 mt-2"><?php echo $stats['scheduled']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-700 text-sm">Completed</p>
                    <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo $stats['completed']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <i class="ri-check-double-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-red-700 text-sm">Cancelled</p>
                    <h3 class="text-3xl font-bold text-red-800 mt-2"><?php echo $stats['cancelled']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-close-circle-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-700 text-sm">Today</p>
                    <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo $stats['today']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-check-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Status</option>
                    <option value="scheduled" <?php echo $status == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Search Donor</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                       placeholder="Name or NIC">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
                <a href="manage_appointments.php" class="ml-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Appointments Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hospital</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No appointments found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): 
                            $statusColor = match($appointment['status']) {
                                'scheduled' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            
                            $isToday = $appointment['appointment_date'] == date('Y-m-d');
                            $isPast = $appointment['appointment_date'] < date('Y-m-d') && $appointment['status'] == 'scheduled';
                        ?>
                        <tr class="<?php echo $isToday ? 'bg-yellow-50' : ($isPast ? 'bg-red-50' : 'hover:bg-gray-50'); ?>">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($appointment['donor_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['blood_type']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-gray-900"><?php echo htmlspecialchars($appointment['nic']); ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </td>
                            <td class="px-6 py-4 text-gray-900"><?php echo htmlspecialchars($appointment['hospital_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                                <?php if ($isToday && $appointment['status'] == 'scheduled'): ?>
                                    <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Today</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900" title="View Details">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    
                                    <?php if ($appointment['status'] == 'scheduled'): ?>
                                        <form method="GET" action="" class="inline">
                                            <input type="hidden" name="id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" name="action" value="complete" 
                                                    class="text-green-600 hover:text-green-900" 
                                                    title="Mark as Completed"
                                                    onclick="return confirm('Mark this appointment as completed?')">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        </form>
                                        
                                        <button onclick="showCancelModal(<?php echo $appointment['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900" title="Cancel">
                                            <i class="ri-close-line"></i>
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
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form method="POST" action="?action=cancel&id=" id="cancelForm">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cancel Appointment</h3>
                <div class="mt-2">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Reason for Cancellation</label>
                    <textarea name="cancellation_reason" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></textarea>
                </div>
                <div class="flex justify-end mt-6 space-x-3">
                    <button type="button" onclick="hideCancelModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Confirm Cancellation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function showCancelModal(appointmentId) {
    document.getElementById('cancelForm').action = '?action=cancel&id=' + appointmentId;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function hideCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>