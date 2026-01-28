<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Get all donors
$donors = Database::fetchAll(
    "SELECT 
        u.id as user_id,
        u.email,
        u.is_verified,
        u.verification_status,
        u.verified_at,
        u.created_at as user_created,
        d.*
     FROM users u
     JOIN donors d ON u.id = d.user_id
     WHERE u.role = 'donor'
     ORDER BY u.created_at DESC",
    []
);

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">All Donors</h1>
        <p class="text-gray-600">Manage all registered donors</p>
    </div>
    
    <!-- Stats -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-red-700 text-sm">Total Donors</p>
                    <h3 class="text-3xl font-bold text-red-800 mt-2"><?php echo count($donors); ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-user-heart-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <?php
        $verifiedCount = array_filter($donors, function($donor) {
            return $donor['is_verified'] == true;
        });
        $pendingCount = array_filter($donors, function($donor) {
            return $donor['verification_status'] == 'pending';
        });
        ?>
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-700 text-sm">Verified</p>
                    <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo count($verifiedCount); ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <i class="ri-user-check-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-700 text-sm">Pending</p>
                    <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo count($pendingCount); ?></h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <i class="ri-time-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-700 text-sm">Today</p>
                    <h3 class="text-3xl font-bold text-blue-800 mt-2">
                        <?php
                        $today = date('Y-m-d');
                        $todayCount = array_filter($donors, function($donor) use ($today) {
                            return date('Y-m-d', strtotime($donor['user_created'])) == $today;
                        });
                        echo count($todayCount);
                        ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <i class="ri-user-add-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Donors Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-xl font-bold text-gray-900">Donor List</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($donors as $donor): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">
                                <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>
                            </div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($donor['nic']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($donor['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                                <?php echo htmlspecialchars($donor['blood_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status = $donor['verification_status'];
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800'
                            ];
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColors[$status] ?? 'bg-gray-100'; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                            <?php if ($donor['verified_at']): ?>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo date('M j, Y', strtotime($donor['verified_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M j, Y', strtotime($donor['user_created'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="verify_reports.php" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                            <?php if ($donor['verification_status'] == 'pending'): ?>
                                <a href="verify_reports.php" class="text-green-600 hover:text-green-900">Verify</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>