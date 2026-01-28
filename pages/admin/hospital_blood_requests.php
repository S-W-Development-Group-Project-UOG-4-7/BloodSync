<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');

$requestId = $_GET['id'] ?? null;

if (!$requestId) {
    header('Location: manage_hospitals.php');
    exit();
}

// Get blood request details
$request = Database::fetch(
    "SELECT br.*, h.hospital_name, h.contact_person, h.contact_email, h.contact_phone,
            u1.email as approved_by_email, u2.email as fulfilled_by_email
     FROM blood_requests br
     JOIN hospitals h ON br.hospital_id = h.id
     LEFT JOIN users u1 ON br.approved_by = u1.id
     LEFT JOIN users u2 ON br.fulfilled_by = u2.id
     WHERE br.id = :id",
    ['id' => $requestId]
);

if (!$request) {
    header('Location: manage_hospitals.php');
    exit();
}

// Get donations related to this request
$donations = Database::fetchAll(
    "SELECT dd.*, d.first_name, d.last_name, d.nic, d.blood_type as donor_blood_type,
            u.email as verified_by_email
     FROM donor_donations dd
     JOIN donors d ON dd.donor_id = d.id
     LEFT JOIN users u ON dd.verified_by = u.id
     WHERE dd.request_id = :request_id OR (dd.blood_type = :blood_type AND dd.donation_date = :required_date)
     ORDER BY dd.donation_date DESC",
    [
        'request_id' => $requestId,
        'blood_type' => $request['blood_type'],
        'required_date' => $request['required_date']
    ]
);

// Get total donations count and units
$donationsSummary = Database::fetch(
    "SELECT COUNT(*) as total_donations, SUM(units_donated) as total_units
     FROM donor_donations 
     WHERE request_id = :request_id",
    ['request_id' => $requestId]
);

require_once '../includes/header.php';
require_once 'admin_nav.php';
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Header with Actions -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Blood Request Details</h1>
            <p class="text-gray-600">Request #<?php echo htmlspecialchars($request['request_number']); ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="manage_hospitals.php" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-arrow-left-line mr-1"></i> Back to Hospitals
            </a>
            <a href="hospital_blood_requests.php?hospital_id=<?php echo $request['hospital_id']; ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="ri-hospital-line mr-1"></i> Hospital Requests
            </a>
        </div>
    </div>
    
    <!-- Status Alert -->
    <?php if ($request['status'] === 'pending'): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
        <div class="flex items-center">
            <i class="ri-time-line text-yellow-600 text-2xl mr-4"></i>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-800 text-lg">Pending Approval</h3>
                <p class="text-yellow-700">
                    This blood request is awaiting approval. Review patient details and hospital information before proceeding.
                </p>
            </div>
            <div class="flex space-x-2">
                <?php if ($request['status'] === 'pending'): ?>
                <a href="process_request.php?id=<?php echo $request['id']; ?>&action=approve" 
                   class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    Approve Request
                </a>
                <a href="process_request.php?id=<?php echo $request['id']; ?>&action=reject" 
                   class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    Reject Request
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php elseif ($request['status'] === 'approved'): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-center">
            <i class="ri-check-double-line text-blue-600 text-2xl mr-4"></i>
            <div class="flex-1">
                <h3 class="font-bold text-blue-800 text-lg">Approved - Awaiting Fulfillment</h3>
                <p class="text-blue-700">
                    This request has been approved. Monitor donations and mark as fulfilled when requirements are met.
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="process_request.php?id=<?php echo $request['id']; ?>&action=fulfill" 
                   class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    Mark as Fulfilled
                </a>
            </div>
        </div>
    </div>
    <?php elseif ($request['status'] === 'fulfilled'): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
        <div class="flex items-center">
            <i class="ri-checkbox-circle-line text-green-600 text-2xl mr-4"></i>
            <div class="flex-1">
                <h3 class="font-bold text-green-800 text-lg">Request Fulfilled</h3>
                <p class="text-green-700">
                    This blood request has been successfully fulfilled. All required units have been collected.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Request Details -->
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
                            <p class="text-gray-500 text-sm">Age & Sex</p>
                            <p class="font-medium">
                                <?php echo htmlspecialchars($request['patient_age']); ?> years, 
                                <?php echo htmlspecialchars($request['patient_sex']); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Ward/Unit</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['patient_ward']); ?></p>
                        </div>
                        <?php if ($request['patient_id']): ?>
                        <div>
                            <p class="text-gray-500 text-sm">Patient ID</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['patient_id']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Blood Type Required</p>
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-50 text-red-700 font-bold text-xl">
                                    <?php echo $request['blood_type']; ?>
                                </span>
                                <div class="ml-4">
                                    <p class="font-bold text-lg"><?php echo $request['blood_type']; ?></p>
                                    <p class="text-gray-600"><?php echo $request['units_required']; ?> units required</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Urgency Level</p>
                            <?php
                            $urgencyColor = match($request['urgency_level']) {
                                'urgent' => 'bg-red-100 text-red-800',
                                'high' => 'bg-orange-100 text-orange-800',
                                'normal' => 'bg-blue-100 text-blue-800',
                                'low' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $urgencyColor; ?>">
                                <?php echo ucfirst($request['urgency_level']); ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Required Date</p>
                            <p class="font-medium"><?php echo date('M d, Y', strtotime($request['required_date'])); ?></p>
                            <?php 
                            $daysLeft = floor((strtotime($request['required_date']) - time()) / (60 * 60 * 24));
                            if ($daysLeft >= 0 && $daysLeft <= 3): ?>
                            <p class="text-sm <?php echo $daysLeft < 1 ? 'text-red-500' : 'text-orange-500'; ?>">
                                <?php echo $daysLeft == 0 ? 'Today' : ($daysLeft == 1 ? 'Tomorrow' : "{$daysLeft} days left"); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($request['reason']): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-500 text-sm mb-2">Reason for Request</p>
                    <p class="text-gray-700 bg-gray-50 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Hospital Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Hospital Information</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Hospital Name</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['hospital_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Contact Person</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['contact_person']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Contact Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['contact_email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Contact Phone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($request['contact_phone']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Request Submitted</p>
                            <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Last Updated</p>
                            <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <a href="view_hospital.php?id=<?php echo $request['hospital_id']; ?>" 
                           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                            View Hospital Details
                        </a>
                        <div class="flex space-x-2">
                            <a href="tel:<?php echo htmlspecialchars($request['contact_phone']); ?>" 
                               class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm">
                                <i class="ri-phone-line mr-1"></i> Call
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($request['contact_email']); ?>" 
                               class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm">
                                <i class="ri-mail-line mr-1"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Donations History -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Donations History</h2>
                    <?php if ($request['status'] === 'approved' || $request['status'] === 'pending'): ?>
                    <a href="add_donation.php?request_id=<?php echo $request['id']; ?>" 
                       class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                        <i class="ri-drop-line mr-1"></i> Add Donation
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Donations Summary -->
                <div class="grid md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-500 text-sm">Units Required</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $request['units_required']; ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-blue-600 text-sm">Units Collected</p>
                        <p class="text-2xl font-bold text-blue-700"><?php echo $donationsSummary['total_units'] ?? 0; ?></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-green-600 text-sm">Donations Made</p>
                        <p class="text-2xl font-bold text-green-700"><?php echo $donationsSummary['total_donations'] ?? 0; ?></p>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Collection Progress</span>
                        <span><?php echo min(100, round((($donationsSummary['total_units'] ?? 0) / $request['units_required']) * 100)); ?>%</span>
                    </div>
                    <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" 
                             style="width: <?php echo min(100, round((($donationsSummary['total_units'] ?? 0) / $request['units_required']) * 100)); ?>%"></div>
                    </div>
                </div>
                
                <?php if (empty($donations)): ?>
                    <p class="text-gray-500 text-center py-8">No donations recorded yet for this request</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blood Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($donations as $donation): 
                                    $donationStatusColor = match($donation['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'verified' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium"><?php echo htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name']); ?></p>
                                        <p class="text-xs text-gray-500">NIC: <?php echo htmlspecialchars($donation['nic']); ?></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold"><?php echo $donation['donor_blood_type']; ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-lg"><?php echo $donation['units_donated']; ?></span>
                                        <span class="text-sm text-gray-500">units</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($donation['donation_date'])); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $donationStatusColor; ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-1">
                                            <a href="view_donation.php?id=<?php echo $donation['id']; ?>" 
                                               class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Actions & Timeline -->
        <div class="space-y-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Request Actions</h2>
                <div class="space-y-3">
                    <?php if ($request['status'] === 'pending'): ?>
                    <a href="process_request.php?id=<?php echo $request['id']; ?>&action=approve" 
                       class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-check-double-line text-green-600 mr-3"></i>
                            <span>Approve Request</span>
                        </div>
                        <i class="ri-arrow-right-line text-green-600"></i>
                    </a>
                    <a href="process_request.php?id=<?php echo $request['id']; ?>&action=reject" 
                       class="flex items-center justify-between p-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-close-line text-red-600 mr-3"></i>
                            <span>Reject Request</span>
                        </div>
                        <i class="ri-arrow-right-line text-red-600"></i>
                    </a>
                    <?php elseif ($request['status'] === 'approved'): ?>
                    <a href="process_request.php?id=<?php echo $request['id']; ?>&action=fulfill" 
                       class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-checkbox-circle-line text-green-600 mr-3"></i>
                            <span>Mark as Fulfilled</span>
                        </div>
                        <i class="ri-arrow-right-line text-green-600"></i>
                    </a>
                    <a href="add_donation.php?request_id=<?php echo $request['id']; ?>" 
                       class="flex items-center justify-between p-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-drop-line text-red-600 mr-3"></i>
                            <span>Add Donation</span>
                        </div>
                        <i class="ri-arrow-right-line text-red-600"></i>
                    </a>
                    <?php endif; ?>
                    
                    <a href="#" onclick="window.print()" 
                       class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-printer-line text-gray-600 mr-3"></i>
                            <span>Print Details</span>
                        </div>
                        <i class="ri-arrow-right-line text-gray-600"></i>
                    </a>
                    
                    <a href="mailto:<?php echo htmlspecialchars($request['contact_email']); ?>" 
                       class="flex items-center justify-between p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-mail-line text-purple-600 mr-3"></i>
                            <span>Email Hospital</span>
                        </div>
                        <i class="ri-arrow-right-line text-purple-600"></i>
                    </a>
                    
                    <?php if ($request['status'] !== 'fulfilled' && $request['status'] !== 'rejected'): ?>
                    <a href="update_request.php?id=<?php echo $request['id']; ?>" 
                       class="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition">
                        <div class="flex items-center">
                            <i class="ri-edit-line text-blue-600 mr-3"></i>
                            <span>Update Request</span>
                        </div>
                        <i class="ri-arrow-right-line text-blue-600"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Request Status -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Request Status</h2>
                <div class="space-y-6">
                    <?php
                    $statusColor = match($request['status']) {
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'fulfilled' => 'bg-blue-100 text-blue-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                    ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Current Status</p>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-500 text-sm">Request #</p>
                            <p class="font-medium font-mono"><?php echo $request['request_number']; ?></p>
                        </div>
                    </div>
                    
                    <?php if ($request['admin_notes']): ?>
                    <div>
                        <p class="text-gray-500 text-sm mb-2">Admin Notes</p>
                        <p class="text-gray-700 bg-gray-50 p-3 rounded-lg text-sm"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Timeline -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Request Timeline</h2>
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-green-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Request Submitted</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></p>
                            <p class="text-xs text-gray-400">by <?php echo htmlspecialchars($request['hospital_name']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($request['approved_at']): ?>
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-green-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Request Approved</p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($request['approved_at'])); ?></p>
                            <?php if ($request['approved_by_email']): ?>
                            <p class="text-xs text-gray-400">by <?php echo htmlspecialchars($request['approved_by_email']); ?></p>
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
                            <p class="text-xs text-gray-400">by <?php echo htmlspecialchars($request['fulfilled_by_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'pending'): ?>
                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <p class="font-medium">Awaiting Approval</p>
                            <p class="text-sm text-gray-500">Pending admin review</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    $daysSince = floor((time() - strtotime($request['created_at'])) / (60 * 60 * 24));
                    ?>
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            <i class="ri-time-line mr-1"></i>
                            Request created <?php echo $daysSince; ?> day<?php echo $daysSince !== 1 ? 's' : ''; ?> ago
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>