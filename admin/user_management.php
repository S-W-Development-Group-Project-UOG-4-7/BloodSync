<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Sample user data
$users = [
  [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'role' => 'admin',
    'status' => 'active',
    'registered_date' => '2023-01-15',
    'last_login' => '2023-10-15 14:30:00',
    'phone' => '0712345678',
    'department' => 'Administration'
  ],
  [
    'id' => 2,
    'name' => 'Jane Smith',
    'email' => 'jane.smith@hospital.lk',
    'role' => 'hospital_admin',
    'status' => 'active',
    'registered_date' => '2023-02-20',
    'last_login' => '2023-10-14 10:15:00',
    'phone' => '0723456789',
    'department' => 'Colombo General Hospital'
  ],
  [
    'id' => 3,
    'name' => 'Dr. Kamal Perera',
    'email' => 'kamal.perera@medical.lk',
    'role' => 'doctor',
    'status' => 'active',
    'registered_date' => '2023-03-10',
    'last_login' => '2023-10-13 09:45:00',
    'phone' => '0734567890',
    'department' => 'Surgery Department'
  ],
  [
    'id' => 4,
    'name' => 'Nimal Silva',
    'email' => 'nimal.silva@donor.lk',
    'role' => 'donor',
    'status' => 'active',
    'registered_date' => '2023-04-05',
    'last_login' => '2023-10-12 16:20:00',
    'phone' => '0745678901',
    'department' => 'N/A'
  ],
  [
    'id' => 5,
    'name' => 'Dr. Sampath Fernando',
    'email' => 'sampath.fernando@kandyhospital.lk',
    'role' => 'hospital_admin',
    'status' => 'active',
    'registered_date' => '2023-05-12',
    'last_login' => '2023-10-11 11:30:00',
    'phone' => '0756789012',
    'department' => 'Kandy Teaching Hospital'
  ],
  [
    'id' => 6,
    'name' => 'Robert Johnson',
    'email' => 'robert.j@example.com',
    'role' => 'donor',
    'status' => 'inactive',
    'registered_date' => '2023-06-18',
    'last_login' => '2023-09-20 08:15:00',
    'phone' => '0767890123',
    'department' => 'N/A'
  ],
  [
    'id' => 7,
    'name' => 'Dr. Anusha Gunawardena',
    'email' => 'anusha.g@gallehospital.lk',
    'role' => 'hospital_staff',
    'status' => 'active',
    'registered_date' => '2023-07-22',
    'last_login' => '2023-10-10 13:45:00',
    'phone' => '0778901234',
    'department' => 'Blood Bank'
  ],
  [
    'id' => 8,
    'name' => 'Michael Brown',
    'email' => 'michael.b@example.com',
    'role' => 'donor',
    'status' => 'suspended',
    'registered_date' => '2023-08-30',
    'last_login' => '2023-10-01 10:00:00',
    'phone' => '0789012345',
    'department' => 'N/A'
  ],
  [
    'id' => 9,
    'name' => 'Dr. Rajitha Herath',
    'email' => 'rajitha.herath@admin.lk',
    'role' => 'admin',
    'status' => 'active',
    'registered_date' => '2023-09-05',
    'last_login' => '2023-10-15 09:00:00',
    'phone' => '0790123456',
    'department' => 'System Administration'
  ],
  [
    'id' => 10,
    'name' => 'Sarah Wilson',
    'email' => 'sarah.w@donor.lk',
    'role' => 'donor',
    'status' => 'active',
    'registered_date' => '2023-09-25',
    'last_login' => '2023-10-14 15:30:00',
    'phone' => '0701234567',
    'department' => 'N/A'
  ]
];

// Statistics
$totalUsers = count($users);
$activeUsers = count(array_filter($users, function($u) { return $u['status'] === 'active'; }));
$adminUsers = count(array_filter($users, function($u) { return $u['role'] === 'admin'; }));
$donorUsers = count(array_filter($users, function($u) { return $u['role'] === 'donor'; }));

// Role statistics
$roleStats = [];
foreach ($users as $user) {
  $role = $user['role'];
  if (!isset($roleStats[$role])) {
    $roleStats[$role] = 0;
  }
  $roleStats[$role]++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - User Management</title>
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

    .user-card {
      transition: all 0.3s ease;
    }

    .user-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .role-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .role-admin { background-color: #7c3aed; color: white; }
    .role-hospital_admin { background-color: #059669; color: white; }
    .role-hospital_staff { background-color: #2563eb; color: white; }
    .role-doctor { background-color: #0891b2; color: white; }
    .role-donor { background-color: #ea580c; color: white; }

    .status-active { background-color: #d1fae5; color: #065f46; }
    .status-inactive { background-color: #f3f4f6; color: #374151; }
    .status-suspended { background-color: #fee2e2; color: #991b1b; }
    .status-pending { background-color: #fef3c7; color: #92400e; }
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
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
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
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
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
      <a href="user_management.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
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
          <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
          <p class="text-gray-600">Manage system users, roles and permissions</p>
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
        <!-- Total Users Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Users</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalUsers; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 15%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last month</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
              <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Active Users Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Active Users</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $activeUsers; ?></h3>
              <p class="text-gray-400 text-sm mt-2"><?php echo round(($activeUsers/$totalUsers)*100); ?>% of total</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Admin Users Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Admin Users</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $adminUsers; ?></h3>
              <p class="text-gray-400 text-sm mt-2">System administrators</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center">
              <i class="fas fa-user-shield text-purple-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Donor Users Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Donor Users</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $donorUsers; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Registered blood donors</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
              <i class="fas fa-heart text-orange-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- User Role Distribution -->
      <div class="bg-white rounded-xl shadow mb-6 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">User Role Distribution</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <?php 
          $roleColors = [
            'admin' => 'bg-purple-500',
            'hospital_admin' => 'bg-green-500',
            'hospital_staff' => 'bg-blue-500',
            'doctor' => 'bg-cyan-500',
            'donor' => 'bg-orange-500'
          ];
          
          $roleNames = [
            'admin' => 'Admin',
            'hospital_admin' => 'Hospital Admin',
            'hospital_staff' => 'Hospital Staff',
            'doctor' => 'Doctor',
            'donor' => 'Donor'
          ];
          
          foreach ($roleStats as $role => $count): 
          ?>
          <div class="text-center">
            <div class="w-16 h-16 rounded-full <?php echo $roleColors[$role]; ?> mx-auto flex items-center justify-center mb-2">
              <span class="text-white font-bold text-xl"><?php echo $count; ?></span>
            </div>
            <p class="text-sm font-medium text-gray-900"><?php echo $roleNames[$role]; ?></p>
            <p class="text-xs text-gray-600"><?php echo round(($count/$totalUsers)*100); ?>%</p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Filter and Action Bar -->
      <div class="bg-white rounded-xl shadow mb-6 p-4">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <div class="flex flex-wrap gap-4">
            <div class="relative">
              <input type="text" placeholder="Search users..." 
                     class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                     id="searchInput">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    id="roleFilter">
              <option value="">All Roles</option>
              <option value="admin">Admin</option>
              <option value="hospital_admin">Hospital Admin</option>
              <option value="hospital_staff">Hospital Staff</option>
              <option value="doctor">Doctor</option>
              <option value="donor">Donor</option>
            </select>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    id="statusFilter">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="pending">Pending</option>
            </select>
          </div>
          <div class="flex space-x-2">
            <button onclick="exportUsers()" 
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              <i class="fas fa-download mr-2"></i> Export
            </button>
            <button onclick="openAddUserModal()" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
              <i class="fas fa-user-plus mr-2"></i> Add User
            </button>
            <button onclick="openBulkUploadModal()" 
                    class="px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-50">
              <i class="fas fa-upload mr-2"></i> Bulk Upload
            </button>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full" id="usersTable">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">
                  <input type="checkbox" class="rounded border-gray-300" id="selectAll">
                </th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">User</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Role</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Status</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Last Login</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Registered</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($users as $user): ?>
              <tr class="hover:bg-gray-50 transition-colors user-row" 
                  data-role="<?php echo $user['role']; ?>" 
                  data-status="<?php echo $user['status']; ?>"
                  data-name="<?php echo strtolower($user['name']); ?>"
                  data-email="<?php echo strtolower($user['email']); ?>">
                <td class="py-4 px-4">
                  <input type="checkbox" class="user-checkbox rounded border-gray-300" value="<?php echo $user['id']; ?>">
                </td>
                <td class="py-4 px-4">
                  <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                      <span class="font-semibold text-red-600">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                      </span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900"><?php echo $user['name']; ?></p>
                      <p class="text-sm text-gray-500"><?php echo $user['email']; ?></p>
                      <p class="text-xs text-gray-400"><?php echo $user['phone']; ?></p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <span class="role-badge role-<?php echo $user['role']; ?>">
                    <?php 
                    $roleDisplayNames = [
                      'admin' => 'Admin',
                      'hospital_admin' => 'Hospital Admin',
                      'hospital_staff' => 'Hospital Staff',
                      'doctor' => 'Doctor',
                      'donor' => 'Donor'
                    ];
                    echo $roleDisplayNames[$user['role']];
                    ?>
                  </span>
                  <?php if ($user['department'] !== 'N/A'): ?>
                  <p class="text-xs text-gray-500 mt-1"><?php echo $user['department']; ?></p>
                  <?php endif; ?>
                </td>
                <td class="py-4 px-4">
                  <span class="status-badge status-<?php echo $user['status']; ?>">
                    <?php echo ucfirst($user['status']); ?>
                  </span>
                </td>
                <td class="py-4 px-4">
                  <p class="text-gray-700"><?php echo date('M d, Y', strtotime($user['last_login'])); ?></p>
                  <p class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($user['last_login'])); ?></p>
                </td>
                <td class="py-4 px-4">
                  <p class="text-gray-700"><?php echo date('M d, Y', strtotime($user['registered_date'])); ?></p>
                  <p class="text-sm text-gray-500">
                    <?php 
                    $days = (strtotime(date('Y-m-d')) - strtotime($user['registered_date'])) / (60 * 60 * 24);
                    echo floor($days) . " days ago";
                    ?>
                  </p>
                </td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button onclick="viewUser(<?php echo $user['id']; ?>)" 
                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editUser(<?php echo $user['id']; ?>)" 
                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="resetPassword(<?php echo $user['id']; ?>)" 
                            class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg">
                      <i class="fas fa-key"></i>
                    </button>
                    <?php if ($user['status'] === 'active'): ?>
                    <button onclick="suspendUser(<?php echo $user['id']; ?>)" 
                            class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg">
                      <i class="fas fa-ban"></i>
                    </button>
                    <?php else: ?>
                    <button onclick="activateUser(<?php echo $user['id']; ?>)" 
                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                      <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                      <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
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
              0 users selected
            </div>
            <div class="flex space-x-2">
              <button onclick="bulkActivate()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                Activate
              </button>
              <button onclick="bulkSuspend()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                Suspend
              </button>
              <button onclick="bulkDelete()" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                Delete Selected
              </button>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
              Showing 1 to 10 of <?php echo $totalUsers; ?> users
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

      <!-- Recent Activity -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent User Activity</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                <i class="fas fa-user-plus text-blue-600"></i>
              </div>
              <div>
                <p class="font-medium text-gray-900">New user registered</p>
                <p class="text-sm text-gray-600">Sarah Wilson joined as a donor</p>
              </div>
            </div>
            <span class="text-sm text-gray-500">2 hours ago</span>
          </div>
          <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                <i class="fas fa-key text-green-600"></i>
              </div>
              <div>
                <p class="font-medium text-gray-900">Password reset</p>
                <p class="text-sm text-gray-600">John Doe reset their password</p>
              </div>
            </div>
            <span class="text-sm text-gray-500">1 day ago</span>
          </div>
          <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                <i class="fas fa-ban text-yellow-600"></i>
              </div>
              <div>
                <p class="font-medium text-gray-900">User suspended</p>
                <p class="text-sm text-gray-600">Michael Brown's account was suspended</p>
              </div>
            </div>
            <span class="text-sm text-gray-500">3 days ago</span>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Add User Modal -->
  <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200 sticky top-0 bg-white">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Add New User</h3>
          <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <form id="userForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input type="tel" 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
              <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">Select Role</option>
                <option value="admin">System Admin</option>
                <option value="hospital_admin">Hospital Admin</option>
                <option value="hospital_staff">Hospital Staff</option>
                <option value="doctor">Doctor</option>
                <option value="donor">Donor</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
              <input type="text" 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
              <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
              </select>
            </div>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Initial Password *</label>
            <div class="relative">
              <input type="password" required id="passwordField"
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <button type="button" onclick="togglePassword()" class="absolute right-3 top-2 text-gray-400">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">Password must be at least 8 characters long</p>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-700">Can view dashboard</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-700">Can manage users</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-700">Can manage hospitals</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-700">Can process blood requests</span>
              </label>
            </div>
          </div>
        </form>
      </div>
      <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
        <button onclick="closeAddUserModal()" 
                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Cancel
        </button>
        <button onclick="saveUser()" 
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Create User
        </button>
      </div>
    </div>
  </div>

  <script>
    // Modal Functions
    function openAddUserModal() {
      document.getElementById('addUserModal').classList.remove('hidden');
      document.getElementById('addUserModal').classList.add('flex');
    }

    function closeAddUserModal() {
      document.getElementById('addUserModal').classList.remove('flex');
      document.getElementById('addUserModal').classList.add('hidden');
      document.getElementById('userForm').reset();
    }

    function openBulkUploadModal() {
      alert('Bulk upload modal would open here');
      // In real app, open modal for CSV upload
    }

    function togglePassword() {
      const passwordField = document.getElementById('passwordField');
      const type = passwordField.type === 'password' ? 'text' : 'password';
      passwordField.type = type;
    }

    // User Action Functions
    function viewUser(userId) {
      alert(`Viewing user details for ID: ${userId}`);
      // In real app, redirect to user profile or show modal
    }

    function editUser(userId) {
      alert(`Editing user with ID: ${userId}`);
      // In real app, open edit modal with pre-filled data
    }

    function resetPassword(userId) {
      if (confirm('Reset password for this user?')) {
        alert(`Password reset for user ID: ${userId}`);
        // In real app, send password reset email
      }
    }

    function suspendUser(userId) {
      if (confirm('Suspend this user account?')) {
        alert(`User ${userId} suspended`);
        // In real app, update user status via AJAX
      }
    }

    function activateUser(userId) {
      if (confirm('Activate this user account?')) {
        alert(`User ${userId} activated`);
        // In real app, update user status via AJAX
      }
    }

    function deleteUser(userId) {
      if (confirm('Are you sure you want to delete this user?')) {
        alert(`User ${userId} deleted`);
        // In real app, send AJAX request to delete
      }
    }

    function saveUser() {
      alert('User created successfully!');
      closeAddUserModal();
      // In real app, send form data via AJAX
    }

    // Bulk Actions
    function updateSelectedCount() {
      const checkboxes = document.querySelectorAll('.user-checkbox:checked');
      document.getElementById('selectedCount').textContent = `${checkboxes.length} users selected`;
    }

    function bulkActivate() {
      const selected = getSelectedUserIds();
      if (selected.length === 0) {
        alert('Please select users first');
        return;
      }
      if (confirm(`Activate ${selected.length} selected users?`)) {
        alert(`Activating users: ${selected.join(', ')}`);
        // In real app, send bulk update request
      }
    }

    function bulkSuspend() {
      const selected = getSelectedUserIds();
      if (selected.length === 0) {
        alert('Please select users first');
        return;
      }
      if (confirm(`Suspend ${selected.length} selected users?`)) {
        alert(`Suspending users: ${selected.join(', ')}`);
        // In real app, send bulk update request
      }
    }

    function bulkDelete() {
      const selected = getSelectedUserIds();
      if (selected.length === 0) {
        alert('Please select users first');
        return;
      }
      if (confirm(`Delete ${selected.length} selected users? This action cannot be undone.`)) {
        alert(`Deleting users: ${selected.join(', ')}`);
        // In real app, send bulk delete request
      }
    }

    function getSelectedUserIds() {
      const checkboxes = document.querySelectorAll('.user-checkbox:checked');
      return Array.from(checkboxes).map(cb => cb.value);
    }

    function exportUsers() {
      alert('Exporting users to CSV...');
      // In real app, generate and download CSV file
    }

    // Search and Filter
    document.addEventListener('DOMContentLoaded', function() {
      // Select all checkbox
      document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateSelectedCount();
      });

      // Individual checkbox changes
      document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
      });

      // Search and filter
      const searchInput = document.getElementById('searchInput');
      const roleFilter = document.getElementById('roleFilter');
      const statusFilter = document.getElementById('statusFilter');

      function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value;
        const selectedStatus = statusFilter.value;

        document.querySelectorAll('.user-row').forEach(row => {
          const name = row.getAttribute('data-name');
          const email = row.getAttribute('data-email');
          const role = row.getAttribute('data-role');
          const status = row.getAttribute('data-status');

          const matchesSearch = !searchTerm || 
                               name.includes(searchTerm) || 
                               email.includes(searchTerm);
          const matchesRole = !selectedRole || role === selectedRole;
          const matchesStatus = !selectedStatus || status === selectedStatus;

          row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
        });
      }

      searchInput.addEventListener('input', filterUsers);
      roleFilter.addEventListener('change', filterUsers);
      statusFilter.addEventListener('change', filterUsers);
    });

    // Close modal when clicking outside
    document.getElementById('addUserModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeAddUserModal();
      }
    });
  </script>
</body>

</html>