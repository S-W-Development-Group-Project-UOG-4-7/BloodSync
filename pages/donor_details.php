<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Get donor ID from query parameter
$donor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$donor_id) {
    header('Location: pending_verifications.php');
    exit;
}

// Fetch donor details with user information
try {
    // Main donor query without the problematic LIMIT
    $donor = Database::fetch("
        SELECT 
            u.id as user_id,
            u.email,
            u.verification_status,
            u.verification_notes,
            u.verified_at,
            u.verified_by,
            u.created_at as user_created_at,
            d.*,
            COALESCE((
                SELECT jsonb_agg(jsonb_build_object(
                    'id', mr.id,
                    'title', mr.title,
                    'file_path', mr.file_path,
                    'report_date', mr.report_date,
                    'report_type', mr.report_type,
                    'status', mr.status,
                    'reviewed_by', mr.reviewed_by,
                    'reviewed_at', mr.reviewed_at,
                    'uploaded_at', mr.uploaded_at
                ) ORDER BY mr.report_date DESC)
                FROM medical_reports mr
                WHERE mr.donor_id = d.id
            ), '[]'::jsonb) as medical_reports,
            COALESCE((
                SELECT jsonb_agg(jsonb_build_object(
                    'id', dd.id,
                    'donation_date', dd.donation_date,
                    'units_donated', dd.units_donated,
                    'blood_type', dd.blood_type,
                    'hospital_location', dd.hospital_location,
                    'status', dd.status,
                    'verified_at', dd.verified_at
                ) ORDER BY dd.donation_date DESC)
                FROM donor_donations dd
                WHERE dd.donor_id = d.id
            ), '[]'::jsonb) as donations,
            COALESCE((
                SELECT jsonb_agg(jsonb_build_object(
                    'id', app.id,
                    'hospital_name', app.hospital_name,
                    'appointment_date', app.appointment_date,
                    'appointment_time', app.appointment_time,
                    'status', app.status,
                    'purpose', app.purpose
                ) ORDER BY app.appointment_date DESC, app.appointment_time DESC)
                FROM appointments app
                WHERE app.donor_id = d.id
            ), '[]'::jsonb) as appointments
        FROM users u
        JOIN donors d ON u.id = d.user_id
        WHERE d.id = :id OR u.id = :id
    ", ['id' => $donor_id]);

    if (!$donor) {
        $_SESSION['error'] = 'Donor not found.';
        header('Location: pending_verifications.php');
        exit;
    }

    // Get verified by admin details if exists
    $verified_by_admin = null;
    if ($donor['verified_by']) {
        $verified_by_admin = Database::fetch("
            SELECT email FROM users WHERE id = :id
        ", ['id' => $donor['verified_by']]);
    }

    // Get recent notifications separately (without LIMIT in jsonb_agg)
    $recent_notifications = [];
    try {
        $notifications_data = Database::fetchAll("
            SELECT 
                id,
                title,
                message,
                is_read,
                created_at
            FROM notifications 
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 10
        ", ['user_id' => $donor['user_id']]);
        
        // Convert to JSON manually
        $recent_notifications = json_encode($notifications_data);
    } catch (Exception $e) {
        // If notifications table doesn't exist or error, use empty array
        $recent_notifications = '[]';
    }

} catch (Exception $e) {
    error_log('Donor details error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error loading donor details: ' . $e->getMessage();
    header('Location: pending_verifications.php');
    exit;
}

// Get stats
try {
    $stats = Database::fetch("
        SELECT 
            SUM(CASE WHEN type = 'report' THEN 1 ELSE 0 END) as total_reports,
            SUM(CASE WHEN type = 'report' AND status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
            SUM(CASE WHEN type = 'report' AND status = 'approved' THEN 1 ELSE 0 END) as approved_reports,
            SUM(CASE WHEN type = 'donation' THEN 1 ELSE 0 END) as total_donations,
            SUM(CASE WHEN type = 'donation' THEN units_donated ELSE 0 END) as total_units_donated
        FROM (
            SELECT 'report' as type, status, 0 as units_donated 
            FROM medical_reports 
            WHERE donor_id = :donor_id
            UNION ALL
            SELECT 'donation' as type, status, units_donated 
            FROM donor_donations 
            WHERE donor_id = :donor_id
        ) as combined
    ", ['donor_id' => $donor['id']]);

} catch (Exception $e) {
    $stats = [
        'total_reports' => 0,
        'pending_reports' => 0,
        'approved_reports' => 0,
        'total_donations' => 0,
        'total_units_donated' => 0
    ];
}

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header with Back Button -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Donor Details</h1>
                <p class="text-gray-600">View and manage donor information</p>
            </div>
            <div>
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

    <!-- Donor Profile Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <!-- Header with Blood Type -->
        <div class="bg-gradient-to-r from-red-600 to-red-800 p-6 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">
                        <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>
                    </h2>
                    <p class="text-red-200">Donor ID: D-<?php echo str_pad($donor['id'], 6, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center border-2 border-white/30">
                        <span class="text-2xl font-bold"><?php echo $donor['blood_type']; ?></span>
                    </div>
                    
                    <!-- Verification Status Badge -->
                    <?php 
                    $statusColor = '';
                    if ($donor['verification_status'] === 'pending') {
                        $statusColor = 'bg-yellow-500';
                    } elseif ($donor['verification_status'] === 'approved') {
                        $statusColor = 'bg-green-500';
                    } elseif ($donor['verification_status'] === 'rejected') {
                        $statusColor = 'bg-red-500';
                    } else {
                        $statusColor = 'bg-gray-500';
                    }
                    ?>
                    <div class="text-center">
                        <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                            <?php echo ucfirst($donor['verification_status']); ?>
                        </span>
                        <?php if ($donor['verified_at']): ?>
                        <p class="text-xs text-white/80 mt-1">
                            <?php echo date('M d, Y', strtotime($donor['verified_at'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="md:col-span-2">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Personal Information</h3>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Full Name</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Email Address</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($donor['email']); ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">NIC Number</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($donor['nic']); ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Phone Number</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($donor['contact_number']); ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Date of Birth</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo date('F j, Y', strtotime($donor['date_of_birth'])); ?>
                        (<?php echo floor((time() - strtotime($donor['date_of_birth'])) / 31556926); ?> years)
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Blood Type</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg font-bold">
                        <?php echo $donor['blood_type']; ?>
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-gray-500 text-sm mb-1">Address</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($donor['address']); ?>
                        <?php if ($donor['city']): ?>, <?php echo htmlspecialchars($donor['city']); ?><?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Weight</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo $donor['weight']; ?> kg
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Last Donation</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo $donor['last_donation_date'] ? date('F j, Y', strtotime($donor['last_donation_date'])) : 'Never donated'; ?>
                    </div>
                </div>

                <!-- Health Information -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Health Information</h3>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-gray-500 text-sm mb-1">Health Conditions</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo $donor['health_conditions'] ? nl2br(htmlspecialchars($donor['health_conditions'])) : 'None reported'; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Eligibility Status</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <span class="<?php echo $donor['is_eligible'] ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                            <?php echo $donor['is_eligible'] ? 'Eligible' : 'Not Eligible'; ?>
                        </span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Total Donations</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo $donor['total_donations']; ?> donations
                        (<?php echo $donor['total_units_donated']; ?> units)
                    </div>
                </div>

                <!-- Account Information -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Account Information</h3>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Account Created</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php echo date('F j, Y', strtotime($donor['user_created_at'])); ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Verification Status</label>
                    <div class="bg-gray-50 px-4 py-3 rounded-lg">
                        <?php if ($donor['verification_notes']): ?>
                            <div class="text-gray-700"><?php echo htmlspecialchars($donor['verification_notes']); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($verified_by_admin && $donor['verified_at']): ?>
                            <div class="text-sm text-gray-500 mt-1">
                                Verified by: <?php echo htmlspecialchars($verified_by_admin['email']); ?>
                                on <?php echo date('M d, Y', strtotime($donor['verified_at'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-wrap gap-4">
                <?php if ($donor['verification_status'] === 'pending'): ?>
                <button onclick="showVerificationModal('approve', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                        class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-lg transition">
                    Approve Verification
                </button>
                
                <button onclick="showVerificationModal('reject', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                        class="bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-3 rounded-lg transition">
                    Reject Verification
                </button>
                <?php endif; ?>
                
                <?php if ($donor['verification_status'] === 'approved'): ?>
                <button onclick="showVerificationModal('reverify', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium px-6 py-3 rounded-lg transition">
                    Mark for Re-verification
                </button>
                <?php endif; ?>
                
                <?php if ($donor['verification_status'] === 'rejected'): ?>
                <button onclick="showVerificationModal('review', <?php echo $donor['user_id']; ?>, '<?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>')"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition">
                    Review Again
                </button>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-4">
                    <i class="ri-file-text-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Reports</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['total_reports']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-4">
                    <i class="ri-time-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Pending Reports</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['pending_reports']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-4">
                    <i class="ri-check-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Approved Reports</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['approved_reports']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-lg bg-red-100 text-red-600 flex items-center justify-center mr-4">
                    <i class="ri-drop-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Donations</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $stats['total_units_donated']; ?> units</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Reports Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">Medical Reports</h3>
        </div>
        
        <?php 
        $medical_reports = json_decode($donor['medical_reports'], true) ?: [];
        if (!empty($medical_reports)): 
        ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($medical_reports as $report): 
                        $report_status = $report['status'] ?? 'pending';
                        $status_color = $report_status === 'approved' ? 'bg-green-100 text-green-800' : 
                                       ($report_status === 'rejected' ? 'bg-red-100 text-red-800' : 
                                       'bg-yellow-100 text-yellow-800');
                    ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($report['title']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo date('M d, Y', strtotime($report['report_date'])); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status_color; ?>">
                                <?php echo ucfirst($report_status); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (!empty($report['file_path'])): ?>
                            <a href="<?php echo htmlspecialchars($report['file_path']); ?>" 
                               target="_blank"
                               class="text-blue-600 hover:text-blue-900">
                                View File
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
            <i class="ri-file-search-line text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No medical reports found</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Donations -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">Recent Donations</h3>
        </div>
        
        <?php 
        $donations = json_decode($donor['donations'], true) ?: [];
        if (!empty($donations)): 
        ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hospital</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($donations as $donation): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo date('M d, Y', strtotime($donation['donation_date'])); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo $donation['units_donated']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo $donation['blood_type']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo htmlspecialchars($donation['hospital_location']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                            $donation_status = $donation['status'] ?? 'pending';
                            $donation_status_color = $donation_status === 'verified' ? 'bg-green-100 text-green-800' : 
                                                    ($donation_status === 'rejected' ? 'bg-red-100 text-red-800' : 
                                                    'bg-yellow-100 text-yellow-800');
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $donation_status_color; ?>">
                                <?php echo ucfirst($donation_status); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
            <i class="ri-drop-line text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No donation records found</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Appointments -->
    <?php 
    $appointments = json_decode($donor['appointments'], true) ?: [];
    if (!empty($appointments)): 
    ?>
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Upcoming Appointments</h3>
        <div class="space-y-4">
            <?php foreach ($appointments as $appointment): 
                if (strtotime($appointment['appointment_date']) >= time() && $appointment['status'] !== 'cancelled'): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($appointment['hospital_name']); ?></h4>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['purpose']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Notifications -->
    <?php 
    $notifications = json_decode($recent_notifications, true) ?: [];
    if (!empty($notifications)): 
    ?>
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Recent Notifications</h3>
        <div class="space-y-3">
            <?php foreach ($notifications as $notification): ?>
            <div class="flex items-start space-x-3 p-3 <?php echo $notification['is_read'] ? 'bg-gray-50' : 'bg-blue-50'; ?> rounded-lg">
                <i class="ri-notification-3-line text-xl <?php echo $notification['is_read'] ? 'text-gray-400' : 'text-blue-500'; ?> mt-1"></i>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></h4>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                    </p>
                </div>
                <?php if (!$notification['is_read']): ?>
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Verification Modal (Same as in pending_verifications.php) -->
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
                <p id="reasonHint" class="text-gray-500 text-xs mt-1">This will be visible to the donor.</p>
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
    const reasonHint = document.getElementById('reasonHint');
    
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
            reasonHint.textContent = 'This will be visible to the donor.';
            break;
            
        case 'reverify':
            modalTitle.textContent = 'Mark for Re-verification';
            modalSubtitle.textContent = 'Mark ' + userName + ' back to pending status?';
            submitBtn.textContent = 'Mark Pending';
            submitBtn.className = 'px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white font-medium';
            reasonField.classList.remove('hidden');
            reasonHint.textContent = 'Explain why the donor needs to be re-verified. This will be visible to the donor.';
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