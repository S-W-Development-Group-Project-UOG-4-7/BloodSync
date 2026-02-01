<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

// Get filter parameters
$bloodType = $_GET['blood_type'] ?? '';
$transactionType = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Check if it's a CSV export request
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';

// Build query with filters
$whereClause = "WHERE 1=1";
$params = [];

if ($bloodType) {
    $whereClause .= " AND bt.blood_type = :blood_type";
    $params['blood_type'] = $bloodType;
}

if ($transactionType) {
    $whereClause .= " AND bt.transaction_type = :type";
    $params['type'] = $transactionType;
}

if ($dateFrom) {
    $whereClause .= " AND DATE(bt.transaction_date) >= :date_from";
    $params['date_from'] = $dateFrom;
}

if ($dateTo) {
    $whereClause .= " AND DATE(bt.transaction_date) <= :date_to";
    $params['date_to'] = $dateTo;
}

// Get all transactions
$transactions = Database::fetchAll(
    "SELECT bt.*, u.email as performed_by_email
     FROM blood_transactions bt
     LEFT JOIN users u ON bt.performed_by = u.id
     $whereClause
     ORDER BY bt.transaction_date DESC",
    $params
);

// Get unique blood types for filter
$bloodTypes = Database::fetchAll(
    "SELECT DISTINCT blood_type FROM blood_stocks ORDER BY blood_type",
    []
);

// Calculate summary values
$received = 0;
$used = 0;
$adjustedIn = 0;
$adjustedOut = 0;

if (!empty($transactions)) {
    foreach ($transactions as $transaction) {
        switch ($transaction['transaction_type']) {
            case 'received':
                $received += $transaction['units'];
                break;
            case 'used':
                $used += $transaction['units'];
                break;
            case 'adjusted_in':
                $adjustedIn += $transaction['units'];
                break;
            case 'adjusted_out':
                $adjustedOut += $transaction['units'];
                break;
        }
    }
}

// If CSV export is requested
if ($exportCsv) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=blood_transactions_' . date('Y-m-d_His') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV Headers
    $headers = [
        'Transaction ID',
        'Date & Time',
        'Blood Type',
        'Transaction Type',
        'Units',
        'Source/Destination',
        'Purpose',
        'Performed By',
        'Notes'
    ];
    
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($transactions as $transaction) {
        $row = [
            $transaction['id'] ?? '',
            date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])),
            $transaction['blood_type'] ?? '',
            ucfirst(str_replace('_', ' ', $transaction['transaction_type'] ?? '')),
            ($transaction['transaction_type'] == 'received' ? '+' : '-') . $transaction['units'],
            $transaction['source_destination'] ?? '',
            $transaction['purpose'] ?? '',
            $transaction['performed_by_email'] ?? 'System',
            $transaction['notes'] ?? ''
        ];
        
        fputcsv($output, $row);
    }
    
    // Add summary section
    fputcsv($output, ['']); // Empty row
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Transactions:', count($transactions)]);
    fputcsv($output, ['Total Units Received:', $received]);
    fputcsv($output, ['Total Units Used:', $used]);
    fputcsv($output, ['Adjusted In:', $adjustedIn]);
    fputcsv($output, ['Adjusted Out:', $adjustedOut]);
    fputcsv($output, ['Net Change:', ($received + $adjustedIn) - ($used + $adjustedOut)]);
    fputcsv($output, ['']); // Empty row
    fputcsv($output, ['Report Generated:', date('F j, Y H:i:s')]);
    fputcsv($output, ['Filters Applied:', 'Blood Type: ' . ($bloodType ?: 'All')]);
    fputcsv($output, ['', 'Transaction Type: ' . ($transactionType ?: 'All')]);
    fputcsv($output, ['', 'Date Range: ' . ($dateFrom ?: 'Start') . ' to ' . ($dateTo ?: 'End')]);
    
    fclose($output);
    exit;
}

// Continue with normal HTML rendering if not CSV export
require_once '../includes/header.php';
?>

<!-- Add JavaScript functions before the content -->
<script>
function printReport() {
    const modal = document.getElementById('print-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePrintModal() {
    const modal = document.getElementById('print-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function generatePrint() {
    const includeSummary = document.getElementById('include-summary').checked;
    const includeFilters = document.getElementById('include-filters').checked;
    const includeTimestamp = document.getElementById('include-timestamp').checked;
    const includeLogo = document.getElementById('include-logo').checked;
    
    closePrintModal();
    
    // Open print window
    const printWindow = window.open('', '_blank');
    
    // Get current date and time
    const now = new Date();
    const timestamp = now.toLocaleString();
    
    // Build print content
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>BloodSync - Transactions Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #DC2626; margin-bottom: 10px; }
                .title { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
                .subtitle { color: #6B7280; margin-bottom: 10px; }
                .timestamp { color: #6B7280; font-size: 12px; margin-bottom: 20px; }
                .filters { background: #F3F4F6; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .filters h3 { margin-top: 0; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #F9FAFB; text-align: left; padding: 10px; border: 1px solid #E5E7EB; }
                td { padding: 10px; border: 1px solid #E5E7EB; }
                .summary { background: #F9FAFB; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .summary h3 { margin-top: 0; }
                .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
                .summary-item { padding: 10px; border-radius: 5px; }
                .received { background: #D1FAE5; color: #065F46; }
                .used { background: #FEE2E2; color: #991B1B; }
                .total { background: #DBEAFE; color: #1E40AF; }
                .footer { margin-top: 30px; text-align: center; color: #6B7280; font-size: 12px; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
    `;
    
    // Add header
    if (includeLogo) {
        printContent += `<div class="logo">BloodSync</div>`;
    }
    printContent += `<div class="title">Blood Transactions Report</div>`;
    if (includeTimestamp) {
        printContent += `<div class="timestamp">Generated on: ${timestamp}</div>`;
    }
    
    // Add filters if selected
    if (includeFilters) {
        printContent += `
            <div class="filters">
                <h3>Filter Criteria</h3>
                <p>Blood Type: ${'<?php echo $bloodType ?: "All"; ?>'}</p>
                <p>Transaction Type: ${'<?php echo $transactionType ? ucfirst($transactionType) : "All"; ?>'}</p>
                <p>Date Range: ${'<?php echo $dateFrom ? date("M j, Y", strtotime($dateFrom)) : "Start"; ?>'} to ${'<?php echo $dateTo ? date("M j, Y", strtotime($dateTo)) : "End"; ?>'}</p>
            </div>
        `;
    }
    
    // Add table
    printContent += `
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Blood Type</th>
                    <th>Type</th>
                    <th>Units</th>
                    <th>Source/Destination</th>
                    <th>Purpose</th>
                    <th>Performed By</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // Add table rows
    <?php if (!empty($transactions)): ?>
        <?php foreach ($transactions as $transaction): ?>
            printContent += `
                <tr>
                    <td>${'<?php echo date("M j, Y\\\\nH:i", strtotime($transaction["transaction_date"])); ?>'}</td>
                    <td>${'<?php echo $transaction["blood_type"]; ?>'}</td>
                    <td>${'<?php echo ucfirst(str_replace("_", " ", $transaction["transaction_type"])); ?>'}</td>
                    <td>${'<?php echo $transaction["transaction_type"] == "received" ? "+" : "-"; ?><?php echo $transaction["units"]; ?>'}</td>
                    <td>${'<?php echo htmlspecialchars(substr($transaction["source_destination"] ?? "-", 0, 30)); ?>'}</td>
                    <td>${'<?php echo htmlspecialchars(substr($transaction["purpose"] ?? "-", 0, 30)); ?>'}</td>
                    <td>${'<?php echo htmlspecialchars(substr($transaction["performed_by_email"] ?? "System", 0, 25)); ?>'}</td>
                </tr>
            `;
        <?php endforeach; ?>
    <?php else: ?>
        printContent += `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #6B7280;">
                    No transactions found
                </td>
            </tr>
        `;
    <?php endif; ?>
    
    printContent += `
            </tbody>
        </table>
    `;
    
    // Add summary if selected
    if (includeSummary && <?php echo !empty($transactions) ? 'true' : 'false'; ?>) {
        printContent += `
            <div class="summary">
                <h3>Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <p>Total Transactions</p>
                        <p style="font-size: 20px; font-weight: bold;">${'<?php echo count($transactions); ?>'}</p>
                    </div>
                    <div class="summary-item received">
                        <p>Total Received</p>
                        <p style="font-size: 20px; font-weight: bold;">+${'<?php echo $received; ?>'}</p>
                    </div>
                    <div class="summary-item used">
                        <p>Total Used</p>
                        <p style="font-size: 20px; font-weight: bold;">-${'<?php echo $used; ?>'}</p>
                    </div>
                    <div class="summary-item total">
                        <p>Net Change</p>
                        <p style="font-size: 20px; font-weight: bold;">${'<?php echo ($received + $adjustedIn) - ($used + $adjustedOut); ?>'}</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    printContent += `
            <div class="footer">
                <p>BloodSync Blood Bank Management System</p>
                <p>Report generated automatically</p>
            </div>
        </body>
        </html>
    `;
    
    // Write content and print
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
    // Wait a moment for content to load, then print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Helper function to add query parameters
function addQueryParams(params) {
    const url = new URL(window.location.href);
    Object.keys(params).forEach(key => {
        url.searchParams.set(key, params[key]);
    });
    return url.toString();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('print-modal');
    if (event.target === modal) {
        closePrintModal();
    }
}
</script>

<?php require_once 'admin_nav.php'; ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Blood Transactions</h1>
        <p class="text-gray-600">View all blood stock transactions</p>
    </div>
    
    <!-- Status Messages -->
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
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Filters</h2>
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm mb-1">Blood Type</label>
                <select name="blood_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Types</option>
                    <?php foreach ($bloodTypes as $type): ?>
                    <option value="<?php echo $type['blood_type']; ?>" <?php echo $bloodType == $type['blood_type'] ? 'selected' : ''; ?>>
                        <?php echo $type['blood_type']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm mb-1">Transaction Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Types</option>
                    <option value="received" <?php echo $transactionType == 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="used" <?php echo $transactionType == 'used' ? 'selected' : ''; ?>>Used</option>
                    <option value="adjusted_in" <?php echo $transactionType == 'adjusted_in' ? 'selected' : ''; ?>>Adjusted In</option>
                    <option value="adjusted_out" <?php echo $transactionType == 'adjusted_out' ? 'selected' : ''; ?>>Adjusted Out</option>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm mb-1">Date From</label>
                <input type="date" name="date_from" value="<?php echo $dateFrom; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm mb-1">Date To</label>
                <input type="date" name="date_to" value="<?php echo $dateTo; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            
            <div class="md:col-span-4 flex space-x-3">
                <button type="submit" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Apply Filters
                </button>
                <a href="transactions.php" 
                   class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Clear Filters
                </a>
                <button type="button" onclick="printReport()"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Print Report
                </button>
                <a href="<?php echo add_query_params(['export' => 'csv']); ?>" 
                   class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Export CSV
                </a>
            </div>
        </form>
    </div>
    
    <!-- Transactions Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900">All Transactions</h2>
            <span class="text-gray-600"><?php echo count($transactions); ?> records found</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full" id="transactions-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source/Destination</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No transactions found
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): 
                            $typeColors = [
                                'received' => 'bg-green-100 text-green-800',
                                'used' => 'bg-red-100 text-red-800',
                                'adjusted_in' => 'bg-blue-100 text-blue-800',
                                'adjusted_out' => 'bg-yellow-100 text-yellow-800'
                            ];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('g:i A', strtotime($transaction['transaction_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                    <?php echo $transaction['blood_type']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $typeColors[$transaction['transaction_type']] ?? 'bg-gray-100'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold <?php echo $transaction['transaction_type'] == 'received' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transaction['transaction_type'] == 'received' ? '+' : '-'; ?>
                                    <?php echo $transaction['units']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($transaction['source_destination'] ?? '-'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($transaction['purpose'] ?? '-'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($transaction['performed_by_email'] ?? 'System'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate">
                                    <?php echo htmlspecialchars($transaction['notes'] ?? '-'); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        <?php if (!empty($transactions)): ?>
        <div class="p-6 border-t bg-gray-50">
            <h3 class="text-lg font-bold text-gray-900 mb-3">Summary</h3>
            <div class="grid md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg shadow">
                    <p class="text-gray-500 text-sm">Total Transactions</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo count($transactions); ?></p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg shadow">
                    <p class="text-green-600 text-sm">Total Received</p>
                    <p class="text-2xl font-bold text-green-700">+<?php echo $received; ?></p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg shadow">
                    <p class="text-red-600 text-sm">Total Used</p>
                    <p class="text-2xl font-bold text-red-700">-<?php echo $used; ?></p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg shadow">
                    <p class="text-blue-600 text-sm">Net Change</p>
                    <p class="text-2xl font-bold text-blue-700"><?php echo ($received + $adjustedIn) - ($used + $adjustedOut); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Export Options -->
        <div class="p-6 border-t bg-gray-50">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Showing <?php echo count($transactions); ?> transactions
                </div>
                <div class="flex space-x-3">
                    <a href="blood_stocks.php" class="text-red-600 hover:underline">
                        ← Back to Stock Management
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Report Modal -->
<div id="print-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-xl bg-white">
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Print Report</h3>
            <p class="text-gray-600">Choose what to include in the report:</p>
        </div>
        
        <div class="mb-6">
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" id="include-summary" checked class="mr-2">
                    <span>Include summary section</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" id="include-filters" checked class="mr-2">
                    <span>Include filter criteria</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" id="include-timestamp" checked class="mr-2">
                    <span>Include timestamp</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" id="include-logo" checked class="mr-2">
                    <span>Include BloodSync logo</span>
                </label>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="button" 
                    onclick="closePrintModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </button>
            <button type="button" 
                    onclick="generatePrint()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Generate Print
            </button>
        </div>
    </div>
</div>

<?php
// Helper function to add query parameters (PHP version)
function add_query_params($params) {
    $currentParams = $_GET;
    $currentParams = array_merge($currentParams, $params);
    return '?' . http_build_query($currentParams);
}
?>

<?php require_once '../includes/footer.php'; ?>