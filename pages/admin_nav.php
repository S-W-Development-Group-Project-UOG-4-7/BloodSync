<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-gray-900 text-white">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center space-x-2">
                <i class="ri-admin-line text-xl"></i>
                <span class="text-xl font-bold">BloodSync Admin</span>
            </div>
            
            <!-- Navigation Links -->
            <div class="flex items-center space-x-6">
                <a href="dashboard.php" 
                   class="<?php echo $current_page == 'dashboard.php' ? 'text-red-400 font-bold' : 'text-gray-300 hover:text-white'; ?>">
                    <i class="ri-dashboard-line mr-2"></i>Dashboard
                </a>
                
                <a href="blood_requests.php" 
                   class="<?php echo $current_page == 'blood_requests.php' ? 'text-red-400 font-bold' : 'text-gray-300 hover:text-white'; ?>">
                    <i class="ri-file-medical-line mr-2"></i>Blood Requests
                </a>

                <a href="medical_reports.php" 
                    class="<?php echo $current_page == 'medical_reports.php' ? 'text-red-400 font-bold' : 'text-gray-300 hover:text-white'; ?>">
                    <i class="ri-file-medical-line mr-2"></i>Medical Reports
                </a>
                
                <a href="all_donors.php" 
                   class="<?php echo $current_page == 'all_donors.php' ? 'text-red-400 font-bold' : 'text-gray-300 hover:text-white'; ?>">
                    <i class="ri-user-search-line mr-2"></i>All Donors
                </a>
                
                
                <a href="blood_stocks.php" 
                    class="<?php echo $current_page == 'blood_stocks.php' ? 'text-red-400 font-bold' : 'text-gray-300 hover:text-white'; ?>">
                    <i class="ri-drop-line mr-2"></i>Blood Stock
                </a>

                <a href="donor_donations.php" 
                    class="<?php echo $current_page == 'donor_donations.php' ? 'text-red-400 font-bold' : 'text-gray-300 hover:text-white'; ?>">
                    <i class="ri-heart-pulse-line mr-2"></i>Donor Donations
                </a>
            
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                    <i class="ri-logout-box-line mr-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Admin Sub Navigation -->
<div class="bg-gray-800 text-white">
    <div class="container mx-auto px-4 py-2">
        <div class="flex items-center space-x-4 text-sm">
            <span class="text-gray-400">
                <i class="ri-user-line mr-1"></i>
                <?php echo htmlspecialchars($_SESSION['email'] ?? 'Admin'); ?>
            </span>
            <span class="text-gray-400">|</span>
            <span class="text-gray-400">
                <i class="ri-time-line mr-1"></i>
                <?php echo date('F j, Y'); ?>
            </span>
        </div>
    </div>
</div>