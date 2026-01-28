<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $blood_type = $_GET['blood_type'] ?? '';
    $urgency = $_GET['urgency'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Build query
    $query = "SELECT br.*, h.hospital_name, h.registration_number, h.location as hospital_location
        FROM blood_requests br
        JOIN hospitals h ON br.hospital_id = h.id
        WHERE 1=1";
        
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (br.patient_name ILIKE :search OR 
                         br.request_number ILIKE :search OR 
                         h.hospital_name ILIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    if (!empty($status) && $status !== 'all') {
        $query .= " AND br.status = :status";
        $params['status'] = $status;
    }
    
    if (!empty($blood_type) && $blood_type !== 'all') {
        $query .= " AND br.blood_type = :blood_type";
        $params['blood_type'] = $blood_type;
    }
    
    if (!empty($urgency) && $urgency !== 'all') {
        $query .= " AND br.urgency_level = :urgency";
        $params['urgency'] = $urgency;
    }
    
    if (!empty($date_from)) {
        $query .= " AND br.required_date >= :date_from";
        $params['date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND br.required_date <= :date_to";
        $params['date_to'] = $date_to;
    }
    
    $query .= " ORDER BY 
        CASE br.urgency_level 
            WHEN 'critical' THEN 1
            WHEN 'urgent' THEN 2
            ELSE 3
        END, 
        br.required_date ASC";
    
    $bloodRequests = Database::fetchAll($query, $params);
    
    if ($exportType === 'excel') {
        // Generate CSV file (Excel compatible)
        $filename = 'blood_requests_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // Add headers
        fputcsv($output, [
            'Request Number',
            'Patient Name',
            'Patient Age',
            'Patient Sex',
            'Patient Ward',
            'Hospital Name',
            'Hospital Location',
            'Registration Number',
            'Blood Type',
            'Units Required',
            'Urgency Level',
            'Status',
            'Required Date',
            'Created Date',
            'Reason',
            'Notes'
        ]);
        
        // Add data rows
        foreach ($bloodRequests as $request) {
            fputcsv($output, [
                $request['request_number'],
                $request['patient_name'],
                $request['patient_age'],
                $request['patient_sex'],
                $request['patient_ward'],
                $request['hospital_name'],
                $request['hospital_location'],
                $request['registration_number'],
                $request['blood_type'],
                $request['units_required'],
                $request['urgency_level'],
                $request['status'],
                date('Y-m-d', strtotime($request['required_date'])),
                date('Y-m-d H:i:s', strtotime($request['created_at'])),
                $request['reason'] ?? '',
                $request['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
        
    } elseif ($exportType === 'pdf') {
        // Generate PDF using HTML that can be printed/saved as PDF
        $filename = 'blood_requests_' . date('Ymd_His') . '.html';
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Calculate summary stats
        $totalRequests = count($bloodRequests);
        $totalUnits = array_sum(array_column($bloodRequests, 'units_required'));
        $pendingCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'pending'));
        $approvedCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'approved'));
        $fulfilledCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'fulfilled'));
        $rejectedCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'rejected'));
        $criticalCount = count(array_filter($bloodRequests, fn($r) => $r['urgency_level'] === 'critical'));
        
        // Build filter text
        $filterText = 'Filters Applied: ';
        $filters = [];
        if (!empty($search)) $filters[] = "Search: '$search'";
        if (!empty($status) && $status !== 'all') $filters[] = "Status: " . ucfirst($status);
        if (!empty($blood_type) && $blood_type !== 'all') $filters[] = "Blood Type: $blood_type";
        if (!empty($urgency) && $urgency !== 'all') $filters[] = "Urgency: " . ucfirst($urgency);
        if (!empty($date_from)) $filters[] = "From: $date_from";
        if (!empty($date_to)) $filters[] = "To: $date_to";
        
        if (empty($filters)) {
            $filterText = 'Showing all blood requests';
        } else {
            $filterText .= implode(', ', $filters);
        }
        
        // Generate HTML for PDF
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Blood Requests Report</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px; 
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px;
                    border-bottom: 3px solid #dc2626;
                    padding-bottom: 20px;
                }
                .header h1 { 
                    color: #dc2626; 
                    margin: 0;
                    font-size: 28px;
                }
                .header h2 { 
                    color: #666; 
                    margin: 10px 0 0 0;
                    font-size: 18px;
                    font-weight: normal;
                }
                .summary-box {
                    background-color: #f9f9f9;
                    border: 1px solid #ddd;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 8px;
                }
                .summary-box h3 {
                    margin-top: 0;
                    color: #333;
                    border-bottom: 2px solid #ddd;
                    padding-bottom: 10px;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 15px;
                    margin-bottom: 20px;
                }
                .stat-card {
                    background-color: white;
                    border: 1px solid #e5e5e5;
                    border-radius: 6px;
                    padding: 15px;
                    text-align: center;
                }
                .stat-value {
                    font-size: 24px;
                    font-weight: bold;
                    margin: 10px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    font-size: 12px;
                }
                th {
                    background-color: #f8f9fa;
                    border: 1px solid #dee2e6;
                    padding: 12px 8px;
                    text-align: left;
                    font-weight: bold;
                    color: #333;
                }
                td {
                    border: 1px solid #dee2e6;
                    padding: 10px 8px;
                    vertical-align: top;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .status-pending { color: #e6b800; font-weight: bold; }
                .status-approved { color: #006600; font-weight: bold; }
                .status-fulfilled { color: #0000cc; font-weight: bold; }
                .status-rejected { color: #cc0000; font-weight: bold; }
                .urgency-critical { color: #ff3300; font-weight: bold; }
                .urgency-urgent { color: #ff9900; font-weight: bold; }
                .urgency-normal { color: #0066cc; }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                    table { page-break-inside: auto; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                    .header { border-bottom: 2px solid #000; }
                }
                .filter-info {
                    background-color: #fff8e1;
                    border-left: 4px solid #ffc107;
                    padding: 12px;
                    margin: 15px 0;
                    font-size: 13px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Blood Bank Management System</h1>
                <h2>Blood Requests Report</h2>
                <p>Generated on: ' . date('F j, Y, g:i a') . '</p>
            </div>
            
            <div class="filter-info">
                <strong>' . $filterText . '</strong>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div>Total Requests</div>
                    <div class="stat-value">' . $totalRequests . '</div>
                </div>
                <div class="stat-card">
                    <div>Total Units</div>
                    <div class="stat-value">' . $totalUnits . '</div>
                </div>
                <div class="stat-card">
                    <div>Critical Requests</div>
                    <div class="stat-value">' . $criticalCount . '</div>
                </div>
                <div class="stat-card">
                    <div>Fulfillment Rate</div>
                    <div class="stat-value">' . ($totalRequests > 0 ? round(($fulfilledCount / $totalRequests) * 100) : 0) . '%</div>
                </div>
            </div>
            
            <div class="summary-box">
                <h3>Request Summary</h3>
                <table style="width: 100%; margin: 0;">
                    <tr>
                        <td><strong>Total Requests:</strong></td>
                        <td>' . $totalRequests . '</td>
                        <td><strong>Pending:</strong></td>
                        <td>' . $pendingCount . '</td>
                    </tr>
                    <tr>
                        <td><strong>Total Units Required:</strong></td>
                        <td>' . $totalUnits . '</td>
                        <td><strong>Approved:</strong></td>
                        <td>' . $approvedCount . '</td>
                    </tr>
                    <tr>
                        <td><strong>Average Units/Request:</strong></td>
                        <td>' . ($totalRequests > 0 ? round($totalUnits / $totalRequests, 2) : 0) . '</td>
                        <td><strong>Fulfilled:</strong></td>
                        <td>' . $fulfilledCount . '</td>
                    </tr>
                    <tr>
                        <td><strong>Critical Requests:</strong></td>
                        <td>' . $criticalCount . '</td>
                        <td><strong>Rejected:</strong></td>
                        <td>' . $rejectedCount . '</td>
                    </tr>
                </table>
            </div>
            
            <h3>Blood Requests Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Patient Details</th>
                        <th>Hospital</th>
                        <th>Blood Type</th>
                        <th>Units</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Required Date</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($bloodRequests as $request) {
            $urgencyClass = 'urgency-' . $request['urgency_level'];
            $statusClass = 'status-' . $request['status'];
            
            $html .= '<tr>
                <td><strong>' . $request['request_number'] . '</strong></td>
                <td>
                    <strong>' . htmlspecialchars($request['patient_name']) . '</strong><br>
                    Age: ' . $request['patient_age'] . ' | Sex: ' . $request['patient_sex'] . '<br>
                    Ward: ' . $request['patient_ward'] . '
                </td>
                <td>
                    <strong>' . htmlspecialchars($request['hospital_name']) . '</strong><br>
                    ' . htmlspecialchars($request['hospital_location']) . '<br>
                    Reg: ' . htmlspecialchars($request['registration_number']) . '
                </td>
                <td><strong>' . $request['blood_type'] . '</strong></td>
                <td><strong>' . $request['units_required'] . '</strong> units</td>
                <td class="' . $urgencyClass . '">' . ucfirst($request['urgency_level']) . '</td>
                <td class="' . $statusClass . '">' . ucfirst($request['status']) . '</td>
                <td>' . date('M d, Y', strtotime($request['required_date'])) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="footer">
                <p>--- End of Report ---</p>
                <p>This report was generated by Blood Bank Management System</p>
                <p class="no-print">Note: To save as PDF, use your browser\'s Print function and select "Save as PDF"</p>
            </div>
            
            <script>
                window.onload = function() {
                    // Auto-print for PDF generation
                    setTimeout(function() {
                        window.print();
                    }, 1000);
                };
            </script>
        </body>
        </html>';
        
        echo $html;
        exit;
    }
}

// Original page content continues below
require_once '../includes/header.php';
require_once 'admin_nav.php';

// Handle search and filters (for display)
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$blood_type = $_GET['blood_type'] ?? '';
$urgency = $_GET['urgency'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT br.*, h.hospital_name, h.registration_number, h.location as hospital_location,
           u1.email as approved_by_email, u2.email as fulfilled_by_email
    FROM blood_requests br
    JOIN hospitals h ON br.hospital_id = h.id
    LEFT JOIN users u1 ON br.approved_by = u1.id
    LEFT JOIN users u2 ON br.fulfilled_by = u2.id";
    
$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(br.patient_name ILIKE :search OR 
                     br.request_number ILIKE :search OR 
                     h.hospital_name ILIKE :search)";
    $params['search'] = "%{$search}%";
}

if (!empty($status) && $status !== 'all') {
    $conditions[] = "br.status = :status";
    $params['status'] = $status;
}

if (!empty($blood_type) && $blood_type !== 'all') {
    $conditions[] = "br.blood_type = :blood_type";
    $params['blood_type'] = $blood_type;
}

if (!empty($urgency) && $urgency !== 'all') {
    $conditions[] = "br.urgency_level = :urgency";
    $params['urgency'] = $urgency;
}

if (!empty($date_from)) {
    $conditions[] = "br.required_date >= :date_from";
    $params['date_from'] = $date_from;
}

if (!empty($date_to)) {
    $conditions[] = "br.required_date <= :date_to";
    $params['date_to'] = $date_to;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY 
    CASE br.urgency_level 
        WHEN 'critical' THEN 1
        WHEN 'urgent' THEN 2
        ELSE 3
    END, 
    br.required_date ASC, 
    br.created_at DESC";

// Fetch blood requests
$bloodRequests = Database::fetchAll($query, $params);

// Get counts for stats
$totalRequests = count($bloodRequests);
$pendingCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'approved'));
$fulfilledCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'fulfilled'));
$rejectedCount = count(array_filter($bloodRequests, fn($r) => $r['status'] === 'rejected'));

// Get urgent requests
$criticalCount = count(array_filter($bloodRequests, fn($r) => $r['urgency_level'] === 'critical' && $r['status'] === 'pending'));
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Blood Requests Management</h1>
    <p class="text-gray-600 mb-6">Review and manage blood supply requests from hospitals.</p>
    
    <!-- Stats Cards -->
    <div class="grid md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-gray-500 text-sm">Total Requests</p>
            <h3 class="text-2xl font-bold mt-2"><?php echo $totalRequests; ?></h3>
        </div>
        <div class="bg-yellow-50 rounded-xl shadow p-6">
            <p class="text-yellow-600 text-sm">Pending</p>
            <h3 class="text-2xl font-bold mt-2 text-yellow-700"><?php echo $pendingCount; ?></h3>
        </div>
        <div class="bg-red-50 rounded-xl shadow p-6">
            <p class="text-red-600 text-sm">Critical</p>
            <h3 class="text-2xl font-bold mt-2 text-red-700"><?php echo $criticalCount; ?></h3>
        </div>
        <div class="bg-green-50 rounded-xl shadow p-6">
            <p class="text-green-600 text-sm">Approved</p>
            <h3 class="text-2xl font-bold mt-2 text-green-700"><?php echo $approvedCount; ?></h3>
        </div>
        <div class="bg-blue-50 rounded-xl shadow p-6">
            <p class="text-blue-600 text-sm">Fulfilled</p>
            <h3 class="text-2xl font-bold mt-2 text-blue-700"><?php echo $fulfilledCount; ?></h3>
        </div>
    </div>
    
    <!-- Urgent Alert -->
    <?php if ($criticalCount > 0): ?>
    <div class="bg-red-100 border border-red-300 rounded-xl p-4 mb-8">
        <div class="flex items-center">
            <i class="ri-alarm-warning-line text-red-600 text-2xl mr-3"></i>
            <div>
                <h3 class="font-bold text-red-800">Critical Blood Requests</h3>
                <p class="text-red-700 text-sm">
                    There are <?php echo $criticalCount; ?> critical blood request<?php echo $criticalCount !== 1 ? 's' : ''; ?> 
                    requiring immediate attention.
                    <a href="#critical-section" class="font-medium underline ml-2">View them now →</a>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Search and Filter -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm mb-2">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                       placeholder="Patient, Request #, or Hospital">
            </div>
            <div>
                <label class="block text-gray-700 text-sm mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="fulfilled" <?php echo $status === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm mb-2">Blood Type</label>
                <select name="blood_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="all" <?php echo $blood_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="A+" <?php echo $blood_type === 'A+' ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo $blood_type === 'A-' ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo $blood_type === 'B+' ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo $blood_type === 'B-' ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo $blood_type === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo $blood_type === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                    <option value="O+" <?php echo $blood_type === 'O+' ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo $blood_type === 'O-' ? 'selected' : ''; ?>>O-</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm mb-2">Urgency</label>
                <select name="urgency" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="all" <?php echo $urgency === 'all' ? 'selected' : ''; ?>>All Urgency Levels</option>
                    <option value="critical" <?php echo $urgency === 'critical' ? 'selected' : ''; ?>>Critical</option>
                    <option value="urgent" <?php echo $urgency === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    <option value="normal" <?php echo $urgency === 'normal' ? 'selected' : ''; ?>>Normal</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm mb-2">Date From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-gray-700 text-sm mb-2">Date To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="md:col-span-2 flex items-end space-x-2">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg">
                    Apply Filters
                </button>
                <a href="blood_requests.php" class="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-center">
                    Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Export Buttons -->
    <div class="flex justify-end mb-4 space-x-3">
        <a href="blood_requests.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
            <i class="ri-file-excel-line mr-2"></i> Export to Excel
        </a>
        <a href="blood_requests.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" 
           class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center">
            <i class="ri-file-pdf-line mr-2"></i> Export to PDF
        </a>
    </div>
    
    <!-- Critical Requests Section -->
    <?php if ($criticalCount > 0): ?>
    <div id="critical-section" class="mb-8">
        <h2 class="text-xl font-bold text-red-700 mb-4">Critical Blood Requests</h2>
        <div class="bg-red-50 border border-red-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-red-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Request #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Blood Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Units</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Hospital</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Required Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100">
                        <?php foreach (array_filter($bloodRequests, fn($r) => $r['urgency_level'] === 'critical' && $r['status'] === 'pending') as $request): ?>
                        <tr class="hover:bg-red-50">
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold"><?php echo $request['request_number']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $request['patient_age']; ?>y, <?php echo $request['patient_sex']; ?></p>
                                <p class="text-xs text-gray-400">Ward: <?php echo $request['patient_ward']; ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">
                                    <?php echo $request['blood_type']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-lg"><?php echo $request['units_required']; ?></span>
                                <span class="text-sm text-gray-500">units</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($request['hospital_location']); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium"><?php echo date('M d, Y', strtotime($request['required_date'])); ?></p>
                                <?php 
                                $daysLeft = floor((strtotime($request['required_date']) - time()) / (60 * 60 * 24));
                                if ($daysLeft < 0) {
                                    echo '<p class="text-xs text-red-500">Overdue!</p>';
                                } elseif ($daysLeft <= 1) {
                                    echo '<p class="text-xs text-red-500">Due tomorrow</p>';
                                } else {
                                    echo '<p class="text-xs text-gray-500">In ' . $daysLeft . ' days</p>';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <a href="view_blood_request.php?id=<?php echo $request['id']; ?>" 
                                       class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg">
                                        Review
                                    </a>
                                    <a href="approve_blood_request.php?id=<?php echo $request['id']; ?>" 
                                       class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg">
                                        Approve
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- All Blood Requests -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">All Blood Requests</h2>
        </div>
        
        <?php if (empty($bloodRequests)): ?>
            <div class="text-center py-12">
                <i class="ri-inbox-line text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No blood requests found.</p>
                <?php if (!empty($search) || !empty($status) || !empty($blood_type) || !empty($urgency)): ?>
                    <p class="text-gray-400 text-sm mt-2">Try changing your filter criteria.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hospital</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood & Units</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Urgency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($bloodRequests as $request): 
                            $urgencyColor = match($request['urgency_level']) {
                                'critical' => 'bg-red-100 text-red-800',
                                'urgent' => 'bg-orange-100 text-orange-800',
                                default => 'bg-blue-100 text-blue-800'
                            };
                            
                            $statusColor = match($request['status']) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'fulfilled' => 'bg-blue-100 text-blue-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="font-mono text-sm font-medium"><?php echo $request['request_number']; ?></p>
                                <p class="font-medium mt-1"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $request['patient_age']; ?>y, <?php echo $request['patient_sex']; ?></p>
                                <p class="text-xs text-gray-400">Ward: <?php echo $request['patient_ward']; ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Needed: <?php echo date('M d, Y', strtotime($request['required_date'])); ?>
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($request['hospital_location']); ?></p>
                                <p class="text-xs text-gray-400">Reg: <?php echo htmlspecialchars($request['registration_number']); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm font-medium">
                                        <?php echo $request['blood_type']; ?>
                                    </span>
                                    <span class="font-bold text-lg"><?php echo $request['units_required']; ?></span>
                                    <span class="text-sm text-gray-500">units</span>
                                </div>
                                <?php 
                                // Check stock availability
                                $stock = Database::fetch(
                                    "SELECT units_available FROM blood_stocks WHERE blood_type = :blood_type",
                                    ['blood_type' => $request['blood_type']]
                                );
                                $stockAvailable = $stock ? $stock['units_available'] : 0;
                                ?>
                                <p class="text-xs mt-1 <?php echo $stockAvailable >= $request['units_required'] ? 'text-green-600' : 'text-red-600'; ?>">
                                    Stock: <?php echo $stockAvailable; ?> units available
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $urgencyColor; ?>">
                                    <?php echo ucfirst($request['urgency_level']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                                <?php if ($request['approved_at']): ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    Approved: <?php echo date('M d', strtotime($request['approved_at'])); ?>
                                </p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col space-y-2">
                                    <a href="view_blood_request.php?id=<?php echo $request['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        <i class="ri-eye-line mr-1"></i> View
                                    </a>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <a href="approve_blood_request.php?id=<?php echo $request['id']; ?>" 
                                       class="text-green-600 hover:text-green-800 text-sm font-medium">
                                        <i class="ri-check-line mr-1"></i> Approve
                                    </a>
                                    <a href="reject_blood_request.php?id=<?php echo $request['id']; ?>" 
                                       class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        <i class="ri-close-line mr-1"></i> Reject
                                    </a>
                                    <?php elseif ($request['status'] === 'approved'): ?>
                                    <a href="fulfill_blood_request.php?id=<?php echo $request['id']; ?>" 
                                       class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                        <i class="ri-check-double-line mr-1"></i> Mark Fulfilled
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-500">
                        Showing <?php echo count($bloodRequests); ?> request<?php echo count($bloodRequests) !== 1 ? 's' : ''; ?>
                    </p>
                    <div class="flex space-x-2">
                        <a href="blood_stocks.php" 
                           class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="ri-drop-line mr-1"></i> Check Stock
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Stats -->
    <div class="mt-8 grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Request Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Units Requested</span>
                    <span class="font-medium">
                        <?php echo array_sum(array_column($bloodRequests, 'units_required')); ?> units
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Average Units/Request</span>
                    <span class="font-medium">
                        <?php echo $totalRequests > 0 ? round(array_sum(array_column($bloodRequests, 'units_required')) / $totalRequests, 1) : 0; ?> units
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Fulfillment Rate</span>
                    <span class="font-medium">
                        <?php echo $totalRequests > 0 ? round(($fulfilledCount / $totalRequests) * 100) : 0; ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="bg-red-50 rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Urgent Actions Needed</h3>
            <?php if ($criticalCount > 0): ?>
            <div class="space-y-3">
                <p class="text-red-700">
                    <i class="ri-alarm-warning-line mr-1"></i>
                    <?php echo $criticalCount; ?> critical request<?php echo $criticalCount !== 1 ? 's' : ''; ?> pending
                </p>
                <?php 
                $todayRequests = array_filter($bloodRequests, function($r) {
                    return $r['required_date'] === date('Y-m-d') && $r['status'] === 'pending';
                });
                ?>
                <?php if (count($todayRequests) > 0): ?>
                <p class="text-orange-700">
                    <i class="ri-calendar-event-line mr-1"></i>
                    <?php echo count($todayRequests); ?> request<?php echo count($todayRequests) !== 1 ? 's' : ''; ?> due today
                </p>
                <?php endif; ?>
                <a href="#critical-section" class="inline-block mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                    Review Critical Requests
                </a>
            </div>
            <?php else: ?>
            <p class="text-green-700">
                <i class="ri-checkbox-circle-line mr-1"></i>
                No urgent actions required at this time.
            </p>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-bold text-gray-900 mb-4">Quick Links</h3>
            <div class="space-y-3">
                <a href="blood_stocks.php" class="flex items-center text-red-600 hover:text-red-800">
                    <i class="ri-drop-line mr-2"></i>
                    <span>Blood Stock Management</span>
                </a>
                <a href="manage_hospitals.php" class="flex items-center text-blue-600 hover:text-blue-800">
                    <i class="ri-hospital-line mr-2"></i>
                    <span>Manage Hospitals</span>
                </a>
                <a href="dashboard.php" class="flex items-center text-gray-600 hover:text-gray-800">
                    <i class="ri-dashboard-line mr-2"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>