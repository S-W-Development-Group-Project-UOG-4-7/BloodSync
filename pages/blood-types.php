<?php include 'includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Blood Types Information</h1>
        <p class="text-gray-600 mb-10">Understanding blood types and compatibility for safe transfusions.</p>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-10">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Blood Type Compatibility</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Can Donate To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Can Receive From</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Population %</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $blood_types = [
                            ['type' => 'O-', 'donate' => 'All Blood Types', 'receive' => 'O-', 'population' => '7%', 'color' => 'bg-red-600'],
                            ['type' => 'O+', 'donate' => 'O+, A+, B+, AB+', 'receive' => 'O+, O-', 'population' => '38%', 'color' => 'bg-red-500'],
                            ['type' => 'A-', 'donate' => 'A-, A+, AB-, AB+', 'receive' => 'A-, O-', 'population' => '6%', 'color' => 'bg-blue-600'],
                            ['type' => 'A+', 'donate' => 'A+, AB+', 'receive' => 'A+, A-, O+, O-', 'population' => '34%', 'color' => 'bg-blue-500'],
                            ['type' => 'B-', 'donate' => 'B-, B+, AB-, AB+', 'receive' => 'B-, O-', 'population' => '2%', 'color' => 'bg-green-600'],
                            ['type' => 'B+', 'donate' => 'B+, AB+', 'receive' => 'B+, B-, O+, O-', 'population' => '9%', 'color' => 'bg-green-500'],
                            ['type' => 'AB-', 'donate' => 'AB-, AB+', 'receive' => 'All Negative Types', 'population' => '1%', 'color' => 'bg-purple-600'],
                            ['type' => 'AB+', 'donate' => 'AB+', 'receive' => 'All Blood Types', 'population' => '3%', 'color' => 'bg-purple-500'],
                        ];
                        
                        foreach ($blood_types as $bt): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center">
                                    <span class="w-3 h-3 rounded-full <?php echo $bt['color']; ?> mr-2"></span>
                                    <span class="text-lg font-semibold text-gray-900"><?php echo $bt['type']; ?></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo $bt['donate']; ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo $bt['receive']; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 rounded-full">
                                    <?php echo $bt['population']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Universal Donor</h3>
                <div class="flex items-center mb-4">
                    <span class="w-4 h-4 rounded-full bg-red-600 mr-2"></span>
                    <span class="text-lg font-bold text-gray-900">O- Blood Type</span>
                </div>
                <p class="text-gray-600">
                    O- is considered the universal donor because it can be transfused to patients of any blood type.
                    This makes it especially valuable in emergency situations when there's no time to determine
                    the patient's blood type.
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Universal Recipient</h3>
                <div class="flex items-center mb-4">
                    <span class="w-4 h-4 rounded-full bg-purple-500 mr-2"></span>
                    <span class="text-lg font-bold text-gray-900">AB+ Blood Type</span>
                </div>
                <p class="text-gray-600">
                    AB+ individuals can receive red blood cells from donors of any blood type. However,
                    they can only donate to other AB+ recipients. This blood type is relatively rare,
                    making up only about 3% of the population.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>