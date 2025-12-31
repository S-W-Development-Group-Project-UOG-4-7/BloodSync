<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Blood inventory data
$inventory = [
  'A+' => [
    'total_units' => 85,
    'available_units' => 65,
    'reserved_units' => 15,
    'expiring_soon' => 5,
    'critical_level' => 20,
    'optimal_level' => 80,
    'status' => 'optimal'
  ],
  'A-' => [
    'total_units' => 25,
    'available_units' => 18,
    'reserved_units' => 5,
    'expiring_soon' => 2,
    'critical_level' => 8,
    'optimal_level' => 25,
    'status' => 'moderate'
  ],
  'B+' => [
    'total_units' => 72,
    'available_units' => 50,
    'reserved_units' => 18,
    'expiring_soon' => 4,
    'critical_level' => 18,
    'optimal_level' => 70,
    'status' => 'optimal'
  ],
  'B-' => [
    'total_units' => 18,
    'available_units' => 12,
    'reserved_units' => 4,
    'expiring_soon' => 2,
    'critical_level' => 6,
    'optimal_level' => 20,
    'status' => 'moderate'
  ],
  'O+' => [
    'total_units' => 120,
    'available_units' => 95,
    'reserved_units' => 20,
    'expiring_soon' => 5,
    'critical_level' => 30,
    'optimal_level' => 100,
    'status' => 'optimal'
  ],
  'O-' => [
    'total_units' => 32,
    'available_units' => 22,
    'reserved_units' => 8,
    'expiring_soon' => 2,
    'critical_level' => 10,
    'optimal_level' => 30,
    'status' => 'low'
  ],
  'AB+' => [
    'total_units' => 15,
    'available_units' => 8,
    'reserved_units' => 5,
    'expiring_soon' => 2,
    'critical_level' => 5,
    'optimal_level' => 15,
    'status' => 'critical'
  ],
  'AB-' => [
    'total_units' => 6,
    'available_units' => 2,
    'reserved_units' => 3,
    'expiring_soon' => 1,
    'critical_level' => 3,
    'optimal_level' => 8,
    'status' => 'critical'
  ],
  'Platelets' => [
    'total_units' => 45,
    'available_units' => 32,
    'reserved_units' => 10,
    'expiring_soon' => 3,
    'critical_level' => 15,
    'optimal_level' => 40,
    'status' => 'optimal'
  ],
  'Plasma' => [
    'total_units' => 38,
    'available_units' => 25,
    'reserved_units' => 10,
    'expiring_soon' => 3,
    'critical_level' => 12,
    'optimal_level' => 35,
    'status' => 'moderate'
  ],
  'Cryoprecipitate' => [
    'total_units' => 22,
    'available_units' => 15,
    'reserved_units' => 5,
    'expiring_soon' => 2,
    'critical_level' => 8,
    'optimal_level' => 20,
    'status' => 'moderate'
  ]
];

// Statistics
$totalBloodUnits = 0;
$availableBloodUnits = 0;
$reservedBloodUnits = 0;
$expiringSoonUnits = 0;
$criticalItems = 0;

foreach ($inventory as $item) {
  $totalBloodUnits += $item['total_units'];
  $availableBloodUnits += $item['available_units'];
  $reservedBloodUnits += $item['reserved_units'];
  $expiringSoonUnits += $item['expiring_soon'];
  if ($item['status'] === 'critical') {
    $criticalItems++;
  }
}

// Recent inventory transactions
$transactions = [
  [
    'id' => 'TX001',
    'type' => 'donation',
    'blood_type' => 'O+',
    'units' => 2,
    'from_to' => 'John Doe',
    'date' => '2023-10-20 09:30:00',
    'status' => 'completed',
    'reference' => 'DON-20231020-001'
  ],
  [
    'id' => 'TX002',
    'type' => 'request',
    'blood_type' => 'A+',
    'units' => 3,
    'from_to' => 'General Hospital',
    'date' => '2023-10-20 11:15:00',
    'status' => 'completed',
    'reference' => 'REQ-20231020-002'
  ],
  [
    'id' => 'TX003',
    'type' => 'transfer',
    'blood_type' => 'B-',
    'units' => 2,
    'from_to' => 'Kandy Hospital → Colombo',
    'date' => '2023-10-19 14:45:00',
    'status' => 'pending',
    'reference' => 'TRF-20231019-003'
  ],
  [
    'id' => 'TX004',
    'type' => 'expired',
    'blood_type' => 'AB+',
    'units' => 1,
    'from_to' => 'Expired Stock',
    'date' => '2023-10-19 16:20:00',
    'status' => 'completed',
    'reference' => 'EXP-20231019-004'
  ],
  [
    'id' => 'TX005',
    'type' => 'donation',
    'blood_type' => 'O-',
    'units' => 1,
    'from_to' => 'Jane Smith',
    'date' => '2023-10-18 10:00:00',
    'status' => 'completed',
    'reference' => 'DON-20231018-005'
  ],
  [
    'id' => 'TX006',
    'type' => 'request',
    'blood_type' => 'B+',
    'units' => 4,
    'from_to' => 'Children\'s Hospital',
    'date' => '2023-10-18 13:30:00',
    'status' => 'completed',
    'reference' => 'REQ-20231018-006'
  ],
  [
    'id' => 'TX007',
    'type' => 'adjustment',
    'blood_type' => 'Platelets',
    'units' => -1,
    'from_to' => 'Quality Control',
    'date' => '2023-10-17 15:10:00',
    'status' => 'completed',
    'reference' => 'ADJ-20231017-007'
  ],
  [
    'id' => 'TX008',
    'type' => 'donation',
    'blood_type' => 'A-',
    'units' => 2,
    'from_to' => 'Kamal Perera',
    'date' => '2023-10-17 09:45:00',
    'status' => 'completed',
    'reference' => 'DON-20231017-008'
  ]
];

// Expiring soon items
$expiringItems = [
  [
    'blood_type' => 'A+',
    'units' => 3,
    'expiry_date' => '2023-10-25',
    'days_left' => 5,
    'location' => 'Main Storage'
  ],
  [
    'blood_type' => 'O-',
    'units' => 2,
    'expiry_date' => '2023-10-24',
    'days_left' => 4,
    'location' => 'Emergency Storage'
  ],
  [
    'blood_type' => 'AB+',
    'units' => 2,
    'expiry_date' => '2023-10-23',
    'days_left' => 3,
    'location' => 'Main Storage'
  ],
  [
    'blood_type' => 'Platelets',
    'units' => 3,
    'expiry_date' => '2023-10-22',
    'days_left' => 2,
    'location' => 'Platelet Storage'
  ],
  [
    'blood_type' => 'B-',
    'units' => 1,
    'expiry_date' => '2023-10-21',
    'days_left' => 1,
    'location' => 'Main Storage'
  ]
];

// Hospital stock levels
$hospitalStock = [
  [
    'hospital' => 'Colombo General Hospital',
    'blood_types' => ['A+' => 25, 'B+' => 18, 'O+' => 40, 'AB+' => 5],
    'status' => 'optimal',
    'last_updated' => '2023-10-20'
  ],
  [
    'hospital' => 'Kandy Teaching Hospital',
    'blood_types' => ['A+' => 15, 'B+' => 10, 'O+' => 25, 'AB+' => 2],
    'status' => 'moderate',
    'last_updated' => '2023-10-19'
  ],
  [
    'hospital' => 'Galle General Hospital',
    'blood_types' => ['A+' => 8, 'B+' => 6, 'O+' => 15, 'AB+' => 1],
    'status' => 'low',
    'last_updated' => '2023-10-20'
  ],
  [
    'hospital' => 'Jaffna Teaching Hospital',
    'blood_types' => ['A+' => 12, 'B+' => 8, 'O+' => 20, 'AB+' => 2],
    'status' => 'moderate',
    'last_updated' => '2023-10-18'
  ]
];

// Blood type colors
$bloodColors = [
  'A+' => 'bg-red-500',
  'A-' => 'bg-red-300',
  'B+' => 'bg-blue-500',
  'B-' => 'bg-blue-300',
  'O+' => 'bg-yellow-500',
  'O-' => 'bg-yellow-300',
  'AB+' => 'bg-green-500',
  'AB-' => 'bg-green-300',
  'Platelets' => 'bg-purple-500',
  'Plasma' => 'bg-pink-500',
  'Cryoprecipitate' => 'bg-indigo-500'
];

// Status colors
$statusColors = [
  'optimal' => 'bg-green-100 text-green-800',
  'moderate' => 'bg-yellow-100 text-yellow-800',
  'low' => 'bg-orange-100 text-orange-800',
  'critical' => 'bg-red-100 text-red-800'
];

// Transaction type colors
$transactionColors = [
  'donation' => 'bg-green-100 text-green-800',
  'request' => 'bg-blue-100 text-blue-800',
  'transfer' => 'bg-purple-100 text-purple-800',
  'expired' => 'bg-gray-100 text-gray-800',
  'adjustment' => 'bg-yellow-100 text-yellow-800'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - Blood Inventory Management</title>
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

    .inventory-card {
      transition: all 0.3s ease;
    }

    .inventory-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .transaction-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .blood-badge {
      font-weight: 700;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      color: white;
    }

    .progress-bar {
      height: 8px;
      border-radius: 4px;
      background-color: #e5e7eb;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      border-radius: 4px;
      transition: width 0.3s ease;
    }

    .progress-optimal {
      background-color: #10b981;
    }

    .progress-moderate {
      background-color: #f59e0b;
    }

    .progress-low {
      background-color: #f97316;
    }

    .progress-critical {
      background-color: #ef4444;
    }

    .gauge-container {
      width: 120px;
      height: 120px;
      position: relative;
    }

    .gauge-background {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: conic-gradient(#ef4444 0% 25%,
          #f97316 25% 50%,
          #f59e0b 50% 75%,
          #10b981 75% 100%);
      mask: radial-gradient(transparent 50%, white 51%);
    }

    .gauge-needle {
      position: absolute;
      top: 10%;
      left: 50%;
      width: 3px;
      height: 40%;
      background-color: #374151;
      transform-origin: bottom center;
      transition: transform 0.5s ease;
    }

    .gauge-value {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 1.5rem;
      font-weight: bold;
      color: #374151;
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
      <a href="manage_donors.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
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
      <a href="inventory.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
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
          <h1 class="text-2xl font-bold text-gray-900">Blood Inventory Management</h1>
          <p class="text-gray-600">Track and manage blood stock across all locations</p>
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
        <!-- Total Blood Units Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Blood Units</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalBloodUnits; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 12%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last week</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
              <i class="fas fa-tint text-red-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Available Units Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Available Units</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $availableBloodUnits; ?></h3>
              <p class="text-gray-400 text-sm mt-2">
                <?php echo round(($availableBloodUnits / $totalBloodUnits) * 100); ?>% of total
              </p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Critical Items Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Critical Items</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $criticalItems; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-red-500 text-sm flex items-center">
                  <i class="fas fa-exclamation-triangle mr-1"></i> Needs attention
                </span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
              <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Expiring Soon Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Expiring Soon</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $expiringSoonUnits; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Within 7 days</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-yellow-50 flex items-center justify-center">
              <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Inventory Dashboard -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Blood Type Inventory -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Blood Type Inventory</h3>
              <div class="flex space-x-2">
                <button onclick="exportInventoryReport()"
                  class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                  <i class="fas fa-download mr-2"></i> Export
                </button>
                <button onclick="openAdjustStockModal()"
                  class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                  <i class="fas fa-edit mr-2"></i> Adjust Stock
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($inventory as $type => $data):
                $percentage = round(($data['available_units'] / $data['optimal_level']) * 100);
                $progressClass = 'progress-' . $data['status'];
                ?>
                <div class="inventory-card bg-white border border-gray-200 rounded-lg p-4">
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <span class="blood-badge <?php echo $bloodColors[$type]; ?> mb-2 inline-block">
                        <?php echo $type; ?>
                      </span>
                      <p class="text-sm text-gray-600">Blood Type</p>
                    </div>
                    <span class="status-badge <?php echo $statusColors[$data['status']]; ?>">
                      <?php echo ucfirst($data['status']); ?>
                    </span>
                  </div>

                  <div class="space-y-3">
                    <div>
                      <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-600">Available</span>
                        <span class="text-sm font-medium text-gray-900">
                          <?php echo $data['available_units']; ?> / <?php echo $data['optimal_level']; ?>
                        </span>
                      </div>
                      <div class="progress-bar">
                        <div class="progress-fill <?php echo $progressClass; ?>"
                          style="width: <?php echo min($percentage, 100); ?>%"></div>
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm">
                      <div class="text-center p-2 bg-gray-50 rounded">
                        <p class="font-bold text-gray-900"><?php echo $data['total_units']; ?></p>
                        <p class="text-gray-600">Total</p>
                      </div>
                      <div class="text-center p-2 bg-gray-50 rounded">
                        <p class="font-bold text-gray-900"><?php echo $data['reserved_units']; ?></p>
                        <p class="text-gray-600">Reserved</p>
                      </div>
                      <div class="text-center p-2 bg-red-50 rounded">
                        <p class="font-bold text-red-600"><?php echo $data['critical_level']; ?></p>
                        <p class="text-red-600">Critical</p>
                      </div>
                      <div class="text-center p-2 bg-yellow-50 rounded">
                        <p class="font-bold text-yellow-600"><?php echo $data['expiring_soon']; ?></p>
                        <p class="text-yellow-600">Expiring</p>
                      </div>
                    </div>

                    <div class="flex space-x-2">
                      <button onclick="viewInventoryDetails('<?php echo $type; ?>')"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                        Details
                      </button>
                      <button onclick="orderBlood('<?php echo $type; ?>')"
                        class="flex-1 px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Order
                      </button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Recent Transactions -->
          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
              <a href="#" class="text-red-600 text-sm font-medium hover:text-red-700">View All</a>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Transaction ID</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Type</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Blood Type</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Units</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">From/To</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Date & Time</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach ($transactions as $transaction): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                      <td class="py-4 px-4">
                        <span class="font-mono text-sm text-gray-900"><?php echo $transaction['id']; ?></span>
                        <p class="text-xs text-gray-500"><?php echo $transaction['reference']; ?></p>
                      </td>
                      <td class="py-4 px-4">
                        <span class="transaction-badge <?php echo $transactionColors[$transaction['type']]; ?>">
                          <?php echo ucfirst($transaction['type']); ?>
                        </span>
                      </td>
                      <td class="py-4 px-4">
                        <span class="blood-badge <?php echo $bloodColors[$transaction['blood_type']]; ?>">
                          <?php echo $transaction['blood_type']; ?>
                        </span>
                      </td>
                      <td class="py-4 px-4">
                        <span
                          class="font-bold <?php echo $transaction['units'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                          <?php echo $transaction['units'] > 0 ? '+' : ''; ?>  <?php echo $transaction['units']; ?>
                        </span>
                        <span class="text-sm text-gray-500"> units</span>
                      </td>
                      <td class="py-4 px-4">
                        <p class="text-gray-700"><?php echo $transaction['from_to']; ?></p>
                      </td>
                      <td class="py-4 px-4">
                        <p class="text-gray-700"><?php echo date('M d', strtotime($transaction['date'])); ?></p>
                        <p class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($transaction['date'])); ?></p>
                      </td>
                      <td class="py-4 px-4">
                        <span
                          class="status-badge <?php echo $transaction['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                          <?php echo ucfirst($transaction['status']); ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
          <!-- Stock Overview Gauge -->
          <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Stock Level Overview</h3>
            <div class="flex flex-col items-center">
              <div class="gauge-container mb-4">
                <div class="gauge-background"></div>
                <div class="gauge-needle" id="gaugeNeedle"></div>
                <div class="gauge-value" id="gaugeValue">
                  <?php echo round(($availableBloodUnits / $totalBloodUnits) * 100); ?>%
                </div>
              </div>
              <p class="text-sm text-gray-600 text-center mb-4">
                Overall stock availability across all blood types
              </p>
              <div class="w-full space-y-2">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Critical</span>
                  <span class="text-sm font-medium text-red-600"><?php echo $criticalItems; ?> types</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Low</span>
                  <span class="text-sm font-medium text-orange-600"><?php
                  $lowCount = 0;
                  foreach ($inventory as $data) {
                    if ($data['status'] === 'low')
                      $lowCount++;
                  }
                  echo $lowCount;
                  ?> types</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Optimal</span>
                  <span class="text-sm font-medium text-green-600"><?php
                  $optimalCount = 0;
                  foreach ($inventory as $data) {
                    if ($data['status'] === 'optimal')
                      $optimalCount++;
                  }
                  echo $optimalCount;
                  ?> types</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Expiring Soon -->
          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Expiring Soon</h3>
              <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                <?php echo $expiringSoonUnits; ?> units
              </span>
            </div>
            <div class="space-y-3">
              <?php foreach ($expiringItems as $item): ?>
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                  <div class="flex items-center">
                    <div
                      class="w-8 h-8 rounded-full <?php echo $bloodColors[$item['blood_type']]; ?> flex items-center justify-center mr-3">
                      <span class="font-bold text-white text-sm"><?php echo $item['blood_type']; ?></span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900"><?php echo $item['units']; ?> units</p>
                      <p class="text-sm text-gray-600"><?php echo $item['location']; ?></p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p
                      class="text-sm font-medium <?php echo $item['days_left'] <= 3 ? 'text-red-600' : 'text-yellow-600'; ?>">
                      <?php echo $item['days_left']; ?> days
                    </p>
                    <p class="text-xs text-gray-500"><?php echo date('M d', strtotime($item['expiry_date'])); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <button onclick="viewExpiringItems()"
              class="w-full mt-4 px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50">
              View All Expiring Items
            </button>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
              <button onclick="openNewDonationModal()"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center">
                <i class="fas fa-plus-circle mr-2"></i> Record New Donation
              </button>
              <button onclick="openTransferModal()"
                class="w-full px-4 py-3 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 flex items-center justify-center">
                <i class="fas fa-exchange-alt mr-2"></i> Transfer Stock
              </button>
              <button onclick="openExpiryCheckModal()"
                class="w-full px-4 py-3 border border-yellow-600 text-yellow-600 rounded-lg hover:bg-yellow-50 flex items-center justify-center">
                <i class="fas fa-clock mr-2"></i> Check Expiry Dates
              </button>
              <button onclick="generateInventoryReport()"
                class="w-full px-4 py-3 border border-gray-600 text-gray-600 rounded-lg hover:bg-gray-50 flex items-center justify-center">
                <i class="fas fa-file-alt mr-2"></i> Generate Report
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Hospital Stock Levels -->
      <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-900">Hospital Stock Levels</h3>
          <button onclick="syncHospitalStock()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fas fa-sync-alt mr-2"></i> Sync All
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <?php foreach ($hospitalStock as $hospital): ?>
            <div class="inventory-card bg-white border border-gray-200 rounded-lg p-4">
              <div class="flex justify-between items-start mb-4">
                <h4 class="font-bold text-gray-900"><?php echo $hospital['hospital']; ?></h4>
                <span class="status-badge <?php echo $statusColors[$hospital['status']]; ?>">
                  <?php echo ucfirst($hospital['status']); ?>
                </span>
              </div>

              <div class="space-y-3">
                <?php foreach ($hospital['blood_types'] as $type => $units): ?>
                  <div>
                    <div class="flex justify-between mb-1">
                      <span class="text-sm text-gray-600"><?php echo $type; ?></span>
                      <span class="text-sm font-medium text-gray-900"><?php echo $units; ?> units</span>
                    </div>
                    <div class="progress-bar">
                      <div class="progress-fill progress-<?php echo $hospital['status']; ?>"
                        style="width: <?php echo min(($units / 50) * 100, 100); ?>%"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-500">
                    Updated: <?php echo date('M d', strtotime($hospital['last_updated'])); ?>
                  </span>
                  <button onclick="viewHospitalStock('<?php echo $hospital['hospital']; ?>')"
                    class="text-sm text-red-600 hover:text-red-700">
                    View Details →
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Critical Alerts -->
      <div class="bg-white rounded-xl shadow p-6 mb-8 border-l-4 border-red-500">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
              <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Critical Stock Alerts</h3>
              <p class="text-sm text-gray-600">Immediate action required</p>
            </div>
          </div>
          <span class="px-3 py-1 bg-red-600 text-white rounded-full text-sm font-medium">
            <?php echo $criticalItems; ?> Critical
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <?php
          $criticalCount = 0;
          foreach ($inventory as $type => $data):
            if ($data['status'] === 'critical'):
              $criticalCount++;
              ?>
              <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                <div class="flex items-center mb-2">
                  <span class="blood-badge <?php echo $bloodColors[$type]; ?> mr-2">
                    <?php echo $type; ?>
                  </span>
                  <span class="text-sm font-medium text-red-800">CRITICAL</span>
                </div>
                <p class="text-2xl font-bold text-red-600 mb-1"><?php echo $data['available_units']; ?> units</p>
                <p class="text-sm text-red-700 mb-3">
                  Only <?php echo round(($data['available_units'] / $data['critical_level']) * 100); ?>% of critical level
                </p>
                <button onclick="orderBlood('<?php echo $type; ?>')"
                  class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                  <i class="fas fa-shopping-cart mr-2"></i> Order Now
                </button>
              </div>
            <?php
            endif;
          endforeach;

          if ($criticalCount === 0):
            ?>
            <div class="md:col-span-4 text-center py-8">
              <i class="fas fa-check-circle text-3xl text-green-500 mb-3"></i>
              <p class="text-gray-700">No critical stock alerts at this time</p>
              <p class="text-sm text-gray-500 mt-1">All blood types are at acceptable levels</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Inventory Summary -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Inventory Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-3xl font-bold text-gray-900"><?php echo $totalBloodUnits; ?></p>
            <p class="text-gray-600">Total Units in System</p>
          </div>
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-3xl font-bold text-green-600"><?php echo $availableBloodUnits; ?></p>
            <p class="text-green-600">Available for Immediate Use</p>
          </div>
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-3xl font-bold text-blue-600"><?php echo count($inventory); ?></p>
            <p class="text-blue-600">Blood Types & Components</p>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Adjust Stock Modal -->
  <div id="adjustStockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Adjust Stock Level</h3>
          <button onclick="closeAdjustStockModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <form id="adjustStockForm">
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type *</label>
              <select required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">Select Blood Type</option>
                <?php foreach (array_keys($inventory) as $type): ?>
                  <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type *</label>
                <select required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                  <option value="">Select Type</option>
                  <option value="add">Add Stock</option>
                  <option value="remove">Remove Stock</option>
                  <option value="correct">Correct Inventory</option>
                  <option value="expired">Mark as Expired</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Units *</label>
                <input type="number" required min="1"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
              <select required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">Select Reason</option>
                <option value="donation">New Donation</option>
                <option value="hospital_return">Hospital Return</option>
                <option value="quality_issue">Quality Issue</option>
                <option value="inventory_correction">Inventory Correction</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                placeholder="Additional details about this adjustment..."></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
              <input type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                placeholder="e.g., DON-20231020-001">
            </div>
          </div>
        </form>
      </div>
      <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
        <button onclick="closeAdjustStockModal()"
          class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Cancel
        </button>
        <button onclick="saveStockAdjustment()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Save Adjustment
        </button>
      </div>
    </div>
  </div>

  <!-- New Donation Modal -->
  <div id="newDonationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Record New Donation</h3>
          <button onclick="closeNewDonationModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <div id="newDonationForm">
          <!-- Form will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // Initialize gauge needle position
    function updateGauge() {
      const percentage = <?php echo round(($availableBloodUnits / $totalBloodUnits) * 100); ?>;
      const gaugeNeedle = document.getElementById('gaugeNeedle');
      const gaugeValue = document.getElementById('gaugeValue');

      // Calculate rotation (0% = -90deg, 100% = 90deg)
      const rotation = -90 + (percentage * 1.8);
      gaugeNeedle.style.transform = `rotate(${rotation}deg)`;
      gaugeValue.textContent = percentage + '%';
    }

    // Modal Functions
    function openAdjustStockModal() {
      document.getElementById('adjustStockModal').classList.remove('hidden');
      document.getElementById('adjustStockModal').classList.add('flex');
    }

    function closeAdjustStockModal() {
      document.getElementById('adjustStockModal').classList.remove('flex');
      document.getElementById('adjustStockModal').classList.add('hidden');
      document.getElementById('adjustStockForm').reset();
    }

    function openNewDonationModal() {
      const formContent = `
        <div class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Donor ID *</label>
              <input type="text" required 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                     placeholder="Enter donor ID or name">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type *</label>
              <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">Select Blood Type</option>
                <?php foreach (array_keys($inventory) as $type): ?>
                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Units Donated *</label>
              <input type="number" required min="1" max="4" 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                     value="1">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Donation Date *</label>
              <input type="datetime-local" required 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                     value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Collection Center</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <option value="">Select Center</option>
              <option value="main">Main Blood Bank</option>
              <option value="mobile">Mobile Unit</option>
              <option value="hospital">Hospital Collection</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Donation Type</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <option value="whole_blood">Whole Blood</option>
              <option value="plasma">Plasma</option>
              <option value="platelets">Platelets</option>
              <option value="double_red">Double Red Cells</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea rows="3" 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      placeholder="Any special notes about this donation..."></textarea>
          </div>
          
          <div>
            <label class="flex items-center">
              <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500" checked>
              <span class="ml-2 text-sm text-gray-700">Send thank you message to donor</span>
            </label>
          </div>
        </div>
      `;

      document.getElementById('newDonationForm').innerHTML = formContent;
      document.getElementById('newDonationModal').classList.remove('hidden');
      document.getElementById('newDonationModal').classList.add('flex');
    }

    function closeNewDonationModal() {
      document.getElementById('newDonationModal').classList.remove('flex');
      document.getElementById('newDonationModal').classList.add('hidden');
    }

    // Inventory Action Functions
    function viewInventoryDetails(bloodType) {
      alert(`Viewing inventory details for ${bloodType}`);
      // In real app, show detailed modal or redirect to details page
    }

    function orderBlood(bloodType) {
      const units = prompt(`How many units of ${bloodType} do you want to order?`);
      if (units && !isNaN(units)) {
        alert(`Order placed for ${units} units of ${bloodType}`);
        // In real app, send order request via AJAX
      }
    }

    function viewExpiringItems() {
      alert('Opening expiring items report');
      // In real app, show detailed report of expiring items
    }

    function syncHospitalStock() {
      if (confirm('Sync stock levels with all hospitals?')) {
        alert('Hospital stock synchronization started');
        // In real app, send sync request via AJAX
      }
    }

    function viewHospitalStock(hospitalName) {
      alert(`Viewing stock details for ${hospitalName}`);
      // In real app, show hospital stock details
    }

    function openTransferModal() {
      alert('Opening stock transfer modal');
      // In real app, open transfer modal
    }

    function openExpiryCheckModal() {
      alert('Opening expiry check report');
      // In real app, show expiry check report
    }

    function saveStockAdjustment() {
      alert('Stock adjustment saved successfully!');
      closeAdjustStockModal();
      // In real app, send form data via AJAX
    }

    function exportInventoryReport() {
      alert('Exporting inventory report...');
      // In real app, generate and download PDF/Excel report
    }

    function generateInventoryReport() {
      alert('Generating comprehensive inventory report...');
      // In real app, generate detailed report
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function () {
      updateGauge();

      // Close modals when clicking outside
      document.getElementById('adjustStockModal').addEventListener('click', function (e) {
        if (e.target === this) closeAdjustStockModal();
      });

      document.getElementById('newDonationModal').addEventListener('click', function (e) {
        if (e.target === this) closeNewDonationModal();
      });
    });
  </script>
</body>

</html>