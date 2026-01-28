<?php
require_once __DIR__ . '/../../autoload.php';
Auth::requireAdmin('../login.php');

require_once '../includes/header.php';
require_once 'admin_nav.php';

// Fetch hospitals
$hospitals = Database::fetchAll("
    SELECT h.*, u.email, u.verification_status, u.verified_at, 
           u2.email as verified_by_email
    FROM hospitals h
    JOIN users u ON h.user_id = u.id
    LEFT JOIN users u2 ON h.verified_by = u2.id
    ORDER BY h.created_at DESC
", []);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Manage Hospitals</h1>
    
    <!-- Stats -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-gray-500 text-sm">Total Hospitals</p>
            <h3 class="text-2xl font-bold mt-2"><?php echo count($hospitals); ?></h3>
        </div>
        <div class="bg-yellow-50 rounded-xl shadow p-6">
            <p class="text-yellow-600 text-sm">Pending</p>
            <h3 class="text-2xl font-bold mt-2 text-yellow-700">
                <?php echo count(array_filter($hospitals, fn($h) => $h['verification_status'] === 'pending')); ?>
            </h3>
        </div>
        <div class="bg-green-50 rounded-xl shadow p-6">
            <p class="text-green-600 text-sm">Verified</p>
            <h3 class="text-2xl font-bold mt-2 text-green-700">
                <?php echo count(array_filter($hospitals, fn($h) => $h['verification_status'] === 'approved')); ?>
            </h3>
        </div>
        <div class="bg-red-50 rounded-xl shadow p-6">
            <p class="text-red-600 text-sm">Rejected</p>
            <h3 class="text-2xl font-bold mt-2 text-red-700">
                <?php echo count(array_filter($hospitals, fn($h) => $h['verification_status'] === 'rejected')); ?>
            </h3>
        </div>
    </div>
    
    <!-- Hospitals Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hospital</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($hospitals as $hospital): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($hospital['hospital_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($hospital['location']); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm"><?php echo htmlspecialchars($hospital['registration_number']); ?></p>
                            <?php if ($hospital['license_number']): ?>
                            <p class="text-xs text-gray-500">License: <?php echo htmlspecialchars($hospital['license_number']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm"><?php echo htmlspecialchars($hospital['contact_person']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($hospital['contact_email']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($hospital['contact_phone']); ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $statusColor = match($hospital['verification_status']) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                <?php echo ucfirst($hospital['verification_status']); ?>
                            </span>
                            <?php if ($hospital['verified_at']): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo date('M d, Y', strtotime($hospital['verified_at'])); ?>
                            </p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="view_hospital.php?id=<?php echo $hospital['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    View
                                </a>
                                <?php if ($hospital['verification_status'] === 'pending'): ?>
                                <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=approve" 
                                   class="text-green-600 hover:text-green-800 text-sm font-medium">
                                    Approve
                                </a>
                                <a href="verify_hospital.php?id=<?php echo $hospital['id']; ?>&action=reject" 
                                   class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    Reject
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>