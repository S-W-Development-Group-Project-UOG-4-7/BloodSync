<?php include('partials/header.php'); ?>

<?php
$donor = [
    'name' => 'Sashika Dulanga',
    'id' => 'UOG0723015',
    'blood_type' => 'O+',
    'email' => 'sashika@example.com',
    'phone' => '076 123 4567',
    'address' => '123, Temple Road, Colombo',
    'last_donation' => '2025-09-15',
    'total_donations' => 5,
    'status' => 'Eligible', // or 'Waiting'
    'days_remaining' => 0
];

// Logic to calculate eligibility (Mock)
$next_eligible_date = date('Y-m-d', strtotime($donor['last_donation'] . ' + 4 months'));
$today = date('Y-m-d');
$is_eligible = ($today >= $next_eligible_date);
?>

<div class="min-h-screen bg-gray-50 flex">

    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-200 h-screen fixed">
        <div class="p-6 flex items-center gap-3">
            <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center text-white font-bold">B</div>
            <span class="text-xl font-bold text-gray-800">BloodSync</span>
        </div>
        
        <nav class="flex-1 px-4 space-y-2 mt-4">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-red-50 text-red-700 rounded-xl font-medium">
                <i class="ri-user-smile-line text-xl"></i> My Profile
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition">
                <i class="ri-calendar-check-line text-xl"></i> Appointments
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition">
                <i class="ri-history-line text-xl"></i> History
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition">
                <i class="ri-medal-line text-xl"></i> Badges
            </a>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:text-red-600 transition">
                <i class="ri-logout-box-line"></i> Log Out
            </a>
        </div>
    </aside>

    <main class="flex-1 md:ml-64 p-6 lg:p-10">
        
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Hello, <?php echo explode(' ', $donor['name'])[0]; ?>! 👋</h1>
                <p class="text-gray-500">Welcome back to your donor dashboard.</p>
            </div>
            
            <?php if($is_eligible): ?>
                <button class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl shadow-lg shadow-red-200 transition transform hover:-translate-y-1">
                    <i class="ri-add-line"></i> Book Donation
                </button>
            <?php else: ?>
                <button disabled class="flex items-center gap-2 bg-gray-300 text-gray-500 px-6 py-3 rounded-xl cursor-not-allowed">
                    <i class="ri-time-line"></i> Wait Period
                </button>
            <?php endif; ?>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <div class="col-span-1 relative h-56 rounded-3xl overflow-hidden shadow-2xl transition-transform transform hover:scale-[1.02]">
                <div class="absolute inset-0 bg-gradient-to-br from-red-600 to-red-900"></div>
                <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -ml-10 -mb-10 w-40 h-40 bg-red-400 opacity-20 rounded-full blur-xl"></div>

                <div class="relative z-10 p-6 flex flex-col justify-between h-full text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-red-200 text-xs tracking-widest uppercase font-semibold">BloodSync Donor ID</p>
                            <h3 class="text-xl font-bold mt-1 tracking-wide"><?php echo $donor['name']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center border border-white/30">
                            <span class="text-xl font-bold"><?php echo $donor['blood_type']; ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-red-200 text-xs">Donor Number</p>
                            <p class="font-mono text-lg tracking-widest"><?php echo $donor['id']; ?></p>
                        </div>
                        <div class="bg-white p-1 rounded-lg">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $donor['id']; ?>" class="w-12 h-12" alt="QR">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-1 lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="relative w-20 h-20">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-100" />
                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" fill="transparent" 
                                class="<?php echo $is_eligible ? 'text-green-500' : 'text-yellow-400'; ?>" 
                                stroke-dasharray="226" 
                                stroke-dashoffset="<?php echo $is_eligible ? '0' : '50'; // dynamic offset logic needed ?>" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="<?php echo $is_eligible ? 'ri-check-line' : 'ri-time-line'; ?> text-2xl text-gray-600"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Eligibility Status</p>
                        <h3 class="text-xl font-bold text-gray-900"><?php echo $is_eligible ? 'Eligible Now' : 'Wait Period'; ?></h3>
                        <p class="text-xs text-gray-400">Next: <?php echo $next_eligible_date; ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-center">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                            <i class="ri-heart-pulse-fill"></i>
                        </div>
                        <span class="text-gray-500 font-medium">Lives Saved</span>
                    </div>
                    <h3 class="text-3xl font-extrabold text-gray-900"><?php echo $donor['total_donations'] * 3; ?> <span class="text-sm font-normal text-gray-400">est.</span></h3>
                    <p class="text-sm text-green-600 mt-1 flex items-center">
                        <i class="ri-arrow-up-line"></i> Top 10% of donors
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Personal Information</h3>
                    <button class="text-red-600 text-sm font-semibold hover:underline">Edit Details</button>
                </div>
                
                <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Full Name</label>
                        <input type="text" value="<?php echo $donor['name']; ?>" readonly 
                            class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 font-medium focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">NIC / ID</label>
                        <input type="text" value="<?php echo $donor['id']; ?>" readonly 
                            class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 font-medium focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Email</label>
                        <input type="email" value="<?php echo $donor['email']; ?>" readonly 
                            class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 font-medium focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Phone</label>
                        <input type="text" value="<?php echo $donor['phone']; ?>" readonly 
                            class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 font-medium focus:ring-0">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Address</label>
                        <input type="text" value="<?php echo $donor['address']; ?>" readonly 
                            class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 font-medium focus:ring-0">
                    </div>
                </form>
            </div>

            <div class="lg:col-span-1 bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Your Badges</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-3 rounded-2xl bg-yellow-50 border border-yellow-100">
                        <div class="w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center text-white text-2xl shadow-sm">
                            🏆
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm">Life Saver</h4>
                            <p class="text-xs text-gray-500">5 Donations Completed</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-2xl bg-blue-50 border border-blue-100">
                        <div class="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center text-white text-2xl shadow-sm">
                            🩸
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm">First Blood</h4>
                            <p class="text-xs text-gray-500">Joined the community</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-2xl bg-gray-50 border border-gray-100 opacity-60 grayscale">
                        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-white text-2xl">
                            🔒
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm">Hero (Locked)</h4>
                            <p class="text-xs text-gray-500">Donate 10 times</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
 ?>