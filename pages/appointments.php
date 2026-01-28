<?php
require_once __DIR__ . '/../autoload.php';
Auth::requireAuth('login.php');
$user = Auth::getUser();
$profile = Auth::getDonorProfile();

require_once 'includes/header.php';
require_once 'includes/nav.php';

// Get donor's appointments
$appointments = Database::fetchAll(
    "SELECT * FROM appointments 
     WHERE donor_id = :donor_id 
     ORDER BY appointment_date DESC, appointment_time DESC",
    ['donor_id' => $profile['id']]
);

// Get upcoming appointments
$upcoming = array_filter($appointments, function($apt) {
    return $apt['status'] == 'scheduled' && $apt['appointment_date'] >= date('Y-m-d');
});

// Get past appointments
$past = array_filter($appointments, function($apt) {
    return $apt['status'] == 'completed' || 
           $apt['status'] == 'cancelled' || 
           ($apt['status'] == 'scheduled' && $apt['appointment_date'] < date('Y-m-d'));
});
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Appointments</h1>
        <p class="text-gray-600">Manage your medical report appointments</p>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-700 text-sm">Total Appointments</p>
                    <h3 class="text-3xl font-bold text-blue-800 mt-2"><?php echo count($appointments); ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-700 text-sm">Upcoming</p>
                    <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo count($upcoming); ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-event-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-700 text-sm">Completed</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2">
                        <?php echo count(array_filter($appointments, function($a) { return $a['status'] == 'completed'; })); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center">
                    <i class="ri-check-double-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Upcoming Appointments</h2>
        
        <?php if (empty($upcoming)): ?>
            <div class="text-center py-8">
                <i class="ri-calendar-line text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">No upcoming appointments</p>
                <p class="text-sm text-gray-500 mt-2">Contact admin to schedule a medical report appointment</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($upcoming as $appointment): 
                    $isToday = $appointment['appointment_date'] == date('Y-m-d');
                ?>
                <div class="border border-gray-200 rounded-lg p-6 hover:bg-gray-50 transition <?php echo $isToday ? 'border-yellow-300 bg-yellow-50' : ''; ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center mb-2">
                                <h3 class="text-lg font-bold text-gray-900">Medical Report Collection</h3>
                                <?php if ($isToday): ?>
                                    <span class="ml-3 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Today</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-gray-500 text-sm">Date</p>
                                    <p class="font-medium"><?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-500 text-sm">Time</p>
                                    <p class="font-medium"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-500 text-sm">Hospital</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($appointment['hospital_name']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($appointment['doctor_name']): ?>
                            <div class="mt-4 grid md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-500 text-sm">Doctor</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                </div>
                                
                                <?php if ($appointment['doctor_contact']): ?>
                                <div>
                                    <p class="text-gray-500 text-sm">Contact</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($appointment['doctor_contact']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($appointment['notes']): ?>
                            <div class="mt-4">
                                <p class="text-gray-500 text-sm">Notes</p>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-right">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                Scheduled
                            </span>
                            <p class="text-sm text-gray-500 mt-2">
                                Created: <?php echo date('M d, Y', strtotime($appointment['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            <i class="ri-information-line mr-1"></i>
                            Please bring your NIC and any previous medical reports to the appointment.
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Past Appointments -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Appointment History</h2>
        
        <?php if (empty($past)): ?>
            <div class="text-center py-8">
                <p class="text-gray-600">No past appointments</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Hospital</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Purpose</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($past as $appointment): 
                            $statusColor = match($appointment['status']) {
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($appointment['hospital_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                                <?php if ($appointment['status'] == 'cancelled' && $appointment['cancellation_reason']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($appointment['cancellation_reason']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                                <?php echo nl2br(htmlspecialchars(substr($appointment['notes'] ?? '', 0, 100))); ?>
                                <?php if (strlen($appointment['notes'] ?? '') > 100): ?>...<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>