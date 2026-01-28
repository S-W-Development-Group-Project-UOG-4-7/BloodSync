<?php
require_once __DIR__ . '/../autoload.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';

// Check authentication
Auth::requireAuth('login.php');

$user = Auth::getUser();
$profile = Auth::getDonorProfile();

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total donation count
    $totalDonations = Database::fetch(
        "SELECT COUNT(*) as count FROM donor_donations 
         WHERE donor_id = :donor_id AND status = 'verified'",
        ['donor_id' => $profile['id']]
    )['count'];

    // Get donations with pagination
    $donations = Database::fetchAll(
        "SELECT * FROM donor_donations 
         WHERE donor_id = :donor_id 
         AND status = 'verified'
         ORDER BY donation_date DESC
         LIMIT :limit OFFSET :offset",
        [
            'donor_id' => $profile['id'],
            'limit' => $limit,
            'offset' => $offset
        ]
    );

    // Calculate totals
    $totals = Database::fetch(
        "SELECT 
            COUNT(*) as total_donations,
            SUM(units_donated) as total_units
         FROM donor_donations 
         WHERE donor_id = :donor_id 
         AND status = 'verified'",
        ['donor_id' => $profile['id']]
    );

    // Calculate pages
    $totalPages = ceil($totalDonations / $limit);
} catch (Exception $e) {
    $donations = [];
    $totals = ['total_donations' => 0, 'total_units' => 0];
    $totalPages = 1;
    $error = "Error loading donation history: " . $e->getMessage();
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Donation History</h1>
        <p class="text-gray-600">View your complete blood donation record</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-red-600 to-red-800 text-white rounded-xl p-6 shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-red-200 text-sm">Total Donations</p>
                    <h3 class="text-3xl font-bold mt-2"><?php echo $totals['total_donations'] ?? 0; ?></h3>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="ri-heart-pulse-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Total Units Donated</p>
                    <h3 class="text-3xl font-bold mt-2 text-red-600"><?php echo $totals['total_units'] ?? 0; ?></h3>
                    <p class="text-gray-500 text-sm mt-1">Units of blood</p>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-drop-fill text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Last Donation</p>
                    <?php if (!empty($donations)): ?>
                        <h3 class="text-xl font-bold mt-2">
                            <?php echo date('M d, Y', strtotime($donations[0]['donation_date'])); ?>
                        </h3>
                    <?php else: ?>
                        <h3 class="text-xl font-bold mt-2 text-gray-400">No donations yet</h3>
                    <?php endif; ?>
                    <p class="text-gray-500 text-sm mt-1">Most recent</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <i class="ri-history-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Donation History Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Donation Records</h2>
        </div>

        <?php if (isset($error)): ?>
            <div class="p-6 text-center">
                <div class="text-red-600 mb-4">
                    <i class="ri-error-warning-line text-3xl"></i>
                    <p class="mt-2"><?php echo $error; ?></p>
                </div>
            </div>
        <?php elseif (empty($donations)): ?>
            <div class="p-8 text-center">
                <i class="ri-drop-line text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No donation history found</h3>
                <p class="text-gray-600">You haven't made any blood donations yet.</p>
                <p class="text-sm text-gray-500 mt-2">Visit our donation camps or hospitals to make your first donation!</p>
                <div class="mt-6">
                    <a href="dashboard.php" class="inline-flex items-center text-red-600 hover:text-red-800 font-medium">
                        <i class="ri-arrow-left-line mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Blood Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Units
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Verified By
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($donations as $donation): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo date('M d, Y', strtotime($donation['donation_date'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('h:i A', strtotime($donation['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <?php echo htmlspecialchars($donation['blood_type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo $donation['units_donated']; ?> units
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($donation['hospital_location']); ?>
                                </div>
                                <?php if ($donation['notes']): ?>
                                <div class="text-xs text-gray-500 truncate max-w-xs">
                                    <?php echo htmlspecialchars($donation['notes']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Verified
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($donation['verified_by']): 
                                    try {
                                        $verifier = Database::fetch(
                                            "SELECT email FROM users WHERE id = :id",
                                            ['id' => $donation['verified_by']]
                                        );
                                        echo htmlspecialchars($verifier['email'] ?? 'Admin');
                                    } catch (Exception $e) {
                                        echo 'Admin';
                                    }
                                else: ?>
                                    Admin
                                <?php endif; ?>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('M d, Y', strtotime($donation['verified_at'])); ?>
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
                <nav class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="ri-arrow-left-line mr-2"></i>
                            Previous
                        </a>
                        <?php else: ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                            <i class="ri-arrow-left-line mr-2"></i>
                            Previous
                        </span>
                        <?php endif; ?>

                        <div class="flex items-center">
                            <span class="text-sm text-gray-700">
                                Page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $totalPages; ?></span>
                            </span>
                        </div>

                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                            <i class="ri-arrow-right-line ml-2"></i>
                        </a>
                        <?php else: ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                            Next
                            <i class="ri-arrow-right-line ml-2"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Donation Summary Chart (Placeholder) -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Donation Summary</h2>
        <div class="grid md:grid-cols-2 gap-8">
            <div>
                <h3 class="font-medium text-gray-900 mb-4">Donations by Year</h3>
                <?php if (!empty($donations)): 
                    // Group donations by year
                    $byYear = [];
                    foreach ($donations as $donation) {
                        $year = date('Y', strtotime($donation['donation_date']));
                        $byYear[$year] = ($byYear[$year] ?? 0) + 1;
                    }
                    krsort($byYear); // Sort by year descending
                ?>
                    <div class="space-y-4">
                        <?php foreach ($byYear as $year => $count): ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium"><?php echo $year; ?></span>
                                <span class="text-gray-600"><?php echo $count; ?> donation(s)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-600 h-2 rounded-full" 
                                     style="width: <?php echo min(100, ($count / max($byYear)) * 100); ?>%">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">No data available</p>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h3 class="font-medium text-gray-900 mb-4">Impact of Your Donations</h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3">
                            <i class="ri-user-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                <?php echo $totals['total_units'] ?? 0; ?> Lives Potentially Saved
                            </p>
                            <p class="text-sm text-gray-600">Each unit can save up to 3 lives</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                            <i class="ri-hospital-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Emergency Support</p>
                            <p class="text-sm text-gray-600">Your donations help in critical situations</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                            <i class="ri-award-line"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Donor Recognition</p>
                            <p class="text-sm text-gray-600">Thank you for being a regular donor!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="mt-8 bg-gradient-to-r from-red-600 to-red-800 rounded-xl p-8 text-white">
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-4">Ready to Donate Again?</h2>
            <p class="mb-6 max-w-2xl mx-auto">Your next donation can help save more lives. Check your eligibility and schedule your next appointment.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="dashboard.php" class="bg-white text-red-600 hover:bg-gray-100 font-medium py-3 px-6 rounded-lg transition">
                    Check Eligibility
                </a>
                <a href="dashboard.php#book-appointment" class="bg-transparent border-2 border-white hover:bg-white/10 font-medium py-3 px-6 rounded-lg transition">
                    Schedule Appointment
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>