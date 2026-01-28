<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');

$requestId = $_GET['id'] ?? null;

if (!$requestId) {
    header('Location: blood_requests.php');
    exit();
}

// Get blood request details
$request = Database::fetch(
    "SELECT br.*, h.hospital_name, h.registration_number, h.location as hospital_location,
            h.contact_person, h.contact_email, h.contact_phone,
            u1.email as approved_by_email, u2.email as fulfilled_by_email,
            bs.units_available as stock_available
     FROM blood_requests br
     JOIN hospitals h ON br.hospital_id = h.id
     LEFT JOIN users u1 ON br.approved_by = u1.id
     LEFT JOIN users u2 ON br.fulfilled_by = u2.id
     LEFT JOIN blood_stocks bs ON br.blood_type = bs.blood_type
     WHERE br.id = :id",
    ['id' => $requestId]
);

if (!$request) {
    header('Location: blood_requests.php');
    exit();
}

// Get similar pending requests
$similarRequests = Database::fetchAll(
    "SELECT br.*, h.hospital_name 
     FROM blood_requests br
     JOIN hospitals h ON br.hospital_id = h.id
     WHERE br.blood_type = :blood_type 
       AND br.status = 'pending'
       AND br.id != :id
     ORDER BY br.urgency_level, br.required_date
     LIMIT 5",
    ['blood_type' => $request['blood_type'], 'id' => $requestId]
);

require_once '../includes/header.php';
require_once 'admin_nav.php';

$urgencyColor = match($request['urgency_level']) {
    'critical' => 'bg-red-100 text-red-800 border-red-200',
    'urgent' => 'bg-orange-100 text-orange-800 border-orange-200',
    default => 'bg-blue-100 text-blue-800 border-blue-200'
};

$statusColor = match($request['status']) {
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'fulfilled' => 'bg-blue-100 text-blue-800',
    'rejected' => 'bg-red-100 text-red-800',
    default => 'bg-gray-100 text-gray-800'
};

$daysLeft = floor((strtotime($request['required_date']) - time()) / (60 * 60 * 24));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Request #<?php echo $request['request_number']; ?> - BloodSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
    
    <!-- Print Styles -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-request, .print-request * {
                visibility: visible;
            }
            .print-request {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
            .print-request .border {
                border-color: #000 !important;
            }
            .print-request .bg-gray-50 {
                background-color: #f9fafb !important;
            }
            .print-request h1, .print-request h2, .print-request h3 {
                color: #000 !important;
                margin-bottom: 10px;
            }
            .print-request .urgent-badge {
                background-color: #fef3c7 !important;
                color: #92400e !important;
                border: 1px solid #d97706;
            }
            .print-request .critical-badge {
                background-color: #fee2e2 !important;
                color: #991b1b !important;
                border: 1px solid #dc2626;
            }
            .print-request .normal-badge {
                background-color: #dbeafe !important;
                color: #1e40af !important;
                border: 1px solid #3b82f6;
            }
            .print-request .blood-type {
                font-size: 24px !important;
                font-weight: bold !important;
                color: #dc2626 !important;
            }
            .print-request .logo {
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 2px solid #000;
            }
            .print-request .logo h1 {
                font-size: 24px;
                color: #dc2626;
            }
            .print-request .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #000;
                font-size: 12px;
                color: #666;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body class="bg-gray-100">

<!-- Print-friendly version (hidden by default, visible only when printing) -->
<div class="print-request" style="display: none;">
    <!-- Header -->
    <div class="logo">
        <h1>BloodSync - Blood Bank Management System</h1>
        <p>Blood Request Details - <?php echo date('F j, Y'); ?></p>
    </div>
    
    <!-- Request Summary -->
    <div class="mb-6">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">Blood Request Details</h2>
                <p style="font-size: 14px; color: #666;">Request #<?php echo $request['request_number']; ?></p>
            </div>
            <div style="text-align: right;">
                <span style="padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; 
                      <?php echo $request['urgency_level'] === 'critical' ? 'background-color: #fee2e2; color: #991b1b; border: 1px solid #dc2626;' : 
                       ($request['urgency_level'] === 'urgent' ? 'background-color: #fef3c7; color: #92400e; border: 1px solid #d97706;' : 
                       'background-color: #dbeafe; color: #1e40af; border: 1px solid #3b82f6;'); ?>">
                    <?php echo strtoupper($request['urgency_level']); ?>
                </span>
            </div>
        </div>
        
        <div style="background-color: #f9fafb; border: 1px solid #000; padding: 15px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                <div>
                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Status</p>
                    <p style="font-size: 16px; font-weight: bold;"><?php echo strtoupper($request['status']); ?></p>
                </div>
                <div>
                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Blood Type</p>
                    <p style="font-size: 20px; font-weight: bold; color: #dc2626;"><?php echo $request['blood_type']; ?></p>
                </div>
                <div>
                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Units Required</p>
                    <p style="font-size: 20px; font-weight: bold;"><?php echo $request['units_required']; ?> units</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patient Information -->
    <div class="page-break">
        <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #000;">Patient Information</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
            <div>
                <table style="width: 100%;">
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0; width: 40%;">Patient Name:</td>
                        <td style="font-size: 14px; font-weight: bold; padding: 5px 0;"><?php echo htmlspecialchars($request['patient_name']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Age & Gender:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo $request['patient_age']; ?> years, <?php echo $request['patient_sex']; ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Ward/Department:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo htmlspecialchars($request['patient_ward']); ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table style="width: 100%;">
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0; width: 40%;">Required Date:</td>
                        <td style="font-size: 14px; font-weight: bold; padding: 5px 0;"><?php echo date('F j, Y', strtotime($request['required_date'])); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Request Date:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Days Remaining:</td>
                        <td style="font-size: 14px; font-weight: bold; color: <?php echo $daysLeft < 0 ? '#dc2626' : ($daysLeft <= 2 ? '#ea580c' : '#059669'); ?>; padding: 5px 0;">
                            <?php 
                            if ($daysLeft < 0) {
                                echo 'Overdue by ' . abs($daysLeft) . ' days';
                            } elseif ($daysLeft === 0) {
                                echo 'Due today';
                            } elseif ($daysLeft === 1) {
                                echo 'Due tomorrow';
                            } else {
                                echo $daysLeft . ' days remaining';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($request['reason']): ?>
        <div style="margin-bottom: 25px;">
            <h4 style="font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #666;">Reason for Request:</h4>
            <div style="background-color: #f9fafb; border: 1px solid #d1d5db; padding: 12px; border-radius: 4px;">
                <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Hospital Information -->
    <div class="page-break">
        <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #000;">Requesting Hospital</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
            <div>
                <table style="width: 100%;">
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0; width: 40%;">Hospital Name:</td>
                        <td style="font-size: 14px; font-weight: bold; padding: 5px 0;"><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Registration No:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo htmlspecialchars($request['registration_number']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Location:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo htmlspecialchars($request['hospital_location']); ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table style="width: 100%;">
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0; width: 40%;">Contact Person:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo htmlspecialchars($request['contact_person']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Contact Email:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo htmlspecialchars($request['contact_email']); ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 5px 0;">Contact Phone:</td>
                        <td style="font-size: 14px; padding: 5px 0;"><?php echo htmlspecialchars($request['contact_phone']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Approval & Fulfillment History -->
    <div class="page-break">
        <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #000;">Request Timeline</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #059669; border-radius: 50%; margin-right: 10px;"></span>
                    <span style="font-size: 14px; font-weight: bold;">Request Submitted</span>
                    <span style="font-size: 12px; color: #666; float: right;"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                </td>
            </tr>
            
            <?php if ($request['approved_at']): ?>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #059669; border-radius: 50%; margin-right: 10px;"></span>
                    <span style="font-size: 14px; font-weight: bold;">Request Approved</span>
                    <span style="font-size: 12px; color: #666; float: right;"><?php echo date('M j, Y g:i A', strtotime($request['approved_at'])); ?></span>
                    <?php if ($request['approved_by_email']): ?>
                    <div style="font-size: 12px; color: #666; margin-top: 2px;">By: <?php echo htmlspecialchars($request['approved_by_email']); ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if ($request['fulfilled_at']): ?>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #3b82f6; border-radius: 50%; margin-right: 10px;"></span>
                    <span style="font-size: 14px; font-weight: bold;">Request Fulfilled</span>
                    <span style="font-size: 12px; color: #666; float: right;"><?php echo date('M j, Y g:i A', strtotime($request['fulfilled_at'])); ?></span>
                    <?php if ($request['fulfilled_by_email']): ?>
                    <div style="font-size: 12px; color: #666; margin-top: 2px;">By: <?php echo htmlspecialchars($request['fulfilled_by_email']); ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <td style="padding: 10px 0;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: <?php echo $daysLeft < 0 ? '#dc2626' : ($daysLeft <= 2 ? '#ea580c' : '#3b82f6'); ?>; border-radius: 50%; margin-right: 10px;"></span>
                    <span style="font-size: 14px; font-weight: bold;">Required By</span>
                    <span style="font-size: 12px; color: #666; float: right;"><?php echo date('F j, Y', strtotime($request['required_date'])); ?></span>
                    <div style="font-size: 12px; color: <?php echo $daysLeft < 0 ? '#dc2626' : ($daysLeft <= 2 ? '#ea580c' : '#666'); ?>; margin-top: 2px;">
                        <?php 
                        if ($daysLeft < 0) {
                            echo 'Overdue by ' . abs($daysLeft) . ' days';
                        } elseif ($daysLeft === 0) {
                            echo 'Due today';
                        } elseif ($daysLeft === 1) {
                            echo 'Due tomorrow';
                        } else {
                            echo 'In ' . $daysLeft . ' days';
                        }
                        ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Stock Information -->
    <div>
        <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #000;">Stock Information</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
            <div style="background-color: #f9fafb; border: 1px solid #d1d5db; padding: 15px; border-radius: 4px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 8px 0; width: 60%;">Blood Type Required:</td>
                        <td style="font-size: 18px; font-weight: bold; color: #dc2626; padding: 8px 0;"><?php echo $request['blood_type']; ?></td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 8px 0;">Units Required:</td>
                        <td style="font-size: 18px; font-weight: bold; padding: 8px 0;"><?php echo $request['units_required']; ?> units</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #666; padding: 8px 0;">Available Stock:</td>
                        <td style="font-size: 18px; font-weight: bold; color: <?php echo $request['stock_available'] >= $request['units_required'] ? '#059669' : '#dc2626'; ?>; padding: 8px 0;">
                            <?php echo $request['stock_available']; ?> units
                        </td>
                    </tr>
                </table>
                
                <div style="margin-top: 15px; padding: 10px; background-color: <?php echo $request['stock_available'] >= $request['units_required'] ? '#d1fae5' : '#fee2e2'; ?>; border-radius: 4px; border: 1px solid <?php echo $request['stock_available'] >= $request['units_required'] ? '#a7f3d0' : '#fecaca'; ?>;">
                    <p style="font-size: 14px; font-weight: bold; color: <?php echo $request['stock_available'] >= $request['units_required'] ? '#065f46' : '#991b1b'; ?>; margin: 0;">
                        <?php if ($request['stock_available'] >= $request['units_required']): ?>
                        ✓ Sufficient stock available
                        <?php else: ?>
                        ✗ Insufficient stock - Deficit: <?php echo $request['units_required'] - $request['stock_available']; ?> units
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div style="background-color: #f9fafb; border: 1px solid #d1d5db; padding: 15px; border-radius: 4px;">
                <h4 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; color: #666;">Admin Notes:</h4>
                <?php if ($request['admin_notes']): ?>
                <div style="font-size: 13px; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></div>
                <?php else: ?>
                <div style="font-size: 13px; color: #9ca3af; font-style: italic;">No admin notes added</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Printed from BloodSync Blood Bank Management System on <?php echo date('F j, Y \a\t g:i A'); ?></p>
        <p>Request ID: <?php echo $request['request_number']; ?> | Patient: <?php echo htmlspecialchars($request['patient_name']); ?></p>
        <p>© <?php echo date('Y'); ?> BloodSync. All rights reserved.</p>
    </div>
</div>

<!-- Main Webpage Content -->
<div class="container mx-auto px-4 py-8 max-w-6xl no-print">
    <!-- Header with Actions -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Blood Request Details</h1>
            <p class="text-gray-600">Request #<?php echo $request['request_number']; ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="blood_requests.php" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-arrow-left-line mr-1"></i> Back
            </a>
            <?php if ($request['status'] === 'pending'): ?>
            <a href="approve_blood_request.php?id=<?php echo $request['id']; ?>" 
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                <i class="ri-check-line mr-1"></i> Approve
            </a>
            <a href="reject_blood_request.php?id=<?php echo $request['id']; ?>" 
               class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                <i class="ri-close-line mr-1"></i> Reject
            </a>
            <?php elseif ($request['status'] === 'approved'): ?>
            <a href="fulfill_blood_request.php?id=<?php echo $request['id']; ?>" 
               class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                <i class="ri-check-double-line mr-1"></i> Mark Fulfilled
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Alert if stock is low -->
    <?php if ($request['stock_available'] < $request['units_required'] && $request['status'] === 'pending'): ?>
    <div class="bg-red-100 border border-red-300 rounded-xl p-4 mb-8">
        <div class="flex items-center">
            <i class="ri-alarm-warning-line text-red-600 text-2xl mr-3"></i>
            <div>
                <h3 class="font-bold text-red-800">Low Stock Alert</h3>
                <p class="text-red-700">
                    Only <?php echo $request['stock_available']; ?> units of <?php echo $request['blood_type']; ?> available.
                    Request requires <?php echo $request['units_required']; ?> units.
                    <span class="font-medium">Deficit: <?php echo $request['units_required'] - $request['stock_available']; ?> units.</span>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Patient & Request Details -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Patient Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Patient Information</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Patient Name</p>
                            <p class="font-medium text-lg"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Age & Gender</p>
                            <p class="font-medium"><?php echo $request['patient_age']; ?> years, <?php echo $request['patient_sex']; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Ward/Department</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['patient_ward']); ?></p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Blood Type Required</p>
                            <p class="font-bold text-2xl text-red-600"><?php echo $request['blood_type']; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Units Required</p>
                            <p class="font-bold text-3xl"><?php echo $request['units_required']; ?></p>
                            <p class="text-sm text-gray-500">units of blood</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($request['reason']): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-500 text-sm mb-2">Reason for Request</p>
                    <p class="text-gray-700 bg-gray-50 p-4 rounded-lg"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Request Details -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Request Details</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Request Number</p>
                            <p class="font-mono font-bold"><?php echo $request['request_number']; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Urgency Level</p>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $urgencyColor; ?>">
                                <?php echo ucfirst($request['urgency_level']); ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Status</p>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Required Date</p>
                            <p class="font-medium"><?php echo date('l, F j, Y', strtotime($request['required_date'])); ?></p>
                            <?php 
                            if ($daysLeft < 0) {
                                echo '<p class="text-red-500 text-sm mt-1">Overdue by ' . abs($daysLeft) . ' days</p>';
                            } elseif ($daysLeft === 0) {
                                echo '<p class="text-orange-500 text-sm mt-1">Due today</p>';
                            } else {
                                echo '<p class="text-gray-500 text-sm mt-1">In ' . $daysLeft . ' day' . ($daysLeft !== 1 ? 's' : '') . '</p>';
                            }
                            ?>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Request Date</p>
                            <p class="font-medium"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></p>
                        </div>
                        <?php if ($request['approved_at']): ?>
                        <div>
                            <p class="text-gray-500 text-sm">Approved Date</p>
                            <p class="font-medium"><?php echo date('M j, Y g:i A', strtotime($request['approved_at'])); ?></p>
                            <?php if ($request['approved_by_email']): ?>
                            <p class="text-sm text-gray-500">By: <?php echo htmlspecialchars($request['approved_by_email']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($request['admin_notes']): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-500 text-sm mb-2">Admin Notes</p>
                    <p class="text-gray-700 bg-gray-50 p-4 rounded-lg"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Hospital Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Requesting Hospital</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Hospital Name</p>
                            <p class="font-medium text-lg"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Registration Number</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['registration_number']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Location</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['hospital_location']); ?></p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Contact Person</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['contact_person']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Contact Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['contact_email']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Contact Phone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['contact_phone']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Actions & Info -->
        <div class="space-y-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <?php if ($request['status'] === 'pending'): ?>
                    <a href="approve_blood_request.php?id=<?php echo $request['id']; ?>" 
                       class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-check-line text-green-600 mr-3"></i>
                            <span>Approve Request</span>
                        </div>
                        <i class="ri-arrow-right-line text-green-600"></i>
                    </a>
                    <a href="reject_blood_request.php?id=<?php echo $request['id']; ?>" 
                       class="flex items-center justify-between p-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-close-line text-red-600 mr-3"></i>
                            <span>Reject Request</span>
                        </div>
                        <i class="ri-arrow-right-line text-red-600"></i>
                    </a>
                    <?php elseif ($request['status'] === 'approved'): ?>
                    <a href="fulfill_blood_request.php?id=<?php echo $request['id']; ?>" 
                       class="flex items-center justify-between p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-check-double-line text-purple-600 mr-3"></i>
                            <span>Mark as Fulfilled</span>
                        </div>
                        <i class="ri-arrow-right-line text-purple-600"></i>
                    </a>
                    <?php endif; ?>
                    
                    <a href="blood_stocks.php" 
                       class="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-drop-line text-blue-600 mr-3"></i>
                            <span>Check Blood Stock</span>
                        </div>
                        <i class="ri-arrow-right-line text-blue-600"></i>
                    </a>
                    
                    <a href="#" onclick="printRequest()" 
                       class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-printer-line text-gray-600 mr-3"></i>
                            <span>Print Request</span>
                        </div>
                        <i class="ri-arrow-right-line text-gray-600"></i>
                    </a>
                </div>
            </div>
            
            <!-- Stock Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Stock Information</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Blood Type</span>
                        <span class="font-bold text-lg"><?php echo $request['blood_type']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Units Required</span>
                        <span class="font-bold text-xl"><?php echo $request['units_required']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Available Stock</span>
                        <span class="font-bold text-xl <?php echo $request['stock_available'] >= $request['units_required'] ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $request['stock_available']; ?>
                        </span>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <?php if ($request['stock_available'] >= $request['units_required']): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="ri-checkbox-circle-line text-green-600 mr-2"></i>
                                <span class="text-green-700 font-medium">Sufficient stock available</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="ri-alarm-warning-line text-red-600 mr-2"></i>
                                <span class="text-red-700 font-medium">Insufficient stock</span>
                            </div>
                            <p class="text-red-600 text-sm mt-1">
                                Deficit: <?php echo $request['units_required'] - $request['stock_available']; ?> units
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="blood_stocks.php" class="text-blue-600 hover:underline font-medium">
                        Manage blood stock →
                    </a>
                </div>
            </div>
            
            <!-- Similar Requests -->
            <?php if (!empty($similarRequests)): ?>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Similar Pending Requests</h2>
                <div class="space-y-3">
                    <?php foreach ($similarRequests as $similar): ?>
                    <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-sm"><?php echo htmlspecialchars($similar['patient_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($similar['hospital_name']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold"><?php echo $similar['units_required']; ?> units</p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('M j', strtotime($similar['required_date'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs px-2 py-1 bg-gray-100 rounded">
                                <?php echo $similar['urgency_level']; ?>
                            </span>
                            <a href="view_blood_request.php?id=<?php echo $similar['id']; ?>" 
                               class="text-xs text-blue-600 hover:underline">
                                View
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Timeline -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Request Timeline</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-green-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Request Submitted</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($request['approved_at']): ?>
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-green-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Request Approved</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($request['approved_at'])); ?></p>
                            <?php if ($request['approved_by_email']): ?>
                            <p class="text-xs text-gray-500">By: <?php echo htmlspecialchars($request['approved_by_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['fulfilled_at']): ?>
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Request Fulfilled</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($request['fulfilled_at'])); ?></p>
                            <?php if ($request['fulfilled_by_email']): ?>
                            <p class="text-xs text-gray-500">By: <?php echo htmlspecialchars($request['fulfilled_by_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-<?php echo $daysLeft < 0 ? 'red' : ($daysLeft <= 2 ? 'orange' : 'blue'); ?>-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Required By</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($request['required_date'])); ?></p>
                            <p class="text-xs <?php echo $daysLeft < 0 ? 'text-red-500' : ($daysLeft <= 2 ? 'text-orange-500' : 'text-gray-500'); ?>">
                                <?php 
                                if ($daysLeft < 0) {
                                    echo 'Overdue by ' . abs($daysLeft) . ' days';
                                } elseif ($daysLeft === 0) {
                                    echo 'Due today';
                                } elseif ($daysLeft === 1) {
                                    echo 'Due tomorrow';
                                } else {
                                    echo 'In ' . $daysLeft . ' days';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print JavaScript -->
<script>
function printRequest() {
    // Show the print-friendly version
    const printContent = document.querySelector('.print-request');
    printContent.style.display = 'block';
    
    // Print it
    window.print();
    
    // Hide it again
    printContent.style.display = 'none';
}

// Close print-friendly version after printing
window.addEventListener('afterprint', function() {
    const printContent = document.querySelector('.print-request');
    printContent.style.display = 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?>