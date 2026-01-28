<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_stock':
            $bloodType = $_POST['blood_type'] ?? '';
            $units = intval($_POST['units'] ?? 0);
            $source = $_POST['source'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if ($bloodType && $units > 0) {
                try {
                    $pdo = Database::getConnection();
                    $pdo->beginTransaction();
                    
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
                        'blood_type' => $bloodType
                    ]);
                    
                    // Record transaction
                    $sql = "INSERT INTO blood_transactions 
                            (blood_type, transaction_type, units, source_destination, notes, performed_by) 
                            VALUES (:blood_type, 'received', :units, :source, :notes, :user_id)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'blood_type' => $bloodType,
                        'units' => $units,
                        'source' => $source,
                        'notes' => $notes,
                        'user_id' => $user['user_id']
                    ]);
                    
                    $pdo->commit();
                    $_SESSION['success_message'] = "Added $units units of $bloodType to stock";
                    
                } catch (PDOException $e) {
                    if (isset($pdo)) {
                        $pdo->rollBack();
                    }
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
            break;
            
        case 'use_stock':
            $bloodType = $_POST['blood_type'] ?? '';
            $units = intval($_POST['units'] ?? 0);
            $destination = $_POST['destination'] ?? '';
            $purpose = $_POST['purpose'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if ($bloodType && $units > 0) {
                try {
                    $pdo = Database::getConnection();
                    $pdo->beginTransaction();
                    
                    // Check if enough stock is available
                    $currentStock = Database::fetch(
                        "SELECT units_available FROM blood_stocks WHERE blood_type = :blood_type",
                        ['blood_type' => $bloodType]
                    );
                    
                    if ($currentStock && $currentStock['units_available'] >= $units) {
                        // Update blood stock
                        $sql = "UPDATE blood_stocks 
                                SET units_available = units_available - :units,
                                    units_used = units_used + :units,
                                    last_updated = NOW(),
                                    updated_by = :user_id
                                WHERE blood_type = :blood_type";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'units' => $units,
                            'user_id' => $user['user_id'],
                            'blood_type' => $bloodType
                        ]);
                        
                        // Record transaction
                        $sql = "INSERT INTO blood_transactions 
                                (blood_type, transaction_type, units, source_destination, purpose, notes, performed_by) 
                                VALUES (:blood_type, 'used', :units, :destination, :purpose, :notes, :user_id)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            'blood_type' => $bloodType,
                            'units' => $units,
                            'destination' => $destination,
                            'purpose' => $purpose,
                            'notes' => $notes,
                            'user_id' => $user['user_id']
                        ]);
                        
                        $pdo->commit();
                        $_SESSION['success_message'] = "Used $units units of $bloodType from stock";
                    } else {
                        $_SESSION['error'] = "Insufficient stock available for $bloodType";
                    }
                    
                } catch (PDOException $e) {
                    if (isset($pdo)) {
                        $pdo->rollBack();
                    }
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
            break;
            
        case 'update_levels':
            $bloodType = $_POST['blood_type'] ?? '';
            $minimum = intval($_POST['minimum_level'] ?? 10);
            $maximum = intval($_POST['maximum_level'] ?? 100);
            
            if ($bloodType && $minimum >= 0 && $maximum > $minimum) {
                try {
                    $sql = "UPDATE blood_stocks 
                            SET minimum_level = :minimum,
                                maximum_level = :maximum,
                                last_updated = NOW(),
                                updated_by = :user_id
                            WHERE blood_type = :blood_type";
                    
                    Database::execute($sql, [
                        'minimum' => $minimum,
                        'maximum' => $maximum,
                        'user_id' => $user['user_id'],
                        'blood_type' => $bloodType
                    ]);
                    
                    $_SESSION['success_message'] = "Updated levels for $bloodType";
                    
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
            break;
            
        case 'adjust_stock':
            $bloodType = $_POST['blood_type'] ?? '';
            $units = intval($_POST['units'] ?? 0);
            $reason = $_POST['reason'] ?? '';
            
            if ($bloodType) {
                try {
                    $pdo = Database::getConnection();
                    $pdo->beginTransaction();
                    
                    // Update blood stock
                    $sql = "UPDATE blood_stocks 
                            SET units_available = units_available + :units,
                                last_updated = NOW(),
                                updated_by = :user_id
                            WHERE blood_type = :blood_type";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'units' => $units,
                        'user_id' => $user['user_id'],
                        'blood_type' => $bloodType
                    ]);
                    
                    // Record transaction
                    $transactionType = $units > 0 ? 'adjusted_in' : 'adjusted_out';
                    $sql = "INSERT INTO blood_transactions 
                            (blood_type, transaction_type, units, notes, performed_by) 
                            VALUES (:blood_type, :type, :units, :reason, :user_id)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'blood_type' => $bloodType,
                        'type' => $transactionType,
                        'units' => abs($units),
                        'reason' => $reason,
                        'user_id' => $user['user_id']
                    ]);
                    
                    $pdo->commit();
                    $_SESSION['success_message'] = "Stock adjusted for $bloodType by " . abs($units) . " units";
                    
                } catch (PDOException $e) {
                    if (isset($pdo)) {
                        $pdo->rollBack();
                    }
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
            break;
    }
    
    header('Location: blood_stocks.php');
    exit();
}

// Get blood stocks data
$bloodStocks = Database::fetchAll(
    "SELECT * FROM blood_stocks ORDER BY blood_type",
    []
);

// Get recent transactions
$recentTransactions = Database::fetchAll(
    "SELECT bt.*, u.email as performed_by_email
     FROM blood_transactions bt
     LEFT JOIN users u ON bt.performed_by = u.id
     ORDER BY bt.transaction_date DESC
     LIMIT 10",
    []
);

// Get statistics
$stats = Database::fetch(
    "SELECT 
        SUM(units_available) as total_available,
        SUM(units_used) as total_used,
        SUM(units_received) as total_received,
        COUNT(CASE WHEN units_available < minimum_level THEN 1 END) as low_stock_count,
        COUNT(CASE WHEN units_available = 0 THEN 1 END) as out_of_stock_count
     FROM blood_stocks",
    []
);

// Get donor statistics
$donorStats = Database::fetch(
    "SELECT 
        COUNT(*) as total_donors,
        SUM(CASE WHEN total_donations > 0 THEN 1 ELSE 0 END) as active_donors,
        SUM(total_units_donated) as total_donated_units,
        AVG(total_units_donated) as avg_units_per_donor
     FROM donors",
    []
);

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Blood Stock Management</h1>
        <p class="text-gray-600">Manage blood inventory and track transactions</p>
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
                    <p class="text-red-700 text-sm">Total Available</p>
                    <h3 class="text-3xl font-bold text-red-800 mt-2"><?php echo $stats['total_available'] ?? 0; ?></h3>
                    <p class="text-red-600 text-sm mt-1">units</p>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                    <i class="ri-drop-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-700 text-sm">Total Used</p>
                    <h3 class="text-3xl font-bold text-blue-800 mt-2"><?php echo $stats['total_used'] ?? 0; ?></h3>
                    <p class="text-blue-600 text-sm mt-1">units</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                    <i class="ri-heart-pulse-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-700 text-sm">Low Stock</p>
                    <h3 class="text-3xl font-bold text-yellow-800 mt-2"><?php echo $stats['low_stock_count'] ?? 0; ?></h3>
                    <p class="text-yellow-600 text-sm mt-1">blood types</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center">
                    <i class="ri-alert-line text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-700 text-sm">Total Received</p>
                    <h3 class="text-3xl font-bold text-green-800 mt-2"><?php echo $stats['total_received'] ?? 0; ?></h3>
                    <p class="text-green-600 text-sm mt-1">units</p>
                </div>
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                    <i class="ri-inbox-archive-line text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Stock Overview -->
        <div class="lg:col-span-2">
            <!-- Stock Table -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Blood Stock Overview</h2>
                    <span class="text-sm text-gray-500">Last updated: Today</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Blood Type</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Available</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Used</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Received</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Min/Max</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($bloodStocks as $stock): 
                                $status = '';
                                $statusColor = '';
                                
                                if ($stock['units_available'] == 0) {
                                    $status = 'Out of Stock';
                                    $statusColor = 'bg-red-100 text-red-800';
                                } elseif ($stock['units_available'] < $stock['minimum_level']) {
                                    $status = 'Low Stock';
                                    $statusColor = 'bg-yellow-100 text-yellow-800';
                                } elseif ($stock['units_available'] > $stock['maximum_level'] * 0.8) {
                                    $status = 'High Stock';
                                    $statusColor = 'bg-green-100 text-green-800';
                                } else {
                                    $status = 'Normal';
                                    $statusColor = 'bg-blue-100 text-blue-800';
                                }
                                
                                $percentage = min(100, ($stock['units_available'] / $stock['maximum_level']) * 100);
                            ?>
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-bold text-gray-900"><?php echo $stock['blood_type']; ?></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-2xl font-bold text-gray-900"><?php echo $stock['units_available']; ?></div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-600"><?php echo $stock['units_used']; ?></td>
                                <td class="px-4 py-4 text-gray-600"><?php echo $stock['units_received']; ?></td>
                                <td class="px-4 py-4">
                                    <div class="text-sm text-gray-600">Min: <?php echo $stock['minimum_level']; ?></div>
                                    <div class="text-sm text-gray-600">Max: <?php echo $stock['maximum_level']; ?></div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="showAddStockModal('<?php echo $stock['blood_type']; ?>')"
                                                class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200">
                                            Add
                                        </button>
                                        <button onclick="showUseStockModal('<?php echo $stock['blood_type']; ?>', <?php echo $stock['units_available']; ?>)"
                                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200">
                                            Use
                                        </button>
                                        <button onclick="showAdjustStockModal('<?php echo $stock['blood_type']; ?>')"
                                                class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-sm hover:bg-yellow-200">
                                            Adjust
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Donor Statistics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Donor Statistics</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-700">Total Verified Donors</span>
                        <span class="text-lg font-bold text-red-600"><?php echo $donorStats['total_donors'] ?? 0; ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-700">Active Donors</span>
                        <span class="text-lg font-bold text-green-600"><?php echo $donorStats['active_donors'] ?? 0; ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-700">Total Donated Units</span>
                        <span class="text-lg font-bold text-blue-600"><?php echo $donorStats['total_donated_units'] ?? 0; ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-700">Avg Units per Donor</span>
                        <span class="text-lg font-bold text-purple-600"><?php echo round($donorStats['avg_units_per_donor'] ?? 0, 1); ?></span>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="donor_donations.php" class="text-red-600 text-sm hover:underline">
                        <i class="ri-arrow-right-line mr-1"></i> Manage Donations
                    </a>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6">
                <h3 class="font-bold text-red-800 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button onclick="showAddStockModal()"
                            class="w-full flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-medium py-3 rounded-lg transition">
                        <i class="ri-add-line mr-2"></i> Add New Stock
                    </button>
                    <button onclick="showUseStockModal()"
                            class="w-full flex items-center justify-center bg-white hover:bg-gray-50 text-red-600 border border-red-600 font-medium py-3 rounded-lg transition">
                        <i class="ri-subtract-line mr-2"></i> Use Stock
                    </button>
                    <a href="transactions.php"
                       class="block text-center bg-white hover:bg-gray-50 text-red-600 border border-red-600 font-medium py-3 rounded-lg transition">
                        View All Transactions
                    </a>
                </div>
            </div>
                
            </div>
        </div>
        
        <!-- Right Column: Recent Transactions & Quick Actions -->
        <div class="space-y-8">
            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Transactions</h2>
                
                <?php if (empty($recentTransactions)): ?>
                    <div class="text-center py-8">
                        <i class="ri-exchange-line text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">No recent transactions</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recentTransactions as $transaction): 
                            $typeColors = [
                                'received' => 'bg-green-100 text-green-800',
                                'used' => 'bg-red-100 text-red-800',
                                'adjusted_in' => 'bg-blue-100 text-blue-800',
                                'adjusted_out' => 'bg-yellow-100 text-yellow-800'
                            ];
                        ?>
                        <div class="border-l-4 <?php echo $transaction['transaction_type'] == 'received' ? 'border-green-500' : 
                                                    ($transaction['transaction_type'] == 'used' ? 'border-red-500' : 'border-yellow-500'); ?> pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php echo $transaction['blood_type']; ?> - 
                                        <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo $transaction['units']; ?> units
                                        <?php if ($transaction['source_destination']): ?>
                                            • <?php echo htmlspecialchars($transaction['source_destination']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium <?php echo $typeColors[$transaction['transaction_type']] ?? 'bg-gray-100'; ?>">
                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="ri-time-line mr-1"></i>
                                <?php echo date('M j, g:i A', strtotime($transaction['transaction_date'])); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="transactions.php" class="text-red-600 text-sm hover:underline">View All Transactions →</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Stock Alerts -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Stock Alerts</h2>
                
                <?php
                $lowStockItems = array_filter($bloodStocks, function($stock) {
                    return $stock['units_available'] < $stock['minimum_level'];
                });
                ?>
                
                <?php if (empty($lowStockItems)): ?>
                    <div class="text-center py-4">
                        <i class="ri-check-double-line text-3xl text-green-400 mb-2"></i>
                        <p class="text-green-600 font-medium">All stocks at safe levels</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($lowStockItems as $item): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-yellow-800"><?php echo $item['blood_type']; ?></p>
                                    <p class="text-sm text-yellow-700">Available: <?php echo $item['units_available']; ?> / Min: <?php echo $item['minimum_level']; ?></p>
                                </div>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">
                                    Low Stock
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="addStockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Add Stock</h3>
            <p class="text-gray-600 mb-6" id="addStockTitle"></p>
            
            <form id="addStockForm" method="POST">
                <input type="hidden" name="action" value="add_stock">
                <input type="hidden" name="blood_type" id="addStockBloodType">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Units to Add</label>
                        <input type="number" name="units" min="1" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="Enter number of units">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Source</label>
                        <input type="text" name="source"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="e.g., Donor name, Hospital">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeModal('addStockModal')" 
                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 rounded-lg transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 rounded-lg transition">
                            Add Stock
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="useStockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Use Stock</h3>
            <p class="text-gray-600 mb-6" id="useStockTitle"></p>
            
            <form id="useStockForm" method="POST">
                <input type="hidden" name="action" value="use_stock">
                <input type="hidden" name="blood_type" id="useStockBloodType">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Units to Use</label>
                        <input type="number" name="units" min="1" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="Enter number of units">
                        <p class="text-xs text-gray-500 mt-1" id="availableStock"></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Destination</label>
                        <input type="text" name="destination" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="e.g., Hospital name, Patient">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Purpose</label>
                        <select name="purpose" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                            <option value="">Select Purpose</option>
                            <option value="surgery">Surgery</option>
                            <option value="emergency">Emergency</option>
                            <option value="transfusion">Transfusion</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeModal('useStockModal')" 
                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 rounded-lg transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-3 rounded-lg transition">
                            Use Stock
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="adjustStockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Adjust Stock</h3>
            <p class="text-gray-600 mb-6" id="adjustStockTitle"></p>
            
            <form id="adjustStockForm" method="POST">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" name="blood_type" id="adjustStockBloodType">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Adjustment Amount</label>
                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="document.getElementById('adjustUnits').stepDown()" 
                                    class="px-3 py-2 bg-gray-200 rounded-lg">-</button>
                            <input type="number" name="units" id="adjustUnits" value="0"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-center">
                            <button type="button" onclick="document.getElementById('adjustUnits').stepUp()" 
                                    class="px-3 py-2 bg-gray-200 rounded-lg">+</button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Positive number adds stock, negative reduces stock</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm mb-1">Reason for Adjustment</label>
                        <textarea name="reason" rows="2" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                  placeholder="Explain the reason for adjustment"></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeModal('adjustStockModal')" 
                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 rounded-lg transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 rounded-lg transition">
                            Adjust Stock
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function showAddStockModal(bloodType = '') {
    const modal = document.getElementById('addStockModal');
    const title = document.getElementById('addStockTitle');
    const bloodTypeInput = document.getElementById('addStockBloodType');
    
    if (bloodType) {
        title.textContent = 'Add stock for ' + bloodType;
        bloodTypeInput.value = bloodType;
    } else {
        title.textContent = 'Add new stock';
        bloodTypeInput.value = '';
    }
    
    modal.classList.remove('hidden');
}

function showUseStockModal(bloodType = '', available = 0) {
    const modal = document.getElementById('useStockModal');
    const title = document.getElementById('useStockTitle');
    const bloodTypeInput = document.getElementById('useStockBloodType');
    const availableStock = document.getElementById('availableStock');
    
    if (bloodType) {
        title.textContent = 'Use stock for ' + bloodType;
        bloodTypeInput.value = bloodType;
        availableStock.textContent = 'Available: ' + available + ' units';
    } else {
        title.textContent = 'Use stock';
        bloodTypeInput.value = '';
        availableStock.textContent = '';
    }
    
    modal.classList.remove('hidden');
}

function showAdjustStockModal(bloodType = '') {
    const modal = document.getElementById('adjustStockModal');
    const title = document.getElementById('adjustStockTitle');
    const bloodTypeInput = document.getElementById('adjustStockBloodType');
    
    if (bloodType) {
        title.textContent = 'Adjust stock for ' + bloodType;
        bloodTypeInput.value = bloodType;
    } else {
        title.textContent = 'Adjust stock';
        bloodTypeInput.value = '';
    }
    
    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modals on outside click
document.addEventListener('click', (e) => {
    if (e.target.id === 'addStockModal' || e.target.id === 'useStockModal' || e.target.id === 'adjustStockModal') {
        e.target.classList.add('hidden');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>