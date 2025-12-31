<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Sample blood request data
$bloodRequests = [
  [
    'id' => 1,
    'hospital' => 'Colombo General Hospital',
    'patient_name' => 'Kumar Perera',
    'blood_type' => 'O+',
    'units_needed' => 3,
    'urgency' => 'urgent',
    'status' => 'pending',
    'request_date' => '2023-10-15',
    'required_date' => '2023-10-20',
    'contact_person' => 'Dr. Sampath',
    'contact_number' => '0712345678',
    'reason' => 'Emergency surgery'
  ],
  [
    'id' => 2,
    'hospital' => 'Kandy Teaching Hospital',
    'patient_name' => 'Nimal Silva',
    'blood_type' => 'A+',
    'units_needed' => 2,
    'urgency' => 'high',
    'status' => 'approved',
    'request_date' => '2023-10-14',
    'required_date' => '2023-10-18',
    'contact_person' => 'Dr. Fernando',
    'contact_number' => '0723456789',
    'reason' => 'Cancer treatment'
  ],
  [
    'id' => 3,
    'hospital' => 'Galle General Hospital',
    'patient_name' => 'Sunil Rathnayake',
    'blood_type' => 'B-',
    'units_needed' => 4,
    'urgency' => 'urgent',
    'status' => 'pending',
    'request_date' => '2023-10-16',
    'required_date' => '2023-10-17',
    'contact_person' => 'Dr. Gunawardena',
    'contact_number' => '0734567890',
    'reason' => 'Accident victim'
  ],
  [
    'id' => 4,
    'hospital' => 'Jaffna Teaching Hospital',
    'patient_name' => 'Rajendran Sivan',
    'blood_type' => 'AB+',
    'units_needed' => 1,
    'urgency' => 'medium',
    'status' => 'completed',
    'request_date' => '2023-10-10',
    'required_date' => '2023-10-15',
    'contact_person' => 'Dr. Arulanandam',
    'contact_number' => '0745678901',
    'reason' => 'Dialysis'
  ],
  [
    'id' => 5,
    'hospital' => 'Kurunegala General Hospital',
    'patient_name' => 'Kamal Bandara',
    'blood_type' => 'O-',
    'units_needed' => 2,
    'urgency' => 'high',
    'status' => 'processing',
    'request_date' => '2023-10-13',
    'required_date' => '2023-10-19',
    'contact_person' => 'Dr. Jayawardena',
    'contact_number' => '0756789012',
    'reason' => 'Childbirth complications'
  ],
  [
    'id' => 6,
    'hospital' => 'Badulla General Hospital',
    'patient_name' => 'Saman Kumara',
    'blood_type' => 'A-',
    'units_needed' => 3,
    'urgency' => 'medium',
    'status' => 'pending',
    'request_date' => '2023-10-12',
    'required_date' => '2023-10-22',
    'contact_person' => 'Dr. Herath',
    'contact_number' => '0767890123',
    'reason' => 'Heart surgery'
  ],
  [
    'id' => 7,
    'hospital' => 'Ratnapura General Hospital',
    'patient_name' => 'Piyal Gunawardena',
    'blood_type' => 'B+',
    'units_needed' => 2,
    'urgency' => 'low',
    'status' => 'cancelled',
    'request_date' => '2023-10-11',
    'required_date' => '2023-10-25',
    'contact_person' => 'Dr. Alwis',
    'contact_number' => '0778901234',
    'reason' => 'Planned surgery'
  ],
  [
    'id' => 8,
    'hospital' => 'Anuradhapura Teaching Hospital',
    'patient_name' => 'Bandara Dissanayake',
    'blood_type' => 'O+',
    'units_needed' => 5,
    'urgency' => 'urgent',
    'status' => 'approved',
    'request_date' => '2023-10-09',
    'required_date' => '2023-10-16',
    'contact_person' => 'Dr. Weerasinghe',
    'contact_number' => '0789012345',
    'reason' => 'Multiple injuries'
  ]
];

// Statistics
$totalRequests = count($bloodRequests);
$pendingRequests = count(array_filter($bloodRequests, function ($r) {
  return $r['status'] === 'pending'; }));
$urgentRequests = count(array_filter($bloodRequests, function ($r) {
  return $r['urgency'] === 'urgent'; }));
$completedRequests = count(array_filter($bloodRequests, function ($r) {
  return $r['status'] === 'completed'; }));

// Blood type statistics
$bloodTypeStats = [];
foreach ($bloodRequests as $request) {
  $type = $request['blood_type'];
  if (!isset($bloodTypeStats[$type])) {
    $bloodTypeStats[$type] = 0;
  }
  $bloodTypeStats[$type]++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - Blood Requests Management</title>
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

    .request-card {
      transition: all 0.3s ease;
    }

    .request-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .urgency-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .urgent {
      background-color: #fee2e2;
      color: #dc2626;
    }

    .high {
      background-color: #ffedd5;
      color: #ea580c;
    }

    .medium {
      background-color: #fef3c7;
      color: #d97706;
    }

    .low {
      background-color: #ecfccb;
      color: #65a30d;
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
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-users w-6 text-center mr-3"></i>
        <span>Manage Donors</span>
      </a>
      <a href="hospitals.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-hospital w-6 text-center mr-3"></i>
        <span>Hospitals</span>
      </a>
      <a href="blood_requests.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
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
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
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
          <h1 class="text-2xl font-bold text-gray-900">Blood Requests Management</h1>
          <p class="text-gray-600">Manage all blood requests from hospitals</p>
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
        <!-- Total Requests Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Requests</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalRequests; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 12%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last week</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
              <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Pending Requests Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Pending Requests</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $pendingRequests; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-red-500 text-sm flex items-center">
                  <i class="fas fa-exclamation-circle mr-1"></i> Needs attention
                </span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-yellow-50 flex items-center justify-center">
              <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Urgent Requests Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Urgent Requests</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $urgentRequests; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Require immediate action</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
              <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Completed Requests Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Completed</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $completedRequests; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Successfully fulfilled</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Blood Type Distribution -->
      <div class="bg-white rounded-xl shadow mb-6 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Blood Type Requests Distribution</h3>
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

          foreach ($bloodTypeStats as $type => $count):
            ?>
            <div class="text-center">
              <div
                class="w-12 h-12 rounded-full <?php echo $bloodColors[$type]; ?> mx-auto flex items-center justify-center mb-2">
                <span class="text-white font-bold"><?php echo $type; ?></span>
              </div>
              <p class="text-sm text-gray-600"><?php echo $count; ?> requests</p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Filter and Action Bar -->
      <div class="bg-white rounded-xl shadow mb-6 p-4">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <div class="flex flex-wrap gap-4">
            <div class="relative">
              <input type="text" placeholder="Search requests..."
                class="w-full md:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <select
              class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="processing">Processing</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <select
              class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <option value="">All Urgency</option>
              <option value="urgent">Urgent</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div class="flex space-x-2">
            <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              <i class="fas fa-download mr-2"></i> Export
            </button>
            <button onclick="openNewRequestModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
              <i class="fas fa-plus mr-2"></i> New Request
            </button>
          </div>
        </div>
      </div>

      <!-- Blood Requests Table -->
      <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Request ID</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Hospital</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Patient</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Blood Type</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Units</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Urgency</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Status</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Required Date</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($bloodRequests as $request): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="py-4 px-4">
                    <span
                      class="font-mono text-sm text-gray-500">#REQ<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></span>
                  </td>
                  <td class="py-4 px-4">
                    <p class="font-medium text-gray-900"><?php echo $request['hospital']; ?></p>
                  </td>
                  <td class="py-4 px-4">
                    <p class="text-gray-700"><?php echo $request['patient_name']; ?></p>
                  </td>
                  <td class="py-4 px-4">
                    <span class="px-3 py-1 rounded-full font-bold 
                    <?php echo $bloodColors[$request['blood_type']]; ?> text-white">
                      <?php echo $request['blood_type']; ?>
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <span class="text-lg font-bold text-red-600"><?php echo $request['units_needed']; ?></span>
                    <span class="text-sm text-gray-500"> units</span>
                  </td>
                  <td class="py-4 px-4">
                    <span class="urgency-badge <?php echo $request['urgency']; ?>">
                      <?php echo ucfirst($request['urgency']); ?>
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <?php
                    $statusColors = [
                      'pending' => 'bg-yellow-100 text-yellow-800',
                      'approved' => 'bg-blue-100 text-blue-800',
                      'processing' => 'bg-purple-100 text-purple-800',
                      'completed' => 'bg-green-100 text-green-800',
                      'cancelled' => 'bg-gray-100 text-gray-800'
                    ];
                    ?>
                    <span class="status-badge <?php echo $statusColors[$request['status']]; ?>">
                      <?php echo ucfirst($request['status']); ?>
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <p class="text-gray-700"><?php echo date('M d, Y', strtotime($request['required_date'])); ?></p>
                    <p class="text-sm text-gray-500">
                      <?php
                      $days = (strtotime($request['required_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                      if ($days > 0) {
                        echo "In $days days";
                      } elseif ($days == 0) {
                        echo "Today";
                      } else {
                        echo "Past due";
                      }
                      ?>
                    </p>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex space-x-2">
                      <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button onclick="updateRequestStatus(<?php echo $request['id']; ?>)"
                        class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button onclick="assignDonors(<?php echo $request['id']; ?>)"
                        class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg">
                        <i class="fas fa-user-friends"></i>
                      </button>
                      <?php if ($request['status'] !== 'completed' && $request['status'] !== 'cancelled'): ?>
                        <button onclick="completeRequest(<?php echo $request['id']; ?>)"
                          class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                          <i class="fas fa-check"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Urgent Requests Highlight -->
      <div class="mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Urgent Requests <span class="text-red-600">⚠️</span></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php
          $urgentRequests = array_filter($bloodRequests, function ($r) {
            return $r['urgency'] === 'urgent' && $r['status'] !== 'completed' && $r['status'] !== 'cancelled';
          });
          foreach (array_slice($urgentRequests, 0, 3) as $request):
            ?>
            <div class="request-card bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <span
                    class="text-sm font-medium text-gray-500">#REQ<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></span>
                  <h4 class="font-bold text-lg text-gray-900 mt-1"><?php echo $request['hospital']; ?></h4>
                </div>
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                  URGENT
                </span>
              </div>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Patient:</span>
                  <span class="font-medium"><?php echo $request['patient_name']; ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Blood Type:</span>
                  <span
                    class="font-bold <?php echo $bloodColors[$request['blood_type']]; ?> text-white px-2 py-1 rounded">
                    <?php echo $request['blood_type']; ?>
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Units Needed:</span>
                  <span class="font-bold text-red-600"><?php echo $request['units_needed']; ?> units</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Required By:</span>
                  <span class="font-medium"><?php echo date('M d', strtotime($request['required_date'])); ?></span>
                </div>
              </div>
              <div class="mt-6 flex space-x-2">
                <button onclick="assignDonors(<?php echo $request['id']; ?>)"
                  class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                  Assign Donors
                </button>
                <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                  class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                  View
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>

  <!-- Request Details Modal -->
  <div id="requestDetailsModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200 sticky top-0 bg-white">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Request Details</h3>
          <button onclick="closeRequestDetailsModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <div id="requestDetailsContent">
          <!-- Details will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Donors Modal -->
  <div id="assignDonorsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Assign Donors to Request</h3>
          <button onclick="closeAssignDonorsModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <div id="assignDonorsContent">
          <!-- Donors list will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // Modal Functions
    function openNewRequestModal() {
      alert('Opening new request form');
      // In real app, open modal for creating new request
    }

    function viewRequestDetails(requestId) {
      // Sample details - in real app, fetch from server
      const details = `
        <div class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-semibold text-gray-700 mb-2">Request Information</h4>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Request ID:</span>
                  <span class="font-medium">#REQ${String(requestId).padStart(4, '0')}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Hospital:</span>
                  <span class="font-medium">Colombo General Hospital</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Request Date:</span>
                  <span class="font-medium">${new Date().toLocaleDateString()}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Required Date:</span>
                  <span class="font-medium">${new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toLocaleDateString()}</span>
                </div>
              </div>
            </div>
            <div>
              <h4 class="font-semibold text-gray-700 mb-2">Patient Information</h4>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Patient Name:</span>
                  <span class="font-medium">Kumar Perera</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Blood Type:</span>
                  <span class="font-bold bg-red-500 text-white px-2 py-1 rounded">O+</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Units Needed:</span>
                  <span class="font-bold text-red-600">3 units</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Urgency:</span>
                  <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Urgent</span>
                </div>
              </div>
            </div>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-700 mb-2">Contact Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-gray-600 text-sm">Contact Person</p>
                <p class="font-medium">Dr. Sampath</p>
              </div>
              <div>
                <p class="text-gray-600 text-sm">Contact Number</p>
                <p class="font-medium">071-2345678</p>
              </div>
              <div class="md:col-span-2">
                <p class="text-gray-600 text-sm">Reason for Request</p>
                <p class="font-medium mt-1 p-3 bg-gray-50 rounded-lg">Emergency surgery required for patient. Blood needed for operation theater.</p>
              </div>
            </div>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-700 mb-2">Status History</h4>
            <div class="space-y-2">
              <div class="flex items-center">
                <div class="w-3 h-3 rounded-full bg-green-500 mr-3"></div>
                <span class="text-gray-700">Request submitted - ${new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toLocaleDateString()}</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 rounded-full bg-yellow-500 mr-3"></div>
                <span class="text-gray-700">Under review - ${new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toLocaleDateString()}</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 rounded-full bg-blue-500 mr-3"></div>
                <span class="text-gray-700">Approved - Today</span>
              </div>
            </div>
          </div>
        </div>
      `;

      document.getElementById('requestDetailsContent').innerHTML = details;
      document.getElementById('requestDetailsModal').classList.remove('hidden');
      document.getElementById('requestDetailsModal').classList.add('flex');
    }

    function closeRequestDetailsModal() {
      document.getElementById('requestDetailsModal').classList.remove('flex');
      document.getElementById('requestDetailsModal').classList.add('hidden');
    }

    function assignDonors(requestId) {
      // Sample donors list - in real app, fetch matching donors from server
      const donors = `
        <div class="space-y-4">
          <h4 class="font-semibold text-gray-700">Available Donors for O+</h4>
          <div class="space-y-3">
            ${[1, 2, 3].map(i => `
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
              <div>
                <p class="font-medium text-gray-900">Donor ${i}</p>
                <p class="text-sm text-gray-600">Last donated: ${new Date(Date.now() - i * 30 * 24 * 60 * 60 * 1000).toLocaleDateString()}</p>
              </div>
              <div class="flex items-center space-x-2">
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                  Assign
                </button>
                <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                  View
                </button>
              </div>
            </div>
            `).join('')}
          </div>
          <div class="mt-6 pt-6 border-t border-gray-200">
            <button onclick="closeAssignDonorsModal()" 
                    class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
              Complete Assignment
            </button>
          </div>
        </div>
      `;

      document.getElementById('assignDonorsContent').innerHTML = donors;
      document.getElementById('assignDonorsModal').classList.remove('hidden');
      document.getElementById('assignDonorsModal').classList.add('flex');
    }

    function closeAssignDonorsModal() {
      document.getElementById('assignDonorsModal').classList.remove('flex');
      document.getElementById('assignDonorsModal').classList.add('hidden');
    }

    function updateRequestStatus(requestId) {
      const newStatus = prompt('Enter new status (pending/approved/processing/completed/cancelled):');
      if (newStatus) {
        alert(`Request ${requestId} status updated to: ${newStatus}`);
        // In real app, send AJAX request to update status
      }
    }

    function completeRequest(requestId) {
      if (confirm('Mark this request as completed?')) {
        alert(`Request ${requestId} marked as completed`);
        // In real app, send AJAX request to complete
      }
    }

    // Search and filter functionality
    document.addEventListener('DOMContentLoaded', function () {
      const searchInput = document.querySelector('input[type="text"]');
      const statusFilter = document.querySelectorAll('select')[0];
      const urgencyFilter = document.querySelectorAll('select')[1];

      function filterRequests() {
        // Implement filtering logic here
      }

      searchInput.addEventListener('input', filterRequests);
      statusFilter.addEventListener('change', filterRequests);
      urgencyFilter.addEventListener('change', filterRequests);
    });

    // Close modals when clicking outside
    document.getElementById('requestDetailsModal').addEventListener('click', function (e) {
      if (e.target === this) closeRequestDetailsModal();
    });

    document.getElementById('assignDonorsModal').addEventListener('click', function (e) {
      if (e.target === this) closeAssignDonorsModal();
    });
  </script>
</body>

</html>