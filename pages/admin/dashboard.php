<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin - admin dashboard should only be accessible by admins
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

require_once '../includes/header.php';

// Check user role and load appropriate navigation
if ($user['role'] !== 'admin') {
    // If somehow a non-admin gets here, redirect to donor dashboard
    header('Location: ../donor/dashboard.php');
    exit();
}

require_once 'admin_nav.php';

// Get admin stats
try {
    $stats = Database::fetchAll(
        "SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'donor') as total_donors,
            (SELECT COUNT(*) FROM users WHERE verification_status = 'pending' AND role = 'donor') as pending_verifications,
            (SELECT COUNT(*) FROM medical_reports WHERE status = 'pending') as pending_reports,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURRENT_DATE AND role = 'donor') as today_registrations,
            (SELECT COUNT(*) FROM users WHERE verification_status = 'approved' AND role = 'donor') as verified_donors,
            (SELECT COUNT(*) FROM medical_reports WHERE DATE(report_date) = CURRENT_DATE) as today_reports,
            (SELECT COUNT(*) FROM users WHERE role = 'hospital') as total_hospitals,
            (SELECT COUNT(*) FROM users WHERE role = 'hospital' AND verification_status = 'pending') as pending_hospitals,
            (SELECT COUNT(*) FROM users WHERE role = 'hospital' AND verification_status = 'approved') as verified_hospitals,
            (SELECT COUNT(*) FROM blood_requests WHERE status = 'pending') as pending_blood_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE status = 'approved') as approved_blood_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE urgency_level = 'critical' AND status = 'pending') as critical_blood_requests,
            (SELECT COUNT(*) FROM blood_requests WHERE DATE(created_at) = CURRENT_DATE) as today_blood_requests,
            (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_admins,
            (SELECT COUNT(*) FROM donor_donations WHERE DATE(donation_date) = CURRENT_DATE) as today_donations,
            (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURRENT_DATE AND status = 'scheduled') as today_appointments,
            (SELECT COUNT(*) FROM events WHERE approval_status = 'pending') as pending_events,
            (SELECT COUNT(*) FROM events WHERE approval_status = 'approved' AND DATE(created_at) = CURRENT_DATE) as today_events,
            -- Appointment Statistics
            (SELECT COUNT(*) FROM appointments WHERE status = 'scheduled') as total_scheduled_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'scheduled' AND appointment_date = CURRENT_DATE) as today_scheduled_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'cancelled') as cancelled_appointments,
            (SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURRENT_DATE) as today_created_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'scheduled' AND appointment_date = CURRENT_DATE + INTERVAL '1 day') as tomorrow_appointments",
        []
    )[0];
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log('Dashboard stats error: ' . $e->getMessage());
    $stats = [
        'total_donors' => 0,
        'pending_verifications' => 0,
        'pending_reports' => 0,
        'today_registrations' => 0,
        'verified_donors' => 0,
        'today_reports' => 0,
        'total_hospitals' => 0,
        'pending_hospitals' => 0,
        'verified_hospitals' => 0,
        'pending_blood_requests' => 0,
        'approved_blood_requests' => 0,
        'critical_blood_requests' => 0,
        'today_blood_requests' => 0,
        'total_admins' => 0,
        'today_donations' => 0,
        'today_appointments' => 0,
        'pending_events' => 0,
        'today_events' => 0,
        // Appointment stats
        'total_scheduled_appointments' => 0,
        'today_scheduled_appointments' => 0,
        'completed_appointments' => 0,
        'cancelled_appointments' => 0,
        'today_created_appointments' => 0,
        'tomorrow_appointments' => 0
    ];
}

// Get total blood stock
try {
    $totalStock = Database::fetch(
        "SELECT SUM(units_available) as total FROM blood_stocks",
        []
    )['total'] ?? 0;
} catch (Exception $e) {
    error_log('Blood stock error: ' . $e->getMessage());
    $totalStock = 0;
}

// Get today's appointments for urgent alerts
try {
    $todayAppointments = Database::fetchAll(
        "SELECT a.*, 
                CONCAT(d.first_name, ' ', d.last_name) as donor_name,
                d.contact_number
         FROM appointments a
         JOIN donors d ON a.donor_id = d.id
         WHERE a.appointment_date = CURRENT_DATE 
         AND a.status = 'scheduled'
         ORDER BY a.appointment_time ASC
         LIMIT 5",
        []
    );
} catch (Exception $e) {
    $todayAppointments = [];
}

// Get upcoming appointments (next 3 days)
try {
    $upcomingAppointments = Database::fetchAll(
        "SELECT a.*, 
                CONCAT(d.first_name, ' ', d.last_name) as donor_name
         FROM appointments a
         JOIN donors d ON a.donor_id = d.id
         WHERE a.appointment_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '3 days'
         AND a.status = 'scheduled'
         ORDER BY a.appointment_date, a.appointment_time
         LIMIT 10",
        []
    );
} catch (Exception $e) {
    $upcomingAppointments = [];
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($user['name'] ?? $user['email']); ?>!</p>
    </div>
    
    <!-- Urgent Alerts Section -->
    <div class="space-y-4 mb-8">
        <?php if ($stats['critical_blood_requests'] > 0): ?>
        <div class="bg-red-100 border border-red-300 rounded-xl p-6">
            <div class="flex items-center">
                <i class="ri-alarm-warning-line text-red-600 text-2xl mr-4"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-red-800 text-lg">Urgent Blood Request!</h3>
                    <p class="text-red-700">
                        There are <span class="font-bold"><?php echo $stats['critical_blood_requests']; ?> critical blood request(s)</span> 
                        that need immediate attention.
                        <a href="blood_requests.php?urgency=critical" class="font-bold underline ml-2">Review Now →</a>
                    </p>
                </div>
                <a href="blood_requests.php?urgency=critical" 
                   class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
                    Take Action
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Today's Appointments Alert -->
        <?php if ($stats['today_scheduled_appointments'] > 0): ?>
        <div class="bg-yellow-100 border border-yellow-300 rounded-xl p-6">
            <div class="flex items-center">
                <i class="ri-calendar-event-line text-yellow-600 text-2xl mr-4"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-yellow-800 text-lg">Today's Appointments</h3>
                    <p class="text-yellow-700">
                        You have <span class="font-bold"><?php echo $stats['today_scheduled_appointments']; ?> appointment(s)</span> 
                        scheduled for today.
                        <a href="manage_appointments.php?date=<?php echo date('Y-m-d'); ?>" class="font-bold underline ml-2">View Today's Schedule →</a>
                    </p>
                </div>
                <a href="manage_appointments.php?date=<?php echo date('Y-m-d'); ?>" 
                   class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg font-medium">
                    View Appointments
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tomorrow's Appointments Alert -->
        <?php if ($stats['tomorrow_appointments'] > 0): ?>
        <div class="bg-blue-100 border border-blue-300 rounded-xl p-6">
            <div class="flex items-center">
                <i class="ri-calendar-check-line text-blue-600 text-2xl mr-4"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-blue-800 text-lg">Tomorrow's Appointments</h3>
                    <p class="text-blue-700">
                        You have <span class="font-bold"><?php echo $stats['tomorrow_appointments']; ?> appointment(s)</span> 
                        scheduled for tomorrow.
                        <a href="manage_appointments.php?date=<?php echo date('Y-m-d', strtotime('+1 day')); ?>" class="font-bold underline ml-2">View Tomorrow's Schedule →</a>
                    </p>
                </div>
                <a href="manage_appointments.php?date=<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    View Schedule
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Pending Verifications Alert -->
        <?php if ($stats['pending_verifications'] > 0): ?>
        <div class="bg-orange-100 border border-orange-300 rounded-xl p-6">
            <div class="flex items-center">
                <i class="ri-user-search-line text-orange-600 text-2xl mr-4"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-orange-800 text-lg">Pending Verifications</h3>
                    <p class="text-orange-700">
                        There are <span class="font-bold"><?php echo $stats['pending_verifications']; ?> donor verification(s)</span> 
                        pending review.
                        <a href="pending_verifications.php" class="font-bold underline ml-2">Review Now →</a>
                    </p>
                </div>
                <a href="pending_verifications.php" 
                   class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg font-medium">
                    Verify Donors
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ADMIN DASHBOARD CONTENT -->
    
    <!-- Quick Stats - Appointment Section -->
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Appointment Management</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="create_appointment.php" class="block">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 hover:border-blue-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-blue-700 text-sm">Schedule New</p>
                            <h3 class="text-3xl font-bold text-blue-800 mt-2">+</h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="ri-calendar-add-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Create new appointment
                    </p>
                </div>
            </a>
            
            <a href="manage_appointments.php?status=scheduled" class="block">
                <div class="bg-green-50 border border-green-200 rounded-xl p-6 hover:border-green-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-green-700 text-sm">Scheduled</p>
                            <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo $stats['total_scheduled_appointments']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                            <i class="ri-calendar-event-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-green-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> View all scheduled
                    </p>
                </div>
            </a>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-yellow-700 text-sm">Today's Appointments</p>
                        <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo $stats['today_scheduled_appointments']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                        <i class="ri-calendar-check-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-yellow-600 text-sm mt-3">
                    Appointments scheduled for today
                </p>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-700 text-sm">Completed</p>
                        <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['completed_appointments']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center">
                        <i class="ri-check-double-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mt-3">
                    Successfully completed appointments
                </p>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats - Donor Section -->
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Donor Management</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="verify_reports.php" class="block">
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 hover:border-yellow-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-yellow-700 text-sm">Pending Reports</p>
                            <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo $stats['pending_reports']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                            <i class="ri-file-medical-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-yellow-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Review medical reports
                    </p>
                </div>
            </a>
            
            <a href="pending_verifications.php" class="block">
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-6 hover:border-orange-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-orange-700 text-sm">Pending Verifications</p>
                            <h3 class="text-3xl font-bold text-orange-800 mt-2"><?php echo $stats['pending_verifications']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center">
                            <i class="ri-user-search-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-orange-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Verify donor accounts
                    </p>
                </div>
            </a>
            
            <a href="all_donors.php" class="block">
                <div class="bg-green-50 border border-green-200 rounded-xl p-6 hover:border-green-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-green-700 text-sm">Verified Donors</p>
                            <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo $stats['verified_donors']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                            <i class="ri-user-heart-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-green-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> View all donors
                    </p>
                </div>
            </a>
            
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-red-700 text-sm">Today's Registrations</p>
                        <h3 class="text-3xl font-bold text-red-800 mt-2"><?php echo $stats['today_registrations']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                        <i class="ri-user-add-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-red-600 text-sm mt-3">
                    New donor registrations today
                </p>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats - Hospital Section -->
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Hospital Management</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="manage_hospitals.php" class="block">
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-6 hover:border-purple-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-purple-700 text-sm">Total Hospitals</p>
                            <h3 class="text-3xl font-bold text-purple-800 mt-2"><?php echo $stats['total_hospitals']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center">
                            <i class="ri-hospital-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-purple-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Manage hospitals
                    </p>
                </div>
            </a>
            
            <a href="manage_hospitals.php?status=pending" class="block">
                <div class="bg-pink-50 border border-pink-200 rounded-xl p-6 hover:border-pink-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-pink-700 text-sm">Pending Hospitals</p>
                            <h3 class="text-3xl font-bold text-pink-800 mt-2"><?php echo $stats['pending_hospitals']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center">
                            <i class="ri-user-search-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-pink-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Verify hospitals
                    </p>
                </div>
            </a>
            
            <div class="bg-teal-50 border border-teal-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-teal-700 text-sm">Verified Hospitals</p>
                        <h3 class="text-3xl font-bold text-teal-800 mt-2"><?php echo $stats['verified_hospitals']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-full flex items-center justify-center">
                        <i class="ri-building-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-teal-600 text-sm mt-3">
                    Approved hospital accounts
                </p>
            </div>
            
            <a href="blood_requests.php" class="block">
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 hover:border-indigo-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-indigo-700 text-sm">Blood Requests</p>
                            <h3 class="text-3xl font-bold text-indigo-800 mt-2"><?php echo $stats['pending_blood_requests']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                            <i class="ri-heart-pulse-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-indigo-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Manage requests
                    </p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Quick Stats - Event Management Section -->
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Event Management</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="manage_events.php?status=pending" class="block">
                <div class="bg-cyan-50 border border-cyan-200 rounded-xl p-6 hover:border-cyan-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-cyan-700 text-sm">Pending Events</p>
                            <h3 class="text-3xl font-bold text-cyan-800 mt-2"><?php echo $stats['pending_events']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center">
                            <i class="ri-calendar-event-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-cyan-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Review & approve events
                    </p>
                </div>
            </a>
            
            <a href="create_event.php" class="block">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 hover:border-emerald-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-emerald-700 text-sm">Create New Event</p>
                            <h3 class="text-3xl font-bold text-emerald-800 mt-2">+</h3>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center">
                            <i class="ri-add-circle-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-emerald-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Create new event
                    </p>
                </div>
            </a>
            
            <div class="bg-lime-50 border border-lime-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-lime-700 text-sm">Today's Events</p>
                        <h3 class="text-3xl font-bold text-lime-800 mt-2"><?php echo $stats['today_events']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-lime-100 text-lime-600 rounded-full flex items-center justify-center">
                        <i class="ri-calendar-check-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-lime-600 text-sm mt-3">
                    Events approved today
                </p>
            </div>
            
            <a href="manage_events.php" class="block">
                <div class="bg-violet-50 border border-violet-200 rounded-xl p-6 hover:border-violet-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-violet-700 text-sm">All Events</p>
                            <h3 class="text-3xl font-bold text-violet-800 mt-2">
                                <?php 
                                try {
                                    $totalEvents = Database::fetch(
                                        "SELECT COUNT(*) as total FROM events",
                                        []
                                    )['total'] ?? 0;
                                    echo $totalEvents;
                                } catch (Exception $e) {
                                    echo '0';
                                }
                                ?>
                            </h3>
                        </div>
                        <div class="w-12 h-12 bg-violet-100 text-violet-600 rounded-full flex items-center justify-center">
                            <i class="ri-calendar-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-violet-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> View all events
                    </p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Quick Stats - Blood Management Section -->
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Blood Management</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="blood_stocks.php" class="block">
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6 hover:border-indigo-300 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-indigo-700 text-sm">Total Blood Stock</p>
                            <h3 class="text-3xl font-bold text-indigo-800 mt-2"><?php echo $totalStock; ?></h3>
                            <p class="text-indigo-600 text-sm mt-1">units available</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                            <i class="ri-drop-line text-2xl"></i>
                        </div>
                    </div>
                    <p class="text-indigo-600 text-sm mt-3 flex items-center">
                        <i class="ri-arrow-right-line mr-1"></i> Manage blood stock
                    </p>
                </div>
            </a>
            
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-red-700 text-sm">Critical Requests</p>
                        <h3 class="text-3xl font-bold text-red-800 mt-2"><?php echo $stats['critical_blood_requests']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                        <i class="ri-alarm-warning-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-red-600 text-sm mt-3">
                    Urgent blood requests
                </p>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-green-700 text-sm">Approved Requests</p>
                        <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo $stats['approved_blood_requests']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                        <i class="ri-check-double-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-green-600 text-sm mt-3">
                    Ready for fulfillment
                </p>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-700 text-sm">Today's Reports</p>
                        <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['today_reports']; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center">
                        <i class="ri-file-upload-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mt-3">
                    Medical reports uploaded today
                </p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Appointment Management Actions -->
        <a href="create_appointment.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-calendar-add-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-blue-600">Schedule Appointment</h3>
                    <p class="text-sm text-gray-600">Create medical report appointment</p>
                </div>
            </div>
        </a>
        
        <a href="manage_appointments.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-green-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-calendar-check-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-green-600">Manage Appointments</h3>
                    <p class="text-sm text-gray-600">View & manage all appointments</p>
                </div>
            </div>
        </a>
        
        <!-- Donor Management Actions -->
        <a href="verify_reports.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-yellow-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-file-check-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-yellow-600">Verify Reports</h3>
                    <p class="text-sm text-gray-600">Review medical reports</p>
                </div>
            </div>
        </a>
        
        <a href="all_donors.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-user-search-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-orange-600">All Donors</h3>
                    <p class="text-sm text-gray-600">View all registered donors</p>
                </div>
            </div>
        </a>
        
        <!-- Hospital Management Actions -->
        <a href="manage_hospitals.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-purple-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-hospital-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-purple-600">Hospitals</h3>
                    <p class="text-sm text-gray-600">Manage hospital accounts</p>
                </div>
            </div>
        </a>
        
        <a href="blood_requests.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-red-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-heart-pulse-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-red-600">Blood Requests</h3>
                    <p class="text-sm text-gray-600">Manage blood requests</p>
                </div>
            </div>
        </a>
        
        <!-- Blood Management Actions -->
        <a href="blood_stocks.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-indigo-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-drop-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-indigo-600">Blood Stock</h3>
                    <p class="text-sm text-gray-600">Manage blood inventory</p>
                </div>
            </div>
        </a>
        
        <a href="donor_donations.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-teal-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-heart-add-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-teal-600">Donations</h3>
                    <p class="text-sm text-gray-600">Manage donations</p>
                </div>
            </div>
        </a>
        
        <!-- Event Management Actions -->
        <a href="manage_events.php" 
           class="border border-gray-200 rounded-xl p-6 hover:border-cyan-300 hover:shadow-md transition group">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-lg flex items-center justify-center mr-4">
                    <i class="ri-calendar-event-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 group-hover:text-cyan-600">Manage Events</h3>
                    <p class="text-sm text-gray-600">Approve & manage events</p>
                </div>
            </div>
        </a>
        
    </div>
    
    <!-- Today's Appointments Overview -->
    <?php if (!empty($todayAppointments)): ?>
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900">Today's Appointments</h2>
            <a href="manage_appointments.php?date=<?php echo date('Y-m-d'); ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                View All Today's Appointments →
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Hospital</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($todayAppointments as $appointment): 
                        $time = date('h:i A', strtotime($appointment['appointment_time']));
                        $isUpcoming = strtotime($appointment['appointment_time']) > time();
                    ?>
                    <tr class="<?php echo $isUpcoming ? 'hover:bg-gray-50' : 'bg-gray-50'; ?>">
                        <td class="px-4 py-3">
                            <div class="font-medium <?php echo $isUpcoming ? 'text-gray-900' : 'text-gray-500'; ?>"><?php echo $time; ?></div>
                            <?php if (!$isUpcoming): ?>
                                <span class="text-xs text-gray-400">Completed</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium"><?php echo htmlspecialchars($appointment['donor_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['contact_number']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-900"><?php echo htmlspecialchars($appointment['hospital_name']); ?></td>
                        <td class="px-4 py-3 text-gray-900">
                            <?php echo $appointment['doctor_name'] ? htmlspecialchars($appointment['doctor_name']) : 'N/A'; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                <?php echo htmlspecialchars($appointment['purpose']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <?php if ($isUpcoming): ?>
                                <a href="manage_appointments.php?action=complete&id=<?php echo $appointment['id']; ?>" 
                                   class="text-green-600 hover:text-green-900" 
                                   title="Mark as Completed"
                                   onclick="return confirm('Mark this appointment as completed?')">
                                    <i class="ri-check-line"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Two Column Layout -->
    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Blood Stock Overview -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Blood Stock Overview</h2>
                <a href="blood_stocks.php" class="text-red-600 hover:text-red-800 font-medium">
                    Manage Stock →
                </a>
            </div>
            
            <?php
            try {
                $bloodStocks = Database::fetchAll(
                    "SELECT * FROM blood_stocks ORDER BY blood_type",
                    []
                );
                
                // Get low stock items
                $lowStockItems = array_filter($bloodStocks, function($stock) {
                    return $stock['units_available'] < $stock['minimum_level'];
                });
                
                // Get critical stock items (empty or very low)
                $criticalStockItems = array_filter($bloodStocks, function($stock) {
                    return $stock['units_available'] <= ($stock['minimum_level'] * 0.3);
                });
            } catch (Exception $e) {
                error_log('Blood stock fetch error: ' . $e->getMessage());
                $bloodStocks = [];
                $lowStockItems = [];
                $criticalStockItems = [];
            }
            ?>
            
            <?php if (empty($bloodStocks)): ?>
                <p class="text-gray-500 text-center py-8">No blood stock data available</p>
            <?php else: ?>
                <!-- Stock Summary -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-500 text-sm">Total Units</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo array_sum(array_column($bloodStocks, 'units_available')); ?>
                        </p>
                    </div>
                    <div class="<?php echo empty($lowStockItems) ? 'bg-green-50' : 'bg-yellow-50'; ?> rounded-lg p-4">
                        <p class="<?php echo empty($lowStockItems) ? 'text-green-600' : 'text-yellow-600'; ?> text-sm">Low Stock Types</p>
                        <p class="text-2xl font-bold <?php echo empty($lowStockItems) ? 'text-green-700' : 'text-yellow-700'; ?>">
                            <?php echo count($lowStockItems); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Stock Items -->
                <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                    <?php foreach ($bloodStocks as $stock): 
                        $percentage = ($stock['units_available'] / $stock['maximum_level']) * 100;
                        $statusColor = '';
                        $statusText = '';
                        
                        if ($stock['units_available'] == 0) {
                            $statusColor = 'bg-red-100 text-red-800 border-red-200';
                            $statusText = 'Empty';
                        } elseif ($stock['units_available'] <= ($stock['minimum_level'] * 0.3)) {
                            $statusColor = 'bg-red-50 text-red-700 border-red-100';
                            $statusText = 'Critical';
                        } elseif ($stock['units_available'] < $stock['minimum_level']) {
                            $statusColor = 'bg-yellow-50 text-yellow-700 border-yellow-100';
                            $statusText = 'Low';
                        } elseif ($stock['units_available'] > $stock['maximum_level'] * 0.8) {
                            $statusColor = 'bg-green-50 text-green-700 border-green-100';
                            $statusText = 'High';
                        } else {
                            $statusColor = 'bg-blue-50 text-blue-700 border-blue-100';
                            $statusText = 'Normal';
                        }
                    ?>
                    <div class="border rounded-lg p-4 <?php echo $statusColor; ?>">
                        <div class="flex justify-between items-center mb-2">
                            <div class="flex items-center">
                                <span class="font-bold text-lg mr-3"><?php echo htmlspecialchars($stock['blood_type']); ?></span>
                                <span class="text-xs px-2 py-1 bg-white/50 rounded-full"><?php echo $statusText; ?></span>
                            </div>
                            <span class="text-lg font-bold"><?php echo $stock['units_available']; ?> units</span>
                        </div>
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div class="h-2 rounded-full 
                                <?php echo $percentage < 10 ? 'bg-red-500' : 
                                       ($percentage < 30 ? 'bg-yellow-500' : 
                                       ($percentage > 80 ? 'bg-green-500' : 'bg-blue-500')); ?>" 
                                style="width: <?php echo min($percentage, 100); ?>%">
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>Min: <?php echo $stock['minimum_level']; ?></span>
                            <span>Current: <?php echo $stock['units_available']; ?></span>
                            <span>Max: <?php echo $stock['maximum_level']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($criticalStockItems)): ?>
                    <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="ri-alarm-warning-line text-red-600 mr-2"></i>
                            <h3 class="font-medium text-red-800">Critical Stock Alert</h3>
                        </div>
                        <p class="text-red-700 text-sm">
                            <?php echo count($criticalStockItems); ?> blood type<?php echo count($criticalStockItems) > 1 ? 's' : ''; ?> 
                            are critically low or empty.
                            <a href="blood_stocks.php" class="font-medium underline ml-1">Review stock immediately</a>
                        </p>
                    </div>
                <?php elseif (!empty($lowStockItems)): ?>
                    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="ri-alert-line text-yellow-600 mr-2"></i>
                            <h3 class="font-medium text-yellow-800">Low Stock Alert</h3>
                        </div>
                        <p class="text-yellow-700 text-sm">
                            <?php echo count($lowStockItems); ?> blood type<?php echo count($lowStockItems) > 1 ? 's' : ''; ?> 
                            below minimum level. <a href="blood_stocks.php" class="font-medium underline ml-1">Review stock</a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recent Requests and Notifications -->
        <div class="space-y-8">
            <!-- Recent Blood Requests -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Recent Blood Requests</h2>
                    <a href="blood_requests.php" class="text-red-600 hover:text-red-800 font-medium">
                        View All →
                    </a>
                </div>
                
                <?php
                try {
                    $recentRequests = Database::fetchAll(
                        "SELECT br.*, h.hospital_name, h.registration_number
                         FROM blood_requests br
                         JOIN hospitals h ON br.hospital_id = h.id
                         ORDER BY br.created_at DESC
                         LIMIT 5",
                        []
                    );
                } catch (Exception $e) {
                    error_log('Recent requests error: ' . $e->getMessage());
                    $recentRequests = [];
                }
                ?>
                
                <?php if (empty($recentRequests)): ?>
                    <p class="text-gray-500 text-center py-8">No recent blood requests</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recentRequests as $request): 
                            $statusColor = match($request['status']) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'fulfilled' => 'bg-blue-100 text-blue-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            
                            $urgencyColor = match($request['urgency_level']) {
                                'critical' => 'text-red-600',
                                'urgent' => 'text-orange-600',
                                default => 'text-gray-600'
                            };
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                                    <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold <?php echo $urgencyColor; ?>"><?php echo $request['units_required']; ?> units</p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($request['blood_type']); ?></p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mt-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                                <div class="flex items-center text-xs text-gray-500">
                                    <i class="ri-calendar-line mr-1"></i>
                                    <?php echo date('M d', strtotime($request['required_date'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Upcoming Appointments</h2>
                    <a href="manage_appointments.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        View All →
                    </a>
                </div>
                
                <?php if (empty($upcomingAppointments)): ?>
                    <div class="text-center py-8">
                        <i class="ri-calendar-line text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">No upcoming appointments</p>
                        <a href="create_appointment.php" class="text-blue-600 hover:underline mt-2 inline-block">
                            Schedule new appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcomingAppointments as $appointment): 
                            $isTomorrow = $appointment['appointment_date'] == date('Y-m-d', strtotime('+1 day'));
                            $isToday = $appointment['appointment_date'] == date('Y-m-d');
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition <?php echo $isToday ? 'border-yellow-300 bg-yellow-50' : ''; ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($appointment['donor_name']); ?></p>
                                    <div class="flex items-center mt-1">
                                        <i class="ri-calendar-line text-gray-400 text-sm mr-1"></i>
                                        <span class="text-sm text-gray-600">
                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                            at <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                        </span>
                                        <?php if ($isToday): ?>
                                            <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Today</span>
                                        <?php elseif ($isTomorrow): ?>
                                            <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Tomorrow</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($appointment['hospital_name']); ?></p>
                                </div>
                                <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="ri-eye-line"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Pending Actions -->
    <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Pending Actions</h2>
        
        <div class="space-y-4">
            <?php if ($stats['pending_verifications'] > 0): ?>
            <a href="pending_verifications.php" class="block">
                <div class="flex items-center justify-between p-4 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
                            <i class="ri-user-search-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Pending Donor Verifications</p>
                            <p class="text-sm text-gray-600"><?php echo $stats['pending_verifications']; ?> donor(s) need verification</p>
                        </div>
                    </div>
                    <i class="ri-arrow-right-s-line text-orange-600 text-xl"></i>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($stats['pending_hospitals'] > 0): ?>
            <a href="manage_hospitals.php?status=pending" class="block">
                <div class="flex items-center justify-between p-4 bg-pink-50 hover:bg-pink-100 border border-pink-200 rounded-lg transition">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center mr-3">
                            <i class="ri-hospital-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Pending Hospital Verifications</p>
                            <p class="text-sm text-gray-600"><?php echo $stats['pending_hospitals']; ?> hospital(s) need verification</p>
                        </div>
                    </div>
                    <i class="ri-arrow-right-s-line text-pink-600 text-xl"></i>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($stats['pending_reports'] > 0): ?>
            <a href="verify_reports.php" class="block">
                <div class="flex items-center justify-between p-4 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg transition">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">
                            <i class="ri-file-medical-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Pending Medical Reports</p>
                            <p class="text-sm text-gray-600"><?php echo $stats['pending_reports']; ?> report(s) need review</p>
                        </div>
                    </div>
                    <i class="ri-arrow-right-s-line text-yellow-600 text-xl"></i>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($stats['pending_blood_requests'] > 0): ?>
            <a href="blood_requests.php" class="block">
                <div class="flex items-center justify-between p-4 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                            <i class="ri-heart-pulse-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Pending Blood Requests</p>
                            <p class="text-sm text-gray-600"><?php echo $stats['pending_blood_requests']; ?> request(s) need action</p>
                        </div>
                    </div>
                    <i class="ri-arrow-right-s-line text-red-600 text-xl"></i>
                </div>
            </a>
            <?php endif; ?>
            
            <!-- NEW: Pending Events Action -->
            <?php if ($stats['pending_events'] > 0): ?>
            <a href="manage_events.php?status=pending" class="block">
                <div class="flex items-center justify-between p-4 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 rounded-lg transition">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center mr-3">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Pending Events</p>
                            <p class="text-sm text-gray-600"><?php echo $stats['pending_events']; ?> event(s) need approval</p>
                        </div>
                    </div>
                    <i class="ri-arrow-right-s-line text-cyan-600 text-xl"></i>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($stats['pending_verifications'] == 0 && $stats['pending_hospitals'] == 0 && 
                      $stats['pending_reports'] == 0 && $stats['pending_blood_requests'] == 0 &&
                      $stats['pending_events'] == 0): ?>
            <div class="text-center py-8">
                <i class="ri-checkbox-circle-line text-4xl text-green-500 mb-4"></i>
                <p class="text-gray-600">No pending actions. Everything is up to date!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Activity</h2>
        
        <?php
        try {
            $recentActivity = Database::fetchAll(
                "SELECT 
                    'appointment' as type, 
                    'New Appointment Scheduled' as title, 
                    a.created_at as created_at,
                    CONCAT(d.first_name, ' ', d.last_name) as user_name,
                    CONCAT(a.hospital_name, ' - ', TO_CHAR(a.appointment_date, 'Mon DD, YYYY')) as details
                 FROM appointments a
                 JOIN donors d ON a.donor_id = d.id
                 WHERE a.created_at >= CURRENT_DATE - INTERVAL '7 days'
                 UNION ALL
                 SELECT 
                    'report' as type, 
                    'Medical Report Uploaded' as title, 
                    mr.uploaded_at as created_at,
                    CONCAT(d.first_name, ' ', d.last_name) as user_name,
                    mr.title as details
                 FROM medical_reports mr
                 JOIN donors d ON mr.donor_id = d.id
                 WHERE mr.uploaded_at >= CURRENT_DATE - INTERVAL '7 days'
                 UNION ALL
                 SELECT 
                    'verification' as type,
                    'Account Verified' as title,
                    u.verified_at as created_at,
                    CONCAT(d.first_name, ' ', d.last_name) as user_name,
                    u.verification_status as details
                 FROM users u
                 JOIN donors d ON u.id = d.user_id
                 WHERE u.verified_at IS NOT NULL
                 AND u.verified_at >= CURRENT_DATE - INTERVAL '7 days'
                 UNION ALL
                 SELECT 
                    'donation' as type,
                    'Blood Donation Recorded' as title,
                    dd.verified_at as created_at,
                    CONCAT(d.first_name, ' ', d.last_name) as user_name,
                    CONCAT(dd.units_donated, ' units of ', dd.blood_type) as details
                 FROM donor_donations dd
                 JOIN donors d ON dd.donor_id = d.id
                 WHERE dd.status = 'verified'
                 AND dd.verified_at >= CURRENT_DATE - INTERVAL '7 days'
                 UNION ALL
                 SELECT 
                    'hospital_registration' as type,
                    'Hospital Registered' as title,
                    h.created_at as created_at,
                    h.hospital_name as user_name,
                    h.registration_number as details
                 FROM hospitals h
                 WHERE h.created_at >= CURRENT_DATE - INTERVAL '7 days'
                 UNION ALL
                 SELECT 
                    'blood_request' as type,
                    'Blood Request Submitted' as title,
                    br.created_at as created_at,
                    h.hospital_name as user_name,
                    CONCAT(br.units_required, ' units of ', br.blood_type) as details
                 FROM blood_requests br
                 JOIN hospitals h ON br.hospital_id = h.id
                 WHERE br.created_at >= CURRENT_DATE - INTERVAL '7 days'
                 UNION ALL
                 SELECT 
                    'event' as type,
                    'New Event Created' as title,
                    e.created_at as created_at,
                    e.organizer_name as user_name,
                    e.title as details
                 FROM events e
                 WHERE e.created_at >= CURRENT_DATE - INTERVAL '7 days'
                 ORDER BY created_at DESC
                 LIMIT 10",
                []
            );
        } catch (Exception $e) {
            error_log('Recent activity error: ' . $e->getMessage());
            $recentActivity = [];
        }
        ?>
        
        <?php if (empty($recentActivity)): ?>
            <p class="text-gray-500 text-center py-8">No recent activity</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recentActivity as $activity): 
                    $icon = match($activity['type']) {
                        'appointment' => 'calendar-event-line',
                        'report' => 'file-upload-line',
                        'verification' => 'user-check-line',
                        'donation' => 'heart-pulse-line',
                        'hospital_registration' => 'hospital-line',
                        'blood_request' => 'heart-add-line',
                        'event' => 'calendar-event-line',
                        default => 'notification-line'
                    };
                    $color = match($activity['type']) {
                        'appointment' => 'blue',
                        'report' => 'yellow',
                        'verification' => 'green',
                        'donation' => 'red',
                        'hospital_registration' => 'purple',
                        'blood_request' => 'pink',
                        'event' => 'cyan',
                        default => 'gray'
                    };
                ?>
                <div class="flex items-start border-b pb-4 last:border-0 hover:bg-gray-50 px-2 py-1 rounded-lg">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-4 flex-shrink-0
                        bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-600">
                        <i class="ri-<?php echo $icon; ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate"><?php echo $activity['title']; ?></h4>
                        <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($activity['user_name']); ?></p>
                        <?php if ($activity['details']): ?>
                        <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($activity['details']); ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="ri-time-line mr-1"></i>
                            <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- System Overview -->
    <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">System Overview</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mx-auto mb-4">
                    <i class="ri-user-line text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Total Donors</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_donors']; ?></p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4">
                    <i class="ri-hospital-line text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Total Hospitals</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_hospitals']; ?></p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-4">
                    <i class="ri-drop-line text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Blood Units</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $totalStock; ?></p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                    <i class="ri-heart-pulse-line text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Today's Requests</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['today_blood_requests']; ?></p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mx-auto mb-4">
                    <i class="ri-calendar-line text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Appointments</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_scheduled_appointments']; ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>