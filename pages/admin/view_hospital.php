<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');

$hospitalId = $_GET['id'] ?? null;

if (!$hospitalId) {
    header('Location: manage_hospitals.php');
    exit();
}

// Get hospital details
$hospital = Database::fetch(
    "SELECT h.*, u.email, u.verification_status, u.verified_at, 
            u2.email as verified_by_email
     FROM hospitals h
     JOIN users u ON h.user_id = u.id
     LEFT JOIN users u2 ON h.verified_by = u2.id
     WHERE h.id = :id",
    ['id' => $hospitalId]
);

if (!$hospital) {
    header('Location: manage_hospitals.php');
    exit();
}

// Get hospital statistics
$stats = Database::fetchAll(
    "SELECT 
        (SELECT COUNT(*) FROM blood_requests WHERE hospital_id = :id) as total_requests,
        (SELECT COUNT(*) FROM blood_requests WHERE hospital_id = :id AND status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM blood_requests WHERE hospital_id = :id AND status = 'approved') as approved_requests,
        (SELECT COUNT(*) FROM blood_requests WHERE hospital_id = :id AND status = 'fulfilled') as fulfilled_requests,
        (SELECT COUNT(*) FROM blood_requests WHERE hospital_id = :id AND status = 'rejected') as rejected_requests,
        (SELECT SUM(units_required) FROM blood_requests WHERE hospital_id = :id AND status = 'fulfilled') as total_units_fulfilled",
    ['id' => $hospitalId]
)[0];

// Get recent requests
$recentRequests = Database::fetchAll(
    "SELECT br.* 
     FROM blood_requests br
     WHERE br.hospital_id = :id
     ORDER BY br.created_at DESC
     LIMIT 5",
    ['id' => $hospitalId]
);

// Handle PDF generation
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Generate PDF report
    $filename = 'hospital_report_' . $hospital['registration_number'] . '_' . date('Ymd_His') . '.html';
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Calculate summary stats
    $totalRequests = $stats['total_requests'];
    $totalUnits = $stats['total_units_fulfilled'] ?: 0;
    $pendingCount = $stats['pending_requests'];
    $approvedCount = $stats['approved_requests'];
    $fulfilledCount = $stats['fulfilled_requests'];
    $rejectedCount = $stats['rejected_requests'];
    
    // Calculate percentages for chart
    $total = $totalRequests;
    $pendingWidth = $total > 0 ? ($pendingCount / $total) * 100 : 0;
    $approvedWidth = $total > 0 ? ($approvedCount / $total) * 100 : 0;
    $fulfilledWidth = $total > 0 ? ($fulfilledCount / $total) * 100 : 0;
    $rejectedWidth = $total > 0 ? ($rejectedCount / $total) * 100 : 0;
    
    // Generate HTML for PDF
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Hospital Report - ' . htmlspecialchars($hospital['hospital_name']) . '</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                margin: 25px;
                color: #2d3748;
                background: #f7fafc;
                line-height: 1.6;
            }
            
            .container {
                max-width: 1000px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }
            
            /* Header Section */
            .header {
                background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
                color: white;
                padding: 40px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            
            .header::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="white" opacity="0.05"/></svg>\');
                background-size: cover;
            }
            
            .header-content {
                position: relative;
                z-index: 1;
            }
            
            .hospital-name {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 8px;
                letter-spacing: -0.5px;
            }
            
            .hospital-tagline {
                font-size: 18px;
                opacity: 0.9;
                font-weight: 300;
                margin-bottom: 20px;
            }
            
            .hospital-id {
                display: inline-block;
                background: rgba(255, 255, 255, 0.15);
                padding: 8px 20px;
                border-radius: 50px;
                font-size: 14px;
                backdrop-filter: blur(10px);
                margin-top: 10px;
            }
            
            /* Report Info */
            .report-info {
                display: flex;
                justify-content: space-between;
                background: #f8fafc;
                padding: 20px 40px;
                border-bottom: 1px solid #e2e8f0;
                font-size: 14px;
                color: #4a5568;
            }
            
            .report-info span {
                font-weight: 600;
                color: #2d3748;
            }
            
            /* Main Content */
            .content {
                padding: 40px;
            }
            
            .section {
                margin-bottom: 40px;
            }
            
            .section-title {
                font-size: 20px;
                font-weight: 600;
                color: #2d3748;
                margin-bottom: 20px;
                padding-bottom: 12px;
                border-bottom: 2px solid #edf2f7;
                display: flex;
                align-items: center;
            }
            
            .section-title i {
                margin-right: 10px;
                color: #dc2626;
            }
            
            /* Info Grid */
            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .info-item {
                background: #f8fafc;
                border-radius: 8px;
                padding: 20px;
                border-left: 4px solid #dc2626;
            }
            
            .info-label {
                font-size: 12px;
                text-transform: uppercase;
                color: #718096;
                font-weight: 600;
                letter-spacing: 0.5px;
                margin-bottom: 5px;
            }
            
            .info-value {
                font-size: 16px;
                font-weight: 600;
                color: #2d3748;
            }
            
            .info-sub {
                font-size: 13px;
                color: #718096;
                margin-top: 3px;
            }
            
            /* Status Badge */
            .status-badge {
                display: inline-block;
                padding: 6px 16px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .status-pending { background: #fef3c7; color: #92400e; }
            .status-approved { background: #d1fae5; color: #065f46; }
            .status-rejected { background: #fee2e2; color: #991b1b; }
            
            /* Stats Grid */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin-bottom: 30px;
            }
            
            .stat-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid #e2e8f0;
                transition: transform 0.2s;
            }
            
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            }
            
            .stat-value {
                font-size: 32px;
                font-weight: 700;
                margin: 10px 0;
                color: #2d3748;
            }
            
            .stat-label {
                font-size: 13px;
                color: #718096;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            /* Progress Chart */
            .progress-chart {
                background: white;
                border-radius: 10px;
                padding: 25px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border: 1px solid #e2e8f0;
                margin-bottom: 30px;
            }
            
            .chart-title {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 20px;
                color: #2d3748;
            }
            
            .chart-bars {
                display: flex;
                height: 40px;
                border-radius: 8px;
                overflow: hidden;
                margin-bottom: 15px;
                background: #f1f5f9;
            }
            
            .chart-bar {
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: 600;
                transition: width 0.5s ease;
            }
            
            .bar-pending { background: #f59e0b; }
            .bar-approved { background: #10b981; }
            .bar-fulfilled { background: #3b82f6; }
            .bar-rejected { background: #ef4444; }
            
            .chart-labels {
                display: flex;
                justify-content: space-between;
                font-size: 12px;
                color: #64748b;
            }
            
            /* Verification Section */
            .verification-box {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                border-radius: 10px;
                padding: 25px;
                margin-bottom: 30px;
                border-left: 4px solid #dc2626;
            }
            
            .verification-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            
            .verification-title {
                font-size: 18px;
                font-weight: 600;
                color: #2d3748;
            }
            
            /* Timeline */
            .timeline {
                position: relative;
                padding-left: 30px;
                margin-top: 20px;
            }
            
            .timeline::before {
                content: "";
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #cbd5e0;
            }
            
            .timeline-item {
                position: relative;
                margin-bottom: 25px;
            }
            
            .timeline-dot {
                position: absolute;
                left: -34px;
                top: 0;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #dc2626;
                border: 3px solid white;
                box-shadow: 0 0 0 2px #dc2626;
            }
            
            .timeline-dot.pending { background: #f59e0b; box-shadow: 0 0 0 2px #f59e0b; }
            .timeline-dot.approved { background: #10b981; box-shadow: 0 0 0 2px #10b981; }
            .timeline-dot.rejected { background: #ef4444; box-shadow: 0 0 0 2px #ef4444; }
            
            .timeline-content {
                background: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }
            
            .timeline-title {
                font-weight: 600;
                margin-bottom: 5px;
                color: #2d3748;
            }
            
            .timeline-date {
                font-size: 13px;
                color: #718096;
            }
            
            .timeline-note {
                font-size: 14px;
                color: #4a5568;
                margin-top: 8px;
                font-style: italic;
            }
            
            /* Table */
            .requests-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 14px;
            }
            
            .requests-table th {
                background: #f8fafc;
                padding: 12px 15px;
                text-align: left;
                font-weight: 600;
                color: #4a5568;
                border-bottom: 2px solid #e2e8f0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 12px;
            }
            
            .requests-table td {
                padding: 12px 15px;
                border-bottom: 1px solid #edf2f7;
            }
            
            .requests-table tr:hover {
                background: #f8fafc;
            }
            
            .table-status {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
            }
            
            /* Footer */
            .footer {
                background: #1a202c;
                color: #a0aec0;
                padding: 30px 40px;
                text-align: center;
                font-size: 13px;
                line-height: 1.8;
            }
            
            .footer p {
                margin-bottom: 5px;
            }
            
            .footer a {
                color: #63b3ed;
                text-decoration: none;
            }
            
            .print-instructions {
                background: #fef3c7;
                border: 1px solid #fbbf24;
                border-radius: 6px;
                padding: 15px;
                margin-top: 30px;
                font-size: 13px;
                color: #92400e;
            }
            
            @media print {
                body {
                    margin: 0;
                    background: white;
                }
                
                .container {
                    box-shadow: none;
                    border-radius: 0;
                }
                
                .print-instructions {
                    display: none;
                }
                
                .stat-card:hover {
                    transform: none;
                    box-shadow: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <h1 class="hospital-name">' . htmlspecialchars($hospital['hospital_name']) . '</h1>
                    <p class="hospital-tagline">Hospital Detailed Report</p>
                    <div class="hospital-id">Registration: ' . htmlspecialchars($hospital['registration_number']) . '</div>
                </div>
            </div>
            
            <!-- Report Info -->
            <div class="report-info">
                <div>Generated on: <span>' . date('F j, Y, g:i A') . '</span></div>
                <div>Report ID: <span>HOSP-' . strtoupper(dechex($hospital['id'])) . '-' . date('Ymd') . '</span></div>
            </div>
            
            <!-- Main Content -->
            <div class="content">
                <!-- Hospital Information -->
                <div class="section">
                    <h2 class="section-title">Hospital Information</h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Hospital Name</div>
                            <div class="info-value">' . htmlspecialchars($hospital['hospital_name']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Registration Number</div>
                            <div class="info-value">' . htmlspecialchars($hospital['registration_number']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Location</div>
                            <div class="info-value">' . htmlspecialchars($hospital['location']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Contact Person</div>
                            <div class="info-value">' . htmlspecialchars($hospital['contact_person']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Contact Email</div>
                            <div class="info-value">' . htmlspecialchars($hospital['contact_email']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Contact Phone</div>
                            <div class="info-value">' . htmlspecialchars($hospital['contact_phone']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Login Email</div>
                            <div class="info-value">' . htmlspecialchars($hospital['email']) . '</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Registration Date</div>
                            <div class="info-value">' . date('F j, Y', strtotime($hospital['created_at'])) . '</div>
                            <div class="info-sub">' . date('g:i A', strtotime($hospital['created_at'])) . '</div>
                        </div>
                    </div>
                </div>
                
                <!-- Verification Status -->
                <div class="section">
                    <h2 class="section-title">Verification Status</h2>
                    
                    <div class="verification-box">
                        <div class="verification-header">
                            <div>
                                <div class="verification-title">Verification Details</div>';
    
    // Determine status class
    $statusClass = '';
    if ($hospital['verification_status'] === 'pending') {
        $statusClass = 'status-pending';
    } elseif ($hospital['verification_status'] === 'approved') {
        $statusClass = 'status-approved';
    } elseif ($hospital['verification_status'] === 'rejected') {
        $statusClass = 'status-rejected';
    }
    
    $html .= '                <span class="status-badge ' . $statusClass . '">
                                    ' . strtoupper($hospital['verification_status']) . '
                                </span>
                            </div>';
    
    if ($hospital['verified_at']) {
        $html .= '            <div>
                                <div class="info-label">Verified On</div>
                                <div class="info-value">' . date('F j, Y', strtotime($hospital['verified_at'])) . '</div>
                            </div>';
    }
    
    $html .= '            </div>';
    
    if ($hospital['verification_note']) {
        $html .= '        <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 6px;">
                            <div class="info-label" style="margin-bottom: 8px;">Verification Notes</div>
                            <div style="color: #4a5568;">' . nl2br(htmlspecialchars($hospital['verification_note'])) . '</div>
                        </div>';
    }
    
    $html .= '        </div>
                    
                    <!-- Timeline -->
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Registration Submitted</div>
                                <div class="timeline-date">' . date('F j, Y g:i A', strtotime($hospital['created_at'])) . '</div>
                            </div>
                        </div>';
    
    if ($hospital['verified_at']) {
        $dotClass = $hospital['verification_status'];
        $html .= '        <div class="timeline-item">
                            <div class="timeline-dot ' . $dotClass . '"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Verification ' . ucfirst($hospital['verification_status']) . '</div>
                                <div class="timeline-date">' . date('F j, Y g:i A', strtotime($hospital['verified_at'])) . '</div>';
        
        if ($hospital['verified_by_email']) {
            $html .= '        <div class="timeline-note">By: ' . htmlspecialchars($hospital['verified_by_email']) . '</div>';
        }
        
        $html .= '        </div>
                        </div>';
    } else {
        $html .= '        <div class="timeline-item">
                            <div class="timeline-dot pending"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Awaiting Verification</div>
                                <div class="timeline-date">Pending administrative review</div>
                            </div>
                        </div>';
    }
    
    $html .= '        </div>
                </div>
                
                <!-- Hospital Statistics -->
                <div class="section">
                    <h2 class="section-title">Hospital Statistics</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Total Requests</div>
                            <div class="stat-value">' . $totalRequests . '</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value">' . $pendingCount . '</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Fulfilled</div>
                            <div class="stat-value">' . $fulfilledCount . '</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Total Units</div>
                            <div class="stat-value">' . $totalUnits . '</div>
                            <div class="stat-label">units fulfilled</div>
                        </div>
                    </div>';
    
    if ($totalRequests > 0) {
        $html .= '    <div class="progress-chart">
                        <div class="chart-title">Request Distribution</div>
                        <div class="chart-bars">
                            <div class="chart-bar bar-pending" style="width: ' . $pendingWidth . '%;">' . round($pendingWidth) . '%</div>
                            <div class="chart-bar bar-approved" style="width: ' . $approvedWidth . '%;">' . round($approvedWidth) . '%</div>
                            <div class="chart-bar bar-fulfilled" style="width: ' . $fulfilledWidth . '%;">' . round($fulfilledWidth) . '%</div>
                            <div class="chart-bar bar-rejected" style="width: ' . $rejectedWidth . '%;">' . round($rejectedWidth) . '%</div>
                        </div>
                        <div class="chart-labels">
                            <span>Pending (' . round($pendingWidth) . '%)</span>
                            <span>Approved (' . round($approvedWidth) . '%)</span>
                            <span>Fulfilled (' . round($fulfilledWidth) . '%)</span>
                            <span>Rejected (' . round($rejectedWidth) . '%)</span>
                        </div>
                    </div>';
    } else {
        $html .= '    <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 10px; color: #718096;">
                        <div style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.5;">📭</div>
                        <div style="font-size: 16px;">No blood requests recorded yet</div>
                    </div>';
    }
    
    $html .= '    </div>';
    
    // Recent Requests
    if (!empty($recentRequests)) {
        $html .= '<!-- Recent Requests -->
                <div class="section">
                    <h2 class="section-title">Recent Blood Requests</h2>
                    
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Patient</th>
                                <th>Blood Type</th>
                                <th>Units</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($recentRequests as $request) {
            // Determine status color for each request
            $requestStatusColor = '';
            if ($request['status'] === 'pending') {
                $requestStatusColor = 'background: #fef3c7; color: #92400e;';
            } elseif ($request['status'] === 'approved') {
                $requestStatusColor = 'background: #d1fae5; color: #065f46;';
            } elseif ($request['status'] === 'fulfilled') {
                $requestStatusColor = 'background: #dbeafe; color: #1e40af;';
            } elseif ($request['status'] === 'rejected') {
                $requestStatusColor = 'background: #fee2e2; color: #991b1b;';
            } else {
                $requestStatusColor = 'background: #f1f5f9; color: #475569;';
            }
            
            $html .= '<tr>
                        <td><strong>' . $request['request_number'] . '</strong></td>
                        <td>
                            <div>' . htmlspecialchars($request['patient_name']) . '</div>
                            <div style="font-size: 12px; color: #718096;">' . $request['patient_ward'] . '</div>
                        </td>
                        <td><strong>' . $request['blood_type'] . '</strong></td>
                        <td><strong>' . $request['units_required'] . '</strong> units</td>
                        <td>
                            <span class="table-status" style="' . $requestStatusColor . '">
                                ' . ucfirst($request['status']) . '
                            </span>
                        </td>
                        <td>' . date('M d, Y', strtotime($request['created_at'])) . '</td>
                    </tr>';
        }
        
        $html .= '</tbody>
                    </table>
                </div>';
    }
    
    $html .= '    <!-- Summary -->
                <div class="section">
                    <h2 class="section-title">Report Summary</h2>
                    
                    <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); padding: 25px; border-radius: 10px; border-left: 4px solid #0ea5e9;">
                        <div style="font-size: 16px; font-weight: 600; color: #0369a1; margin-bottom: 15px;">Key Information</div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                            <div>
                                <div style="font-size: 13px; color: #475569; margin-bottom: 5px;">Registration Period</div>
                                <div style="font-weight: 600; color: #1e293b;">
                                    ' . ceil((time() - strtotime($hospital['created_at'])) / (60 * 60 * 24)) . ' days
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 13px; color: #475569; margin-bottom: 5px;">Fulfillment Rate</div>
                                <div style="font-weight: 600; color: #1e293b;">
                                    ' . ($totalRequests > 0 ? round(($fulfilledCount / $totalRequests) * 100) : 0) . '%
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 13px; color: #475569; margin-bottom: 5px;">Average Units per Request</div>
                                <div style="font-weight: 600; color: #1e293b;">
                                    ' . ($totalRequests > 0 ? round($totalUnits / $totalRequests, 1) : 0) . ' units
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 13px; color: #475569; margin-bottom: 5px;">Current Status</div>
                                <div style="font-weight: 600; color: #1e293b; text-transform: capitalize;">
                                    ' . $hospital['verification_status'] . '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Print Instructions -->
                <div class="print-instructions">
                    <strong>Instructions:</strong> To save as PDF, use your browser\'s Print function (Ctrl+P) and select "Save as PDF" as the destination.
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p><strong>Blood Bank Management System</strong> - Hospital Detailed Report</p>
                <p>This report is confidential and intended only for authorized personnel.</p>
                <p>Generated on: ' . date('F j, Y, g:i A') . ' | Report ID: HOSP-' . strtoupper(dechex($hospital['id'])) . '-' . date('Ymd') . '</p>
                <p style="margin-top: 15px; font-size: 12px; opacity: 0.7;">
                    &copy; ' . date('Y') . ' Blood Bank Management System. All rights reserved.
                </p>
            </div>
        </div>
        
        <script>
            // Auto-trigger print dialog for PDF generation
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 1000);
            };
        </script>
    </body>
    </html>';
    
    echo $html;
    exit();
}

require_once '../includes/header.php';
require_once 'admin_nav.php';

// Determine status color for display
$statusColor = '';
if ($hospital['verification_status'] === 'pending') {
    $statusColor = 'bg-yellow-100 text-yellow-800';
} elseif ($hospital['verification_status'] === 'approved') {
    $statusColor = 'bg-green-100 text-green-800';
} elseif ($hospital['verification_status'] === 'rejected') {
    $statusColor = 'bg-red-100 text-red-800';
} else {
    $statusColor = 'bg-gray-100 text-gray-800';
}
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Header with Actions -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Hospital Details</h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($hospital['hospital_name']); ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="manage_hospitals.php" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-arrow-left-line mr-1"></i> Back
            </a>
            <!-- PDF Export Button -->
            <a href="view_hospital.php?id=<?php echo $hospital['id']; ?>&export=pdf" 
               class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center">
                <i class="ri-file-pdf-line mr-2"></i> Export PDF
            </a>
            <?php if ($hospital['verification_status'] === 'pending'): ?>
            <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=approve" 
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                <i class="ri-check-line mr-1"></i> Approve
            </a>
            <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=reject" 
               class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                <i class="ri-close-line mr-1"></i> Reject
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Verification Status Alert -->
    <?php if ($hospital['verification_status'] === 'pending'): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
        <div class="flex items-center">
            <i class="ri-time-line text-yellow-600 text-2xl mr-4"></i>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-800 text-lg">Pending Verification</h3>
                <p class="text-yellow-700">
                    This hospital is awaiting verification. They cannot request blood supplies until approved.
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=approve" 
                   class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    Approve
                </a>
                <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=reject" 
                   class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    Reject
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Hospital Details -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Hospital Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Hospital Information</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Hospital Name</p>
                            <p class="font-medium text-lg"><?php echo htmlspecialchars($hospital['hospital_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Registration Number</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['registration_number']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Location</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['location']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">License Number</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['license_number'] ?: 'Not provided'); ?></p>
                            <?php if ($hospital['license_expiry']): ?>
                            <p class="text-sm <?php echo strtotime($hospital['license_expiry']) < time() ? 'text-red-500' : 'text-gray-500'; ?>">
                                Expires: <?php echo date('M d, Y', strtotime($hospital['license_expiry'])); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Contact Person</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_person']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Contact Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_email']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Contact Phone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_phone']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Login Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($hospital['email']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Registration Date</p>
                            <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($hospital['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Verification Status -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Verification Status</p>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                                <?php echo ucfirst($hospital['verification_status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($hospital['verified_at']): ?>
                        <div class="text-right">
                            <p class="text-gray-500 text-sm">Verified On</p>
                            <p class="font-medium"><?php echo date('M d, Y', strtotime($hospital['verified_at'])); ?></p>
                            <?php if ($hospital['verified_by_email']): ?>
                            <p class="text-sm text-gray-500">
                                By: <?php echo htmlspecialchars($hospital['verified_by_email']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($hospital['verification_note']): ?>
                    <div class="mt-4">
                        <p class="text-gray-500 text-sm mb-2">Verification Notes</p>
                        <p class="text-gray-700 bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($hospital['verification_note'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Hospital Statistics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Hospital Statistics</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-500 text-sm">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_requests']; ?></p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-yellow-600 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-yellow-700"><?php echo $stats['pending_requests']; ?></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-green-600 text-sm">Fulfilled</p>
                        <p class="text-2xl font-bold text-green-700"><?php echo $stats['fulfilled_requests']; ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-blue-600 text-sm">Total Units</p>
                        <p class="text-2xl font-bold text-blue-700"><?php echo $stats['total_units_fulfilled'] ?: 0; ?></p>
                        <p class="text-blue-500 text-xs">units fulfilled</p>
                    </div>
                </div>
                
                <?php if ($stats['total_requests'] > 0): ?>
                <div class="mt-4">
                    <p class="text-gray-500 text-sm mb-2">Request Distribution</p>
                    <div class="flex items-center space-x-2">
                        <?php 
                        $total = $stats['total_requests'];
                        $pendingWidth = $total > 0 ? ($stats['pending_requests'] / $total) * 100 : 0;
                        $approvedWidth = $total > 0 ? ($stats['approved_requests'] / $total) * 100 : 0;
                        $fulfilledWidth = $total > 0 ? ($stats['fulfilled_requests'] / $total) * 100 : 0;
                        $rejectedWidth = $total > 0 ? ($stats['rejected_requests'] / $total) * 100 : 0;
                        ?>
                        <div class="h-4 rounded-l-full bg-yellow-500" style="width: <?php echo $pendingWidth; ?>%"></div>
                        <div class="h-4 bg-green-500" style="width: <?php echo $approvedWidth; ?>%"></div>
                        <div class="h-4 bg-blue-500" style="width: <?php echo $fulfilledWidth; ?>%"></div>
                        <div class="h-4 rounded-r-full bg-red-500" style="width: <?php echo $rejectedWidth; ?>%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>Pending: <?php echo round($pendingWidth); ?>%</span>
                        <span>Approved: <?php echo round($approvedWidth); ?>%</span>
                        <span>Fulfilled: <?php echo round($fulfilledWidth); ?>%</span>
                        <span>Rejected: <?php echo round($rejectedWidth); ?>%</span>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-gray-500 text-center py-4">No blood requests yet</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Blood Requests -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Recent Blood Requests</h2>
                    <a href="hospital_blood_requests.php?hospital_id=<?php echo $hospital['id']; ?>" 
                       class="text-red-600 hover:text-red-800 font-medium">
                        View All →
                    </a>
                </div>
                
                <?php if (empty($recentRequests)): ?>
                    <p class="text-gray-500 text-center py-8">No recent blood requests</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recentRequests as $request): 
                                    // Determine status color for each request
                                    $requestStatusColor = '';
                                    if ($request['status'] === 'pending') {
                                        $requestStatusColor = 'bg-yellow-100 text-yellow-800';
                                    } elseif ($request['status'] === 'approved') {
                                        $requestStatusColor = 'bg-green-100 text-green-800';
                                    } elseif ($request['status'] === 'fulfilled') {
                                        $requestStatusColor = 'bg-blue-100 text-blue-800';
                                    } elseif ($request['status'] === 'rejected') {
                                        $requestStatusColor = 'bg-red-100 text-red-800';
                                    } else {
                                        $requestStatusColor = 'bg-gray-100 text-gray-800';
                                    }
                                ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-sm"><?php echo $request['request_number']; ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $request['patient_ward']; ?></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold"><?php echo $request['blood_type']; ?></span>
                                        <span class="text-sm text-gray-500 ml-1">(<?php echo $request['units_required']; ?>u)</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $requestStatusColor; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo date('M d', strtotime($request['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Actions & Info -->
        <div class="space-y-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <?php if ($hospital['verification_status'] === 'pending'): ?>
                    <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=approve" 
                       class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-600 mr-3"></i>
                            <span>Approve Hospital</span>
                        </div>
                        <i class="ri-arrow-right-line text-green-600"></i>
                    </a>
                    <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=reject" 
                       class="flex items-center justify-between p-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-close-line text-red-600 mr-3"></i>
                            <span>Reject Hospital</span>
                        </div>
                        <i class="ri-arrow-right-line text-red-600"></i>
                    </a>
                    <?php elseif ($hospital['verification_status'] === 'approved'): ?>
                    <a href="hospital_blood_requests.php?hospital_id=<?php echo $hospital['id']; ?>" 
                       class="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-heart-pulse-line text-blue-600 mr-3"></i>
                            <span>View Blood Requests</span>
                        </div>
                        <i class="ri-arrow-right-line text-blue-600"></i>
                    </a>
                    <?php endif; ?>
                    
                    <a href="view_hospital.php?id=<?php echo $hospital['id']; ?>&export=pdf" 
                       class="flex items-center justify-between p-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-file-pdf-line text-red-600 mr-3"></i>
                            <span>Export as PDF</span>
                        </div>
                        <i class="ri-arrow-right-line text-red-600"></i>
                    </a>
                    
                    <a href="mailto:<?php echo htmlspecialchars($hospital['contact_email']); ?>" 
                       class="flex items-center justify-between p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-mail-line text-purple-600 mr-3"></i>
                            <span>Email Hospital</span>
                        </div>
                        <i class="ri-arrow-right-line text-purple-600"></i>
                    </a>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Contact Information</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-gray-500 text-sm">Primary Contact</p>
                        <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_person']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Email Address</p>
                        <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_email']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Phone Number</p>
                        <p class="font-medium"><?php echo htmlspecialchars($hospital['contact_phone']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Login Email</p>
                        <p class="font-medium"><?php echo htmlspecialchars($hospital['email']); ?></p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="font-medium text-gray-700 mb-3">Quick Contact</h3>
                    <div class="flex space-x-2">
                        <a href="mailto:<?php echo htmlspecialchars($hospital['contact_email']); ?>" 
                           class="flex-1 text-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
                            <i class="ri-mail-line mr-1"></i> Email
                        </a>
                        <a href="tel:<?php echo htmlspecialchars($hospital['contact_phone']); ?>" 
                           class="flex-1 text-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm">
                            <i class="ri-phone-line mr-1"></i> Call
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Verification Timeline -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Verification Timeline</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-green-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Registration Submitted</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($hospital['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($hospital['verified_at']): ?>
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-<?php echo $hospital['verification_status'] === 'approved' ? 'green' : 'red'; ?>-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Verification <?php echo ucfirst($hospital['verification_status']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($hospital['verified_at'])); ?></p>
                            <?php if ($hospital['verified_by_email']): ?>
                            <p class="text-xs text-gray-500">By: <?php echo htmlspecialchars($hospital['verified_by_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Awaiting Verification</p>
                            <p class="text-sm text-gray-500">Pending admin review</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    $daysRegistered = floor((time() - strtotime($hospital['created_at'])) / (60 * 60 * 24));
                    ?>
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            <i class="ri-time-line mr-1"></i>
                            Registered <?php echo $daysRegistered; ?> day<?php echo $daysRegistered !== 1 ? 's' : ''; ?> ago
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>