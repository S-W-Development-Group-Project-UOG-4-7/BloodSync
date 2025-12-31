<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Sample donors data
$donors = [
  [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'phone' => '0712345678',
    'blood_type' => 'O+',
    'age' => 28,
    'gender' => 'Male',
    'location' => 'Colombo',
    'status' => 'active',
    'donation_count' => 5,
    'last_donation' => '2023-09-15',
    'next_eligible_date' => '2023-11-15',
    'registered_date' => '2022-01-15',
    'health_status' => 'excellent'
  ],
  [
    'id' => 2,
    'name' => 'Jane Smith',
    'email' => 'jane.smith@example.com',
    'phone' => '0723456789',
    'blood_type' => 'A+',
    'age' => 32,
    'gender' => 'Female',
    'location' => 'Kandy',
    'status' => 'active',
    'donation_count' => 3,
    'last_donation' => '2023-08-20',
    'next_eligible_date' => '2023-10-20',
    'registered_date' => '2022-03-10',
    'health_status' => 'good'
  ],
  [
    'id' => 3,
    'name' => 'Kamal Perera',
    'email' => 'kamal.perera@example.com',
    'phone' => '0734567890',
    'blood_type' => 'B-',
    'age' => 45,
    'gender' => 'Male',
    'location' => 'Galle',
    'status' => 'active',
    'donation_count' => 12,
    'last_donation' => '2023-10-10',
    'next_eligible_date' => '2023-12-10',
    'registered_date' => '2020-05-22',
    'health_status' => 'excellent'
  ],
  [
    'id' => 4,
    'name' => 'Nimal Silva',
    'email' => 'nimal.silva@example.com',
    'phone' => '0745678901',
    'blood_type' => 'AB+',
    'age' => 29,
    'gender' => 'Male',
    'location' => 'Jaffna',
    'status' => 'active',
    'donation_count' => 2,
    'last_donation' => '2023-07-05',
    'next_eligible_date' => '2023-09-05',
    'registered_date' => '2023-01-20',
    'health_status' => 'good'
  ],
  [
    'id' => 5,
    'name' => 'Sunil Rathnayake',
    'email' => 'sunil.r@example.com',
    'phone' => '0756789012',
    'blood_type' => 'O-',
    'age' => 36,
    'gender' => 'Male',
    'location' => 'Kurunegala',
    'status' => 'temporary_deferred',
    'donation_count' => 8,
    'last_donation' => '2023-06-18',
    'next_eligible_date' => '2024-01-18',
    'registered_date' => '2021-08-15',
    'health_status' => 'fair',
    'deferral_reason' => 'Recent surgery'
  ],
  [
    'id' => 6,
    'name' => 'Saman Kumara',
    'email' => 'saman.k@example.com',
    'phone' => '0767890123',
    'blood_type' => 'A-',
    'age' => 41,
    'gender' => 'Male',
    'location' => 'Badulla',
    'status' => 'active',
    'donation_count' => 7,
    'last_donation' => '2023-09-30',
    'next_eligible_date' => '2023-11-30',
    'registered_date' => '2021-11-05',
    'health_status' => 'excellent'
  ],
  [
    'id' => 7,
    'name' => 'Piyal Gunawardena',
    'email' => 'piyal.g@example.com',
    'phone' => '0778901234',
    'blood_type' => 'B+',
    'age' => 33,
    'gender' => 'Male',
    'location' => 'Ratnapura',
    'status' => 'inactive',
    'donation_count' => 4,
    'last_donation' => '2023-02-15',
    'next_eligible_date' => 'N/A',
    'registered_date' => '2022-04-10',
    'health_status' => 'good',
    'inactive_reason' => 'Moved overseas'
  ],
  [
    'id' => 8,
    'name' => 'Bandara Dissanayake',
    'email' => 'bandara.d@example.com',
    'phone' => '0789012345',
    'blood_type' => 'O+',
    'age' => 27,
    'gender' => 'Male',
    'location' => 'Anuradhapura',
    'status' => 'active',
    'donation_count' => 6,
    'last_donation' => '2023-09-25',
    'next_eligible_date' => '2023-11-25',
    'registered_date' => '2022-06-30',
    'health_status' => 'excellent'
  ],
  [
    'id' => 9,
    'name' => 'Anusha Fernando',
    'email' => 'anusha.f@example.com',
    'phone' => '0790123456',
    'blood_type' => 'A+',
    'age' => 31,
    'gender' => 'Female',
    'location' => 'Colombo',
    'status' => 'active',
    'donation_count' => 3,
    'last_donation' => '2023-08-12',
    'next_eligible_date' => '2023-10-12',
    'registered_date' => '2023-02-14',
    'health_status' => 'good'
  ],
  [
    'id' => 10,
    'name' => 'Rajitha Herath',
    'email' => 'rajitha.h@example.com',
    'phone' => '0701234567',
    'blood_type' => 'B-',
    'age' => 39,
    'gender' => 'Male',
    'location' => 'Kandy',
    'status' => 'active',
    'donation_count' => 15,
    'last_donation' => '2023-10-05',
    'next_eligible_date' => '2023-12-05',
    'registered_date' => '2019-03-25',
    'health_status' => 'excellent'
  ],
  [
    'id' => 11,
    'name' => 'Chamari Perera',
    'email' => 'chamari.p@example.com',
    'phone' => '0712345679',
    'blood_type' => 'AB-',
    'age' => 26,
    'gender' => 'Female',
    'location' => 'Galle',
    'status' => 'active',
    'donation_count' => 1,
    'last_donation' => '2023-07-30',
    'next_eligible_date' => '2023-09-30',
    'registered_date' => '2023-05-10',
    'health_status' => 'good'
  ],
  [
    'id' => 12,
    'name' => 'Dinesh Kumar',
    'email' => 'dinesh.k@example.com',
    'phone' => '0723456790',
    'blood_type' => 'O+',
    'age' => 34,
    'gender' => 'Male',
    'location' => 'Matara',
    'status' => 'pending_verification',
    'donation_count' => 0,
    'last_donation' => 'N/A',
    'next_eligible_date' => 'N/A',
    'registered_date' => '2023-10-10',
    'health_status' => 'pending'
  ]
];

// Statistics
$totalDonors = count($donors);
$activeDonors = count(array_filter($donors, function($d) { return $d['status'] === 'active'; }));
$newDonors = count(array_filter($donors, function($d) { 
  $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
  return $d['registered_date'] >= $threeMonthsAgo;
}));
$totalDonations = array_sum(array_column($donors, 'donation_count'));

// Blood type statistics
$bloodTypeStats = [];
foreach ($donors as $donor) {
  $type = $donor['blood_type'];
  if (!isset($bloodTypeStats[$type])) {
    $bloodTypeStats[$type] = 0;
  }
  $bloodTypeStats[$type]++;
}

// Location statistics
$locationStats = [];
foreach ($donors as $donor) {
  $location = $donor['location'];
  if (!isset($locationStats[$location])) {
    $locationStats[$location] = 0;
  }
  $locationStats[$location]++;
}

// Eligible for next donation (within next 30 days)
$eligibleDonors = array_filter($donors, function($d) {
  if ($d['next_eligible_date'] === 'N/A' || $d['status'] !== 'active') return false;
  $days = (strtotime($d['next_eligible_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
  return $days <= 30 && $days >= 0;
});

// Urgent need donors (rare blood types)
$rareBloodTypes = ['AB-', 'B-', 'A-', 'O-'];
$rareDonors = array_filter($donors, function($d) use ($rareBloodTypes) {
  return in_array($d['blood_type'], $rareBloodTypes) && $d['status'] === 'active';
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - Manage Donors</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #e11d48;
    }

    .sidebar {
      transition: all 0.3s ease;
    }

    .stat-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .nav-link {
      transition: all 0.2s ease;
    }

    .nav-link:hover {
      background-color: rgba(225, 29, 72, 0.1);
    }

    .nav-link.active {
      background-color: rgba(225, 29, 72, 0.15);
      color: #e11d48;
      font-weight: 600;
    }

    .donor-card {
      transition: all 0.3s ease;
    }

    .donor-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .blood-badge {
      font-weight: 700;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
    }

    .status-active { background-color: #d1fae5; color: #065f46; }
    .status-inactive { background-color: #f3f4f6; color: #374151; }
    .status-temporary_deferred { background-color: #fef3c7; color: #92400e; }
    .status-pending_verification { background-color: #e0e7ff; color: #4f46e5; }

    .health-excellent { color: #059669; }
    .health-good { color: #3b82f6; }
    .health-fair { color: #d97706; }
    .health-pending { color: #6b7280; }

    .progress-circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: conic-gradient(#e11d48 var(--progress), #f3f4f6 0deg);
      position: relative;
    }

    .progress-circle-inner {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      background: white;
      display: flex;
      align-items: center;
      justify-content: center;
      position: absolute;
    }

    .tab-button {
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .tab-button.active {
      background-color: #e11d48;
      color: white;
    }

    .tab-button:not(.active):hover {
      background-color: #f3f4f6;
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Sidebar -->
  <div class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg">
    <div class="p-6">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
          <i class="fas fa-tint text-red-600"></i>
        </div>
        <div>
          <h1 class="text-xl font-bold text-gray-900">BloodSync</h1>
          <p class="text-xs text-gray-500">Admin Dashboard</p>
        </div>
      </div>
    </div>

    <nav class="px-4 space-y-1">
      <a href="admin.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-tachometer-alt w-6 text-center mr-3"></i>
        <span>Dashboard</span>
      </a>
      <a href="manage_donors.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
        <i class="fas fa-users w-6 text-center mr-3"></i>
        <span>Manage Donors</span>
      </a>
      <a href="hospitals.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-hospital w-6 text-center mr-3"></i>
        <span>Hospitals</span>
      </a>
      <a href="blood_requests.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-clipboard-list w-6 text-center mr-3"></i>
        <span>Blood Requests</span>
      </a>
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-box w-6 text-center mr-3"></i>
        <span>Inventory</span>
      </a>
      <a href="appointments.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-calendar-check w-6 text-center mr-3"></i>
        <span>Appointments</span>
      </a>
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-chart-bar w-6 text-center mr-3"></i>
        <span>Reports</span>
      </a>
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-cog w-6 text-center mr-3"></i>
        <span>System Settings</span>
      </a>
      <a href="user_management.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-user-shield w-6 text-center mr-3"></i>
        <span>User Management</span>
      </a>
    </nav>

    <div class="absolute bottom-0 w-full p-4">
      <a href="logout.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-red-50">
        <i class="fas fa-sign-out-alt w-6 text-center mr-3"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="ml-64">
    <!-- Top Navigation -->
    <header class="bg-white shadow-sm">
      <div class="flex items-center justify-between px-8 py-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Manage Donors</h1>
          <p class="text-gray-600">View and manage all registered blood donors</p>
        </div>

        <div class="flex items-center space-x-4">
          <button class="relative p-2 text-gray-600 hover:text-red-600">
            <i class="fas fa-bell text-xl"></i>
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-600 rounded-full"></span>
          </button>

          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
              <i class="fas fa-user text-red-600"></i>
            </div>
            <div>
              <p class="font-medium text-gray-900">Admin User</p>
              <p class="text-sm text-gray-500">System Administrator</p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="p-8">
      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Donors Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Donors</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalDonors; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 18%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last month</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
              <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Active Donors Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Active Donors</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $activeDonors; ?></h3>
              <p class="text-gray-400 text-sm mt-2"><?php echo round(($activeDonors/$totalDonors)*100); ?>% of total</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Total Donations Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Donations</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalDonations; ?></h3>
              <p class="text-gray-400 text-sm mt-2">From all donors</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
              <i class="fas fa-tint text-red-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- New Donors Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">New Donors</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $newDonors; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Registered in last 3 months</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center">
              <i class="fas fa-user-plus text-purple-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="bg-white rounded-xl shadow mb-6 p-4">
        <div class="flex space-x-2 overflow-x-auto">
          <div class="tab-button active" onclick="switchTab('all')">
            <i class="fas fa-list mr-2"></i> All Donors
          </div>
          <div class="tab-button" onclick="switchTab('active')">
            <i class="fas fa-check-circle mr-2"></i> Active
          </div>
          <div class="tab-button" onclick="switchTab('eligible')">
            <i class="fas fa-calendar-check mr-2"></i> Eligible Soon
          </div>
          <div class="tab-button" onclick="switchTab('rare')">
            <i class="fas fa-star mr-2"></i> Rare Blood Types
          </div>
          <div class="tab-button" onclick="switchTab('new')">
            <i class="fas fa-user-plus mr-2"></i> New Donors
          </div>
          <div class="tab-button" onclick="switchTab('inactive')">
            <i class="fas fa-user-slash mr-2"></i> Inactive
          </div>
        </div>
      </div>

      <!-- Blood Type Distribution -->
      <div class="bg-white rounded-xl shadow mb-6 p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Blood Type Distribution</h3>
          <button onclick="exportBloodTypeReport()" class="text-sm text-red-600 hover:text-red-700">
            <i class="fas fa-download mr-1"></i> Export Report
          </button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
          <?php 
          $bloodColors = [
            'A+' => 'bg-red-500',
            'A-' => 'bg-red-300',
            'B+' => 'bg-blue-500',
            'B-' => 'bg-blue-300',
            'O+' => 'bg-yellow-500',
            'O-' => 'bg-yellow-300',
            'AB+' => 'bg-green-500',
            'AB-' => 'bg-green-300'
          ];
          
          ksort($bloodTypeStats);
          foreach ($bloodTypeStats as $type => $count): 
            $percentage = round(($count/$totalDonors)*100);
          ?>
          <div class="text-center">
            <div class="relative mx-auto mb-2" style="width: 80px; height: 80px;">
              <div class="progress-circle" style="--progress: <?php echo $percentage * 3.6; ?>deg">
                <div class="progress-circle-inner">
                  <span class="font-bold text-gray-900"><?php echo $percentage; ?>%</span>
                </div>
              </div>
            </div>
            <div class="blood-badge <?php echo $bloodColors[$type]; ?> text-white inline-block mb-1">
              <?php echo $type; ?>
            </div>
            <p class="text-sm text-gray-600"><?php echo $count; ?> donors</p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Location Distribution -->
      <div class="bg-white rounded-xl shadow mb-6 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Donors by Location</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <?php 
          arsort($locationStats);
          foreach ($locationStats as $location => $count): 
            $percentage = round(($count/$totalDonors)*100);
          ?>
          <div class="text-center p-3 bg-gray-50 rounded-lg">
            <p class="text-lg font-bold text-gray-900"><?php echo $count; ?></p>
            <p class="text-sm text-gray-700"><?php echo $location; ?></p>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
              <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1"><?php echo $percentage; ?>%</p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Search and Action Bar -->
      <div class="bg-white rounded-xl shadow mb-6 p-4">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <div class="flex flex-wrap gap-4">
            <div class="relative">
              <input type="text" placeholder="Search donors..." 
                     class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                     id="searchInput">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    id="bloodTypeFilter">
              <option value="">All Blood Types</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="O+">O+</option>
              <option value="O-">O-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
            </select>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    id="locationFilter">
              <option value="">All Locations</option>
              <?php foreach (array_keys($locationStats) as $location): ?>
              <option value="<?php echo $location; ?>"><?php echo $location; ?></option>
              <?php endforeach; ?>
            </select>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    id="statusFilter">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="temporary_deferred">Temporarily Deferred</option>
              <option value="pending_verification">Pending Verification</option>
            </select>
          </div>
          <div class="flex space-x-2">
            <button onclick="exportDonors()" 
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              <i class="fas fa-download mr-2"></i> Export
            </button>
            <button onclick="openAddDonorModal()" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
              <i class="fas fa-user-plus mr-2"></i> Add Donor
            </button>
            <button onclick="openBulkImportModal()" 
                    class="px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50">
              <i class="fas fa-upload mr-2"></i> Bulk Import
            </button>
          </div>
        </div>
      </div>

      <!-- Donors Table -->
      <div class="bg-white rounded-xl shadow overflow-hidden mb-8" id="donorsTable">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">
                  <input type="checkbox" class="rounded border-gray-300" id="selectAll">
                </th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Donor</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Blood Type</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Location</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Donations</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Last Donation</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Next Eligible</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Status</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Health</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="donorsTableBody">
              <?php foreach ($donors as $donor): ?>
              <tr class="hover:bg-gray-50 transition-colors donor-row" 
                  data-status="<?php echo $donor['status']; ?>"
                  data-blood-type="<?php echo $donor['blood_type']; ?>"
                  data-location="<?php echo $donor['location']; ?>"
                  data-name="<?php echo strtolower($donor['name']); ?>"
                  data-email="<?php echo strtolower($donor['email']); ?>">
                <td class="py-4 px-4">
                  <input type="checkbox" class="donor-checkbox rounded border-gray-300" value="<?php echo $donor['id']; ?>">
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                      <span class="font-semibold text-red-600">
                        <?php echo strtoupper(substr($donor['name'], 0, 1)); ?>
                      </span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900"><?php echo $donor['name']; ?></p>
                      <p class="text-sm text-gray-500"><?php echo $donor['email']; ?></p>
                      <p class="text-xs text-gray-400"><?php echo $donor['phone']; ?></p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <span class="blood-badge <?php echo $bloodColors[$donor['blood_type']]; ?> text-white font-bold">
                    <?php echo $donor['blood_type']; ?>
                  </span>
                </td>
                <td class="py-4 px-4">
                  <p class="text-gray-700"><?php echo $donor['location']; ?></p>
                  <p class="text-sm text-gray-500"><?php echo $donor['age']; ?> yrs, <?php echo $donor['gender']; ?></p>
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center">
                    <span class="text-lg font-bold text-red-600"><?php echo $donor['donation_count']; ?></span>
                    <div class="ml-2">
                      <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" 
                             style="width: <?php echo min(100, ($donor['donation_count']/20)*100); ?>%"></div>
                      </div>
                      <p class="text-xs text-gray-500 mt-1">
                        <?php 
                        if ($donor['donation_count'] === 0) echo 'New donor';
                        elseif ($donor['donation_count'] <= 3) echo 'Regular';
                        elseif ($donor['donation_count'] <= 10) echo 'Frequent';
                        else echo 'Lifetime';
                        ?>
                      </p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <?php if ($donor['last_donation'] !== 'N/A'): ?>
                  <p class="text-gray-700"><?php echo date('M d, Y', strtotime($donor['last_donation'])); ?></p>
                  <p class="text-sm text-gray-500">
                    <?php 
                    $days = (strtotime(date('Y-m-d')) - strtotime($donor['last_donation'])) / (60 * 60 * 24);
                    echo floor($days) . " days ago";
                    ?>
                  </p>
                  <?php else: ?>
                  <p class="text-gray-400">Never donated</p>
                  <?php endif; ?>
                </td>
                <td class="py-4 px-4">
                  <?php if ($donor['next_eligible_date'] !== 'N/A'): 
                    $days = (strtotime($donor['next_eligible_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                  ?>
                  <p class="font-medium <?php echo $days <= 0 ? 'text-green-600' : ($days <= 7 ? 'text-yellow-600' : 'text-gray-700'); ?>">
                    <?php echo date('M d, Y', strtotime($donor['next_eligible_date'])); ?>
                  </p>
                  <p class="text-sm <?php echo $days <= 0 ? 'text-green-500' : ($days <= 7 ? 'text-yellow-500' : 'text-gray-500'); ?>">
                    <?php 
                    if ($days <= 0) echo 'Eligible now';
                    elseif ($days <= 7) echo 'In ' . floor($days) . ' days';
                    else echo 'In ' . floor($days) . ' days';
                    ?>
                  </p>
                  <?php else: ?>
                  <p class="text-gray-400">N/A</p>
                  <?php endif; ?>
                </td>
                <td class="py-4 px-4">
                  <span class="status-badge status-<?php echo $donor['status']; ?>">
                    <?php 
                    $statusNames = [
                      'active' => 'Active',
                      'inactive' => 'Inactive',
                      'temporary_deferred' => 'Deferred',
                      'pending_verification' => 'Pending'
                    ];
                    echo $statusNames[$donor['status']];
                    ?>
                  </span>
                  <?php if (isset($donor['deferral_reason'])): ?>
                  <p class="text-xs text-gray-500 mt-1"><?php echo $donor['deferral_reason']; ?></p>
                  <?php endif; ?>
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center">
                    <i class="fas fa-heartbeat mr-2 health-<?php echo $donor['health_status']; ?>"></i>
                    <span class="capitalize"><?php echo $donor['health_status']; ?></span>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button onclick="viewDonor(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editDonor(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="scheduleAppointment(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg">
                      <i class="fas fa-calendar-plus"></i>
                    </button>
                    <button onclick="sendMessage(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg">
                      <i class="fas fa-envelope"></i>
                    </button>
                    <?php if ($donor['status'] === 'active'): ?>
                    <button onclick="deferDonor(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg">
                      <i class="fas fa-pause"></i>
                    </button>
                    <?php else: ?>
                    <button onclick="activateDonor(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                      <i class="fas fa-play"></i>
                    </button>
                    <?php endif; ?>
                    <button onclick="deleteDonor(<?php echo $donor['id']; ?>)" 
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Bulk Actions -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500" id="selectedCount">
              0 donors selected
            </div>
            <div class="flex space-x-2">
              <button onclick="bulkSendMessage()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-envelope mr-2"></i> Send Message
              </button>
              <button onclick="bulkScheduleAppointment()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-calendar-plus mr-2"></i> Schedule
              </button>
              <button onclick="bulkExport()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-download mr-2"></i> Export Selected
              </button>
              <button onclick="bulkDeactivate()" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-user-slash mr-2"></i> Deactivate Selected
              </button>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
              Showing 1 to 12 of <?php echo $totalDonors; ?> donors
            </div>
            <div class="flex space-x-2">
              <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
              </button>
              <button class="px-3 py-1 bg-red-600 text-white rounded-lg">1</button>
              <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">2</button>
              <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">3</button>
              <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Special Sections -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Eligible Soon -->
        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Eligible for Donation Soon</h3>
            <a href="#" class="text-red-600 text-sm font-medium hover:text-red-700">View All</a>
          </div>
          <div class="space-y-3">
            <?php 
            $eligibleCount = 0;
            foreach ($eligibleDonors as $donor):
              if ($eligibleCount >= 4) break;
              $days = (strtotime($donor['next_eligible_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            ?>
            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
              <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                  <span class="font-semibold text-green-600"><?php echo strtoupper(substr($donor['name'], 0, 1)); ?></span>
                </div>
                <div>
                  <p class="font-medium text-gray-900"><?php echo $donor['name']; ?></p>
                  <p class="text-sm text-gray-600"><?php echo $donor['blood_type']; ?> • <?php echo $donor['location']; ?></p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-green-600">
                  <?php echo $days <= 0 ? 'Ready now' : 'In ' . floor($days) . ' days'; ?>
                </p>
                <button onclick="scheduleAppointment(<?php echo $donor['id']; ?>)" 
                        class="text-xs text-red-600 hover:text-red-700">
                  Schedule →
                </button>
              </div>
            </div>
            <?php 
              $eligibleCount++;
            endforeach; 
            if ($eligibleCount === 0):
            ?>
            <div class="text-center py-4 text-gray-500">
              <i class="fas fa-calendar-times text-2xl mb-2"></i>
              <p>No donors eligible soon</p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Rare Blood Types -->
        <div class="bg-white rounded-xl shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Rare Blood Type Donors</h3>
            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
              <?php echo count($rareDonors); ?> donors
            </span>
          </div>
          <div class="space-y-3">
            <?php 
            $rareCount = 0;
            foreach ($rareDonors as $donor):
              if ($rareCount >= 4) break;
            ?>
            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
              <div class="flex items-center">
                <div class="w-8 h-8 rounded-full <?php echo $bloodColors[$donor['blood_type']]; ?> flex items-center justify-center mr-3">
                  <span class="font-bold text-white"><?php echo $donor['blood_type']; ?></span>
                </div>
                <div>
                  <p class="font-medium text-gray-900"><?php echo $donor['name']; ?></p>
                  <p class="text-sm text-gray-600"><?php echo $donor['location']; ?> • Last: <?php echo date('M d', strtotime($donor['last_donation'])); ?></p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900"><?php echo $donor['donation_count']; ?> donations</p>
                <button onclick="viewDonor(<?php echo $donor['id']; ?>)" 
                        class="text-xs text-red-600 hover:text-red-700">
                  View Details →
                </button>
              </div>
            </div>
            <?php 
              $rareCount++;
            endforeach; 
            if ($rareCount === 0):
            ?>
            <div class="text-center py-4 text-gray-500">
              <i class="fas fa-star text-2xl mb-2"></i>
              <p>No rare blood type donors</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Donor Achievement Stats -->
      <div class="bg-white rounded-xl shadow p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Donors</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <?php 
          // Get top donors by donation count
          usort($donors, function($a, $b) {
            return $b['donation_count'] - $a['donation_count'];
          });
          
          foreach (array_slice($donors, 0, 3) as $topDonor):
          ?>
          <div class="donor-card bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center mb-3">
              <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-3">
                <span class="font-bold text-red-600 text-lg">
                  <?php echo strtoupper(substr($topDonor['name'], 0, 1)); ?>
                </span>
              </div>
              <div>
                <h4 class="font-bold text-gray-900"><?php echo $topDonor['name']; ?></h4>
                <p class="text-sm text-gray-600"><?php echo $topDonor['location']; ?></p>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Blood Type:</span>
                <span class="font-bold <?php echo $bloodColors[$topDonor['blood_type']]; ?> text-white px-2 rounded">
                  <?php echo $topDonor['blood_type']; ?>
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Total Donations:</span>
                <span class="font-bold text-red-600"><?php echo $topDonor['donation_count']; ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Last Donation:</span>
                <span class="font-medium"><?php echo date('M d, Y', strtotime($topDonor['last_donation'])); ?></span>
              </div>
            </div>
            <div class="mt-4">
              <button onclick="viewDonor(<?php echo $topDonor['id']; ?>)" 
                      class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                View Profile
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Donor Modal -->
  <div id="addDonorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200 sticky top-0 bg-white">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Add New Donor</h3>
          <button onclick="closeAddDonorModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <form id="donorForm">
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Personal Information -->
              <div class="md:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h4>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                <input type="text" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                <input type="email" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                <input type="tel" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                <input type="date" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type *</label>
                <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                  <option value="">Select Blood Type</option>
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                </select>
              </div>
            </div>

            <!-- Address Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h4>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1</label>
                <input type="text" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                <input type="text" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                <input type="text" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">District *</label>
                <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                  <option value="">Select District</option>
                  <option value="Colombo">Colombo</option>
                  <option value="Kandy">Kandy</option>
                  <option value="Galle">Galle</option>
                  <option value="Jaffna">Jaffna</option>
                  <option value="Kurunegala">Kurunegala</option>
                  <option value="Badulla">Badulla</option>
                  <option value="Ratnapura">Ratnapura</option>
                  <option value="Anuradhapura">Anuradhapura</option>
                </select>
              </div>
            </div>

            <!-- Medical Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Medical Information</h4>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Health Status</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                  <option value="excellent">Excellent</option>
                  <option value="good">Good</option>
                  <option value="fair">Fair</option>
                  <option value="poor">Poor</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                <input type="number" min="45" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Medical Conditions (if any)</label>
                <textarea rows="2" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                          placeholder="List any medical conditions, allergies, or medications..."></textarea>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Donation History</label>
                <div class="space-y-2">
                  <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-700">First-time donor</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="ml-2 text-sm text-gray-700">Previous donation experience</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Emergency Contact -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Emergency Contact</h4>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact Name</label>
                <input type="text" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact Phone</label>
                <input type="tel" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
        <button onclick="closeAddDonorModal()" 
                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Cancel
        </button>
        <button onclick="saveDonor()" 
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Register Donor
        </button>
      </div>
    </div>
  </div>

  <script>
    // Tab Switching
    let currentTab = 'all';
    
    function switchTab(tab) {
      currentTab = tab;
      
      // Update tab buttons
      document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
      });
      event.target.classList.add('active');
      
      // Filter donors based on tab
      filterDonors();
    }

    // Filter donors based on search and filters
    function filterDonors() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const bloodType = document.getElementById('bloodTypeFilter').value;
      const location = document.getElementById('locationFilter').value;
      const status = document.getElementById('statusFilter').value;
      
      document.querySelectorAll('.donor-row').forEach(row => {
        const name = row.getAttribute('data-name');
        const email = row.getAttribute('data-email');
        const donorBloodType = row.getAttribute('data-blood-type');
        const donorLocation = row.getAttribute('data-location');
        const donorStatus = row.getAttribute('data-status');
        
        // Apply tab filter
        let tabMatch = true;
        switch(currentTab) {
          case 'active':
            tabMatch = donorStatus === 'active';
            break;
          case 'eligible':
            // Logic for eligible donors (next eligible within 30 days)
            const eligibleDate = row.querySelector('td:nth-child(7) p').textContent;
            tabMatch = eligibleDate !== 'N/A' && !eligibleDate.includes('In 3');
            break;
          case 'rare':
            const rareTypes = ['AB-', 'B-', 'A-', 'O-'];
            tabMatch = rareTypes.includes(donorBloodType) && donorStatus === 'active';
            break;
          case 'new':
            // Logic for new donors (registered within last 3 months)
            const registeredCell = row.querySelector('td:nth-child(1) .text-xs');
            tabMatch = registeredCell && registeredCell.textContent.includes('New');
            break;
          case 'inactive':
            tabMatch = donorStatus === 'inactive' || donorStatus === 'temporary_deferred';
            break;
        }
        
        const matchesSearch = !searchTerm || 
                             name.includes(searchTerm) || 
                             email.includes(searchTerm);
        const matchesBloodType = !bloodType || donorBloodType === bloodType;
        const matchesLocation = !location || donorLocation === location;
        const matchesStatus = !status || donorStatus === status;
        
        row.style.display = (tabMatch && matchesSearch && matchesBloodType && matchesLocation && matchesStatus) ? '' : 'none';
      });
      
      updateSelectedCount();
    }

    // Modal Functions
    function openAddDonorModal() {
      document.getElementById('addDonorModal').classList.remove('hidden');
      document.getElementById('addDonorModal').classList.add('flex');
    }

    function closeAddDonorModal() {
      document.getElementById('addDonorModal').classList.remove('flex');
      document.getElementById('addDonorModal').classList.add('hidden');
      document.getElementById('donorForm').reset();
    }

    function openBulkImportModal() {
      alert('Bulk import modal would open here');
      // In real app, open modal for CSV upload
    }

    // Donor Action Functions
    function viewDonor(donorId) {
      alert(`Viewing donor profile for ID: ${donorId}`);
      // In real app, redirect to donor profile page or show modal
    }

    function editDonor(donorId) {
      alert(`Editing donor with ID: ${donorId}`);
      // In real app, open edit modal with pre-filled data
    }

    function scheduleAppointment(donorId) {
      alert(`Scheduling appointment for donor ID: ${donorId}`);
      // In real app, redirect to appointment scheduling page
    }

    function sendMessage(donorId) {
      const message = prompt('Enter message to send to donor:');
      if (message) {
        alert(`Message sent to donor ${donorId}: ${message}`);
        // In real app, send SMS/email
      }
    }

    function deferDonor(donorId) {
      const reason = prompt('Enter reason for deferral:');
      if (reason) {
        alert(`Donor ${donorId} temporarily deferred: ${reason}`);
        // In real app, update donor status via AJAX
      }
    }

    function activateDonor(donorId) {
      if (confirm('Activate this donor?')) {
        alert(`Donor ${donorId} activated`);
        // In real app, update donor status via AJAX
      }
    }

    function deleteDonor(donorId) {
      if (confirm('Are you sure you want to delete this donor?')) {
        alert(`Donor ${donorId} deleted`);
        // In real app, send AJAX request to delete
      }
    }

    function saveDonor() {
      alert('Donor registered successfully!');
      closeAddDonorModal();
      // In real app, send form data via AJAX
    }

    // Bulk Actions
    function updateSelectedCount() {
      const checkboxes = document.querySelectorAll('.donor-checkbox:checked');
      document.getElementById('selectedCount').textContent = `${checkboxes.length} donors selected`;
    }

    function bulkSendMessage() {
      const selected = getSelectedDonorIds();
      if (selected.length === 0) {
        alert('Please select donors first');
        return;
      }
      const message = prompt(`Enter message to send to ${selected.length} donors:`);
      if (message) {
        alert(`Message sent to donors: ${selected.join(', ')}`);
        // In real app, send bulk messages
      }
    }

    function bulkScheduleAppointment() {
      const selected = getSelectedDonorIds();
      if (selected.length === 0) {
        alert('Please select donors first');
        return;
      }
      alert(`Scheduling appointments for donors: ${selected.join(', ')}`);
      // In real app, redirect to bulk scheduling
    }

    function bulkExport() {
      const selected = getSelectedDonorIds();
      if (selected.length === 0) {
        alert('Please select donors first');
        return;
      }
      alert(`Exporting ${selected.length} donors to CSV...`);
      // In real app, generate and download CSV
    }

    function bulkDeactivate() {
      const selected = getSelectedDonorIds();
      if (selected.length === 0) {
        alert('Please select donors first');
        return;
      }
      if (confirm(`Deactivate ${selected.length} selected donors?`)) {
        alert(`Deactivating donors: ${selected.join(', ')}`);
        // In real app, send bulk update request
      }
    }

    function getSelectedDonorIds() {
      const checkboxes = document.querySelectorAll('.donor-checkbox:checked');
      return Array.from(checkboxes).map(cb => cb.value);
    }

    function exportDonors() {
      alert('Exporting all donors to CSV...');
      // In real app, generate and download CSV file
    }

    function exportBloodTypeReport() {
      alert('Exporting blood type distribution report...');
      // In real app, generate blood type report
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
      // Select all checkbox
      document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.donor-checkbox');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateSelectedCount();
      });

      // Individual checkbox changes
      document.querySelectorAll('.donor-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
      });

      // Search and filter inputs
      const searchInput = document.getElementById('searchInput');
      const bloodTypeFilter = document.getElementById('bloodTypeFilter');
      const locationFilter = document.getElementById('locationFilter');
      const statusFilter = document.getElementById('statusFilter');

      searchInput.addEventListener('input', filterDonors);
      bloodTypeFilter.addEventListener('change', filterDonors);
      locationFilter.addEventListener('change', filterDonors);
      statusFilter.addEventListener('change', filterDonors);
    });

    // Close modal when clicking outside
    document.getElementById('addDonorModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeAddDonorModal();
      }
    });
  </script>
</body>

</html>