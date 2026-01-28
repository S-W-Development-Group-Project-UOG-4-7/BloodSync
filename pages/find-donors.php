<?php include 'includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Find Blood Donors</h1>
        <p class="text-gray-600 mb-10">Search for available donors by blood type, location, and availability.</p>

        <!-- Search Form -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-10">
            <form class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Type</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">All Blood Types</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Enter city">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Any Time</option>
                        <option value="24h">Within 24 Hours</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
                
                <div class="md:col-span-3">
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                        Search Donors
                    </button>
                </div>
            </form>
        </div>

        <!-- Donor List -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Sample Donor Cards -->
            <?php
            $donors = [
                ['name' => 'John Smith', 'blood_type' => 'O+', 'city' => 'New York', 'last_donation' => '3 months ago', 'available' => true],
                ['name' => 'Maria Garcia', 'blood_type' => 'A-', 'city' => 'Los Angeles', 'last_donation' => '2 months ago', 'available' => true],
                ['name' => 'David Chen', 'blood_type' => 'B+', 'city' => 'Chicago', 'last_donation' => '1 month ago', 'available' => true],
                ['name' => 'Sarah Johnson', 'blood_type' => 'AB+', 'city' => 'Miami', 'last_donation' => '4 months ago', 'available' => false],
                ['name' => 'Robert Kim', 'blood_type' => 'O-', 'city' => 'Seattle', 'last_donation' => '2 weeks ago', 'available' => true],
                ['name' => 'Lisa Wong', 'blood_type' => 'A+', 'city' => 'Boston', 'last_donation' => '5 months ago', 'available' => true],
            ];
            
            foreach ($donors as $donor): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $donor['name']; ?></h3>
                        <p class="text-gray-600"><?php echo $donor['city']; ?></p>
                    </div>
                    <span class="bg-red-100 text-red-800 text-sm font-semibold px-3 py-1 rounded-full">
                        <?php echo $donor['blood_type']; ?>
                    </span>
                </div>
                
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last Donation:</span>
                        <span class="text-gray-900"><?php echo $donor['last_donation']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="<?php echo $donor['available'] ? 'text-green-600' : 'text-gray-500'; ?> font-medium">
                            <?php echo $donor['available'] ? 'Available' : 'Not Available'; ?>
                        </span>
                    </div>
                </div>
                
                <button class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                    Contact Donor
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>