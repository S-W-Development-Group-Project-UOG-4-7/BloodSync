<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');
$user = Auth::getUser();

require_once '../includes/header.php';
require_once 'admin_nav.php';

if (!isset($_GET['id'])) {
    header('Location: manage_appointments.php');
    exit();
}

$appointmentId = $_GET['id'];
$appointment = Database::fetch(
    "SELECT a.*, 
            d.first_name, d.last_name, d.nic, d.blood_type, d.contact_number, d.email as donor_email,
            CONCAT(d.first_name, ' ', d.last_name) as donor_name,
            uc.email as created_by_email
     FROM appointments a
     JOIN donors d ON a.donor_id = d.id
     LEFT JOIN users uc ON a.created_by = uc.id
     WHERE a.id = :id",
    ['id' => $appointmentId]
);

if (!$appointment) {
    header('Location: manage_appointments.php');
    exit();
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Appointment Details</h1>
                <p class="text-gray-600">View appointment information</p>
            </div>
            <a href="manage_appointments.php" 
               class="text-gray-600 hover:text-gray-900 font-medium">
                ← Back to Appointments
            </a>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Appointment Details -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Appointment Information</h2>
                        <p class="text-gray-600">Medical Report Collection</p>
                    </div>
                    <?php
                    $statusColor = match($appointment['status']) {
                        'scheduled' => 'bg-blue-100 text-blue-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                    ?>
                    <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                    </span>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-gray-500 text-sm">Date</p>
                        <p class="font-medium text-lg"><?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">Time</p>
                        <p class="font-medium text-lg"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <p class="text-gray-500 text-sm">Hospital/Clinic</p>
                        <p class="font-medium text-lg"><?php echo htmlspecialchars($appointment['hospital_name']); ?></p>
                    </div>
                    
                    <?php if ($appointment['doctor_name']): ?>
                    <div>
                        <p class="text-gray-500 text-sm">Doctor</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">Doctor Contact</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['doctor_contact']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($appointment['notes']): ?>
                    <div class="md:col-span-2">
                        <p class="text-gray-500 text-sm">Notes</p>
                        <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($appointment['notes']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($appointment['status'] == 'cancelled' && $appointment['cancellation_reason']): ?>
                    <div class="md:col-span-2">
                        <p class="text-gray-500 text-sm">Cancellation Reason</p>
                        <p class="text-red-700"><?php echo htmlspecialchars($appointment['cancellation_reason']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Donor Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Donor Information</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-gray-500 text-sm">Full Name</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['donor_name']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">NIC</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['nic']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">Blood Type</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['blood_type']); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">Contact Number</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['contact_number']); ?></p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <p class="text-gray-500 text-sm">Email</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['donor_email']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Appointment Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="font-bold text-gray-900 mb-4">Actions</h3>
                
                <div class="space-y-3">
                    <?php if ($appointment['status'] == 'scheduled'): ?>
                        <form method="GET" action="manage_appointments.php" class="block">
                            <input type="hidden" name="id" value="<?php echo $appointment['id']; ?>">
                            <button type="submit" name="action" value="complete" 
                                    class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-center">
                                Mark as Completed
                            </button>
                        </form>
                        
                        <button onclick="showCancelModal()" 
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                            Cancel Appointment
                        </button>
                    <?php endif; ?>
                    
                    <a href="create_appointment.php?donor_nic=<?php echo urlencode($appointment['nic']); ?>" 
                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-center">
                        Create New for Same Donor
                    </a>
                </div>
            </div>
            
            <!-- Appointment Metadata -->
            <div class="bg-gray-50 rounded-xl shadow p-6">
                <h3 class="font-bold text-gray-900 mb-4">Appointment Details</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-gray-500 text-sm">Appointment ID</p>
                        <p class="font-medium">APT-<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">Created By</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['created_by_email'] ?? 'System'); ?></p>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">Created On</p>
                        <p class="font-medium"><?php echo date('M d, Y h:i A', strtotime($appointment['created_at'])); ?></p>
                    </div>
                    
                    <?php if ($appointment['notified_at']): ?>
                    <div>
                        <p class="text-gray-500 text-sm">Notified On</p>
                        <p class="font-medium"><?php echo date('M d, Y h:i A', strtotime($appointment['notified_at'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($appointment['cancelled_at']): ?>
                    <div>
                        <p class="text-gray-500 text-sm">Cancelled On</p>
                        <p class="font-medium"><?php echo date('M d, Y h:i A', strtotime($appointment['cancelled_at'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <form method="POST" action="manage_appointments.php?action=cancel&id=<?php echo $appointment['id']; ?>">
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
function showCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
}

function hideCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>