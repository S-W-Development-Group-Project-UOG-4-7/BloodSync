<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'record_donation':
            $donorId = $_POST['donor_id'] ?? '';
            $donationDate = $_POST['donation_date'] ?? '';
            $units = intval($_POST['units'] ?? 0);
            $hospital = $_POST['hospital'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if ($donorId && $donationDate && $units > 0) {
                try {
                    // Get donor details
                    $donor = Database::fetch(
                        "SELECT d.*, u.email FROM donors d 
                         JOIN users u ON d.user_id = u.id 
                         WHERE d.id = :id",
                        ['id' => $donorId]
                    );
                    
                    if ($donor) {
                        $pdo = Database::getConnection();
                        $pdo->beginTransaction();
                        
                        // Record donation
                        $sql = "INSERT INTO donor_donations 
                                (donor_id, donation_date, units_donated, blood_type, hospital_location, notes, status) 
                                VALUES (:donor_id, :date, :units, :blood_type, :hospital, :notes, 'verified')";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'donor_id' => $donorId,
                            'date' => $donationDate,
                            'units' => $units,
                            'blood_type' => $donor['blood_type'],
                            'hospital' => $hospital,
                            'notes' => $notes
                        ]);
                        
                        // Update donor statistics
                        $sql = "UPDATE donors 
                                SET total_donations = total_donations + 1,
                                    last_donation_date = :date,
                                    total_units_donated = total_units_donated + :units
                                WHERE id = :donor_id";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'date' => $donationDate,
                            'units' => $units,
                            'donor_id' => $donorId
                        ]);
                        
                        // Update blood stock
                        $sql = "UPDATE blood_stocks 
                                SET units_available = units_available + :units,
                                    units_received = units_received + :units,
                                    last_updated = NOW(),
                                    updated_by = :user_id
                                WHERE blood_type = :blood_type";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'units' => $units,
                            'user_id' => $user['user_id'],
                            'blood_type' => $donor['blood_type']
                        ]);
                        
                        // Record transaction
                        $sql = "INSERT INTO blood_transactions 
                                (blood_type, transaction_type, units, source_destination, notes, performed_by) 
                                VALUES (:blood_type, 'received', :units, :source, :notes, :user_id)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'blood_type' => $donor['blood_type'],
                            'units' => $units,
                            'source' => "Donor: " . $donor['first_name'] . " " . $donor['last_name'],
                            'notes' => "Donation at " . $hospital . ". " . $notes,
                            'user_id' => $user['user_id']
                        ]);
                        
                        $pdo->commit();
                        
                        $_SESSION['success_message'] = "Donation recorded successfully! Stock updated for " . $donor['blood_type'];
                    }
                    
                } catch (PDOException $e) {
                    if (isset($pdo)) {
                        $pdo->rollBack();
                    }
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
            break;
            
        case 'verify_donation':
            $donationId = $_POST['donation_id'] ?? '';
            
            if ($donationId) {
                try {
                    $pdo = Database::getConnection();
                    $pdo->beginTransaction();
                    
                    // Get donation details
                    $donation = Database::fetch(
                        "SELECT dd.*, d.blood_type, d.first_name, d.last_name 
                         FROM donor_donations dd
                         JOIN donors d ON dd.donor_id = d.id
                         WHERE dd.id = :id",
                        ['id' => $donationId]
                    );
                    
                    if ($donation && $donation['status'] == 'pending') {
                        // Update donation status
                        $sql = "UPDATE donor_donations 
                                SET status = 'verified',
                                    verified_by = :user_id,
                                    verified_at = NOW()
                                WHERE id = :id";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'user_id' => $user['user_id'],
                            'id' => $donationId
                        ]);
                        
                        // Update donor statistics
                        $sql = "UPDATE donors 
                                SET total_donations = total_donations + 1,
                                    last_donation_date = :date,
                                    total_units_donated = total_units_donated + :units
                                WHERE id = :donor_id";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'date' => $donation['donation_date'],
                            'units' => $donation['units_donated'],
                            'donor_id' => $donation['donor_id']
                        ]);
                        
                        // Update blood stock
                        $sql = "UPDATE blood_stocks 
                                SET units_available = units_available + :units,
                                    units_received = units_received + :units,
                                    last_updated = NOW(),
                                    updated_by = :user_id
                                WHERE blood_type = :blood_type";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'units' => $donation['units_donated'],
                            'user_id' => $user['user_id'],
                            'blood_type' => $donation['blood_type']
                        ]);
                        
                        // Record transaction
                        $sql = "INSERT INTO blood_transactions 
                                (blood_type, transaction_type, units, source_destination, notes, performed_by) 
                                VALUES (:blood_type, 'received', :units, :source, :notes, :user_id)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'blood_type' => $donation['blood_type'],
                            'units' => $donation['units_donated'],
                            'source' => "Donor: " . $donation['first_name'] . " " . $donation['last_name'],
                            'notes' => "Donation at " . ($donation['hospital_location'] ?? 'Unknown'),
                            'user_id' => $user['user_id']
                        ]);
                        
                        $pdo->commit();
                        
                        $_SESSION['success_message'] = "Donation verified and stock updated!";
                    }
                    
                } catch (PDOException $e) {
                    if (isset($pdo)) {
                        $pdo->rollBack();
                    }
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
            break;
    }
    
    header('Location: donor_donations.php');
    exit();
}

// Get donor donations
$donations = Database::fetchAll(
    "SELECT dd.*, 
            d.first_name, d.last_name, d.blood_type, d.contact_number,
            u.email as donor_email,
            ver.email as verified_by_email
     FROM donor_donations dd
     JOIN donors d ON dd.donor_id = d.id
     JOIN users u ON d.user_id = u.id
     LEFT JOIN users ver ON dd.verified_by = ver.id
     ORDER BY dd.donation_date DESC, dd.created_at DESC
     LIMIT 50",
    []
);

// Get donors for dropdown
$donors = Database::fetchAll(
    "SELECT d.id, d.first_name, d.last_name, d.blood_type, d.contact_number, u.email
     FROM donors d
     JOIN users u ON d.user_id = u.id
     WHERE u.is_verified = TRUE
     ORDER BY d.first_name, d.last_name",
    []
);

// Get statistics
$stats = Database::fetch(
    "SELECT 
        COUNT(*) as total_donations,
        SUM(units_donated) as total_units,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_count
     FROM donor_donations",
    []
);

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Donor Donations Management</h1>
        <p class="text-gray-600">Record and verify donor blood donations</p>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-red-700 text-sm">Total Donations</p>
                    <h3 class="text-3xl font-bold text-red-800 mt-2"><?php echo $stats['total_donations'] ?? 0; ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-heart-pulse-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-700 text-sm">Total Units</p>
                    <h3 class="text-3xl font-bold text-blue-800 mt-2"><?php echo $stats['total_units'] ?? 0; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <i class="ri-drop-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-700 text-sm">Pending Verification</p>
                    <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo $stats['pending_count'] ?? 0; ?></h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <i class="ri-time-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-700 text-sm">Verified Donations</p>
                    <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo $stats['verified_count'] ?? 0; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <i class="ri-check-double-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Record New Donation -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Record New Donation</h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="record_donation">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Select Donor *</label>
                            <select name="donor_id" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                                <option value="">Select a donor</option>
                                <?php foreach ($donors as $donor): ?>
                                <option value="<?php echo $donor['id']; ?>">
                                    <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?> 
                                    (<?php echo $donor['blood_type']; ?>) - <?php echo htmlspecialchars($donor['email']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Donation Date *</label>
                            <input type="date" name="donation_date" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Units Donated *</label>
                            <input type="number" name="units" min="1" max="5" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                   placeholder="1-5 units" value="1">
                            <p class="text-sm text-gray-500 mt-1">Typically 1 unit = 450ml</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Hospital/Location</label>
                            <input type="text" name="hospital" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                   placeholder="e.g., General Hospital, Colombo">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-medium mb-2">Notes</label>
                            <textarea name="notes" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                      placeholder="Any additional notes about the donation"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="mt-6 w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-lg transition">
                        <i class="ri-save-line mr-2"></i> Record Donation & Update Stock
                    </button>
                </form>
            </div>
            
            <!-- Recent Donations -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Donations</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Donor</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Blood Type</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Units</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($donations as $donation): 
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'verified' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                            ?>
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($donation['donor_email']); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                                        <?php echo $donation['blood_type']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-600">
                                    <?php echo date('M j, Y', strtotime($donation['donation_date'])); ?>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-lg font-bold text-red-600">
                                        <?php echo $donation['units_donated']; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColors[$donation['status']] ?? 'bg-gray-100'; ?>">
                                        <?php echo ucfirst($donation['status']); ?>
                                    </span>
                                    <?php if ($donation['verified_by_email']): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        by <?php echo $donation['verified_by_email']; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4">
                                    <?php if ($donation['status'] == 'pending'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="verify_donation">
                                        <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                        <button type="submit" 
                                                class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200">
                                            Verify
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <button onclick="showDonationDetails(<?php echo htmlspecialchars(json_encode($donation)); ?>)"
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200">
                                        View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Donor Statistics & Quick Actions -->
        <div class="space-y-8">
            <!-- Top Donors -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Top Donors</h2>
                
                <?php
                $topDonors = Database::fetchAll(
                    "SELECT 
                        d.first_name, d.last_name, d.blood_type,
                        d.total_donations, d.total_units_donated,
                        DATE(d.last_donation_date) as last_donation
                     FROM donors d
                     WHERE d.total_donations > 0
                     ORDER BY d.total_units_donated DESC
                     LIMIT 5",
                    []
                );
                ?>
                
                <?php if (empty($topDonors)): ?>
                    <div class="text-center py-4">
                        <i class="ri-user-heart-line text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-600">No donation records yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($topDonors as $index => $donor): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                                    <span class="font-bold"><?php echo $index + 1; ?></span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo $donor['blood_type']; ?> • 
                                        <?php echo $donor['total_units_donated']; ?> units
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-red-600">
                                    <?php echo $donor['total_donations']; ?>
                                </div>
                                <div class="text-xs text-gray-500">donations</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="all_donors.php?sort=donations" class="text-red-600 text-sm hover:underline">
                            View All Donors →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Blood Type Distribution -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Donations by Blood Type</h2>
                
                <?php
                $bloodTypeStats = Database::fetchAll(
                    "SELECT 
                        d.blood_type,
                        COUNT(dd.id) as donation_count,
                        SUM(dd.units_donated) as total_units
                     FROM donors d
                     LEFT JOIN donor_donations dd ON d.id = dd.donor_id AND dd.status = 'verified'
                     GROUP BY d.blood_type
                     ORDER BY total_units DESC",
                    []
                );
                ?>
                
                <div class="space-y-3">
                    <?php foreach ($bloodTypeStats as $stat): ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium"><?php echo $stat['blood_type']; ?></span>
                            <span><?php echo $stat['total_units'] ?? 0; ?> units</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <?php 
                            $maxUnits = max(array_column($bloodTypeStats, 'total_units')) ?: 1;
                            $width = min(100, ($stat['total_units'] / $maxUnits) * 100);
                            ?>
                            <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo $width; ?>%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            <?php echo $stat['donation_count']; ?> donations
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6">
                <h3 class="font-bold text-red-800 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="blood_stocks.php"
                       class="flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-medium py-3 rounded-lg transition">
                        <i class="ri-drop-line mr-2"></i> Blood Stock Management
                    </a>
                    <a href="all_donors.php"
                       class="flex items-center justify-center bg-white hover:bg-gray-50 text-red-600 border border-red-600 font-medium py-3 rounded-lg transition">
                        <i class="ri-user-search-line mr-2"></i> View All Donors
                    </a>
                    <button onclick="showQuickDonationForm()"
                            class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-medium py-3 rounded-lg transition">
                        <i class="ri-add-line mr-2"></i> Quick Donation Entry
                    </button>
                </div>
                
                <div class="mt-6 pt-4 border-t border-red-300">
                    <p class="text-sm text-red-700">
                        <i class="ri-information-line mr-2"></i>
                        Donations automatically update blood stock
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Donation Details Modal -->
<div id="donationDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Donation Details</h3>
            
            <div id="donationDetailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            
            <div class="mt-6 text-center">
                <button type="button" onclick="closeModal('donationDetailsModal')" 
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showDonationDetails(donation) {
    const modal = document.getElementById('donationDetailsModal');
    const content = document.getElementById('donationDetailsContent');
    
    const statusColors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'verified': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    
    let html = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Donor Name</p>
                    <p class="font-medium">${donation.first_name} ${donation.last_name}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Blood Type</p>
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">${donation.blood_type}</span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Donation Date</p>
                    <p class="font-medium">${new Date(donation.donation_date).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Units Donated</p>
                    <p class="text-2xl font-bold text-red-600">${donation.units_donated}</p>
                </div>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Hospital/Location</p>
                <p class="font-medium">${donation.hospital_location || 'Not specified'}</p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Status</p>
                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusColors[donation.status] || 'bg-gray-100'}">
                    ${donation.status.charAt(0).toUpperCase() + donation.status.slice(1)}
                </span>
                ${donation.verified_by_email ? `
                <p class="text-xs text-gray-500 mt-1">Verified by: ${donation.verified_by_email}</p>
                ` : ''}
            </div>
            
            ${donation.notes ? `
            <div>
                <p class="text-sm text-gray-500">Notes</p>
                <p class="text-gray-700">${donation.notes}</p>
            </div>
            ` : ''}
            
            <div class="pt-4 border-t">
                <p class="text-sm text-gray-500">Contact Information</p>
                <p class="font-medium">${donation.donor_email}</p>
                <p class="text-sm text-gray-600">${donation.contact_number || 'No phone number'}</p>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modal on outside click
document.addEventListener('click', (e) => {
    if (e.target.id === 'donationDetailsModal') {
        closeModal('donationDetailsModal');
    }
});

function showQuickDonationForm() {
    // Scroll to the donation form
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require_once '../includes/footer.php'; ?>