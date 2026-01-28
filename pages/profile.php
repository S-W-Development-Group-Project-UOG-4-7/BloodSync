<?php
require_once __DIR__ . '/../autoload.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';

// Check authentication
Auth::requireAuth('login.php');

$user = Auth::getUser();
$profile = Auth::getDonorProfile();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
            <p class="text-gray-600">Manage your donor information</p>
        </div>
        
        <!-- Profile Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <!-- Header with Blood Type -->
            <div class="bg-gradient-to-r from-red-600 to-red-800 p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold"><?php echo $user['name']; ?></h2>
                        <p class="text-red-200">Donor ID: <?php echo substr($profile['id'] ?? '000000', 0, 8); ?></p>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center border-2 border-white/30">
                        <span class="text-2xl font-bold"><?php echo $profile['blood_type']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Personal Information</h3>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">Full Name</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['first_name'] . ' ' . $profile['last_name']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">Email Address</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['email']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">NIC Number</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['nic']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">Phone Number</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['contact_number']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">Date of Birth</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo date('F j, Y', strtotime($profile['date_of_birth'])); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">Blood Type</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg font-bold">
                            <?php echo $profile['blood_type']; ?>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-500 text-sm mb-1">Address</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['address']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">City</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['city']; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-500 text-sm mb-1">Weight</label>
                        <div class="bg-gray-50 px-4 py-3 rounded-lg">
                            <?php echo $profile['weight']; ?> kg
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-8 flex space-x-4">
                    <a href="dashboard.php" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-lg transition">
                        Back to Dashboard
                    </a>
                    <a href="edit_profile.php" 
                        class="bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-3 rounded-lg transition">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Donation Stats</h3>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <i class="ri-heart-pulse-line text-3xl text-red-600 mb-2"></i>
                    <p class="text-gray-500">Status</p>
                    <p class="text-2xl font-bold text-gray-900">Active Donor</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <i class="ri-medal-line text-3xl text-blue-600 mb-2"></i>
                    <p class="text-gray-500">Member Since</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php echo date('M Y', strtotime($profile['created_at'])); ?>
                    </p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <i class="ri-user-star-line text-3xl text-green-600 mb-2"></i>
                    <p class="text-gray-500">Donor Level</p>
                    <p class="text-2xl font-bold text-gray-900">Bronze</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>