<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Sample appointments data
$appointments = [
  [
    'id' => 1,
    'donor_name' => 'John Doe',
    'donor_id' => 'D001',
    'hospital' => 'Colombo General Hospital',
    'blood_type' => 'O+',
    'appointment_date' => '2023-10-20',
    'appointment_time' => '09:00 AM',
    'status' => 'confirmed',
    'type' => 'blood_donation',
    'duration' => '45 minutes',
    'contact' => '0712345678',
    'notes' => 'Regular donor, prefers morning appointments',
    'created_date' => '2023-10-15'
  ],
  [
    'id' => 2,
    'donor_name' => 'Jane Smith',
    'donor_id' => 'D002',
    'hospital' => 'Kandy Teaching Hospital',
    'blood_type' => 'A+',
    'appointment_date' => '2023-10-21',
    'appointment_time' => '10:30 AM',
    'status' => 'pending',
    'type' => 'plasma_donation',
    'duration' => '60 minutes',
    'contact' => '0723456789',
    'notes' => 'First-time plasma donor',
    'created_date' => '2023-10-16'
  ],
  [
    'id' => 3,
    'donor_name' => 'Kamal Perera',
    'donor_id' => 'D003',
    'hospital' => 'Galle General Hospital',
    'blood_type' => 'B-',
    'appointment_date' => '2023-10-19',
    'appointment_time' => '02:00 PM',
    'status' => 'completed',
    'type' => 'blood_donation',
    'duration' => '30 minutes',
    'contact' => '0734567890',
    'notes' => 'Completed successfully',
    'created_date' => '2023-10-14'
  ],
  [
    'id' => 4,
    'donor_name' => 'Nimal Silva',
    'donor_id' => 'D004',
    'hospital' => 'Jaffna Teaching Hospital',
    'blood_type' => 'AB+',
    'appointment_date' => '2023-10-22',
    'appointment_time' => '11:00 AM',
    'status' => 'confirmed',
    'type' => 'blood_donation',
    'duration' => '45 minutes',
    'contact' => '0745678901',
    'notes' => 'Special blood type, urgent need',
    'created_date' => '2023-10-17'
  ],
  [
    'id' => 5,
    'donor_name' => 'Sunil Rathnayake',
    'donor_id' => 'D005',
    'hospital' => 'Kurunegala General Hospital',
    'blood_type' => 'O-',
    'appointment_date' => '2023-10-18',
    'appointment_time' => '03:30 PM',
    'status' => 'cancelled',
    'type' => 'blood_donation',
    'duration' => '45 minutes',
    'contact' => '0756789012',
    'notes' => 'Cancelled due to illness',
    'created_date' => '2023-10-13'
  ],
  [
    'id' => 6,
    'donor_name' => 'Saman Kumara',
    'donor_id' => 'D006',
    'hospital' => 'Badulla General Hospital',
    'blood_type' => 'A-',
    'appointment_date' => '2023-10-23',
    'appointment_time' => '09:30 AM',
    'status' => 'confirmed',
    'type' => 'platelet_donation',
    'duration' => '90 minutes',
    'contact' => '0767890123',
    'notes' => 'Platelet donation for cancer patient',
    'created_date' => '2023-10-18'
  ],
  [
    'id' => 7,
    'donor_name' => 'Piyal Gunawardena',
    'donor_id' => 'D007',
    'hospital' => 'Ratnapura General Hospital',
    'blood_type' => 'B+',
    'appointment_date' => '2023-10-24',
    'appointment_time' => '01:00 PM',
    'status' => 'pending',
    'type' => 'blood_donation',
    'duration' => '45 minutes',
    'contact' => '0778901234',
    'notes' => 'Walk-in appointment requested',
    'created_date' => '2023-10-19'
  ],
  [
    'id' => 8,
    'donor_name' => 'Bandara Dissanayake',
    'donor_id' => 'D008',
    'hospital' => 'Anuradhapura Teaching Hospital',
    'blood_type' => 'O+',
    'appointment_date' => '2023-10-25',
    'appointment_time' => '10:00 AM',
    'status' => 'confirmed',
    'type' => 'blood_donation',
    'duration' => '45 minutes',
    'contact' => '0789012345',
    'notes' => 'Group donation event',
    'created_date' => '2023-10-20'
  ],
  [
    'id' => 9,
    'donor_name' => 'Anusha Fernando',
    'donor_id' => 'D009',
    'hospital' => 'Colombo General Hospital',
    'blood_type' => 'A+',
    'appointment_date' => '2023-10-26',
    'appointment_time' => '02:30 PM',
    'status' => 'pending',
    'type' => 'plasma_donation',
    'duration' => '60 minutes',
    'contact' => '0790123456',
    'notes' => 'Recovered COVID-19 patient',
    'created_date' => '2023-10-21'
  ],
  [
    'id' => 10,
    'donor_name' => 'Rajitha Herath',
    'donor_id' => 'D010',
    'hospital' => 'Kandy Teaching Hospital',
    'blood_type' => 'B-',
    'appointment_date' => '2023-10-27',
    'appointment_time' => '11:30 AM',
    'status' => 'confirmed',
    'type' => 'blood_donation',
    'duration' => '45 minutes',
    'contact' => '0701234567',
    'notes' => 'Regular quarterly donation',
    'created_date' => '2023-10-22'
  ]
];

// Statistics
$totalAppointments = count($appointments);
$confirmedAppointments = count(array_filter($appointments, function($a) { return $a['status'] === 'confirmed'; }));
$pendingAppointments = count(array_filter($appointments, function($a) { return $a['status'] === 'pending'; }));
$completedAppointments = count(array_filter($appointments, function($a) { return $a['status'] === 'completed'; }));

// Today's appointments
$today = date('Y-m-d');
$todayAppointments = array_filter($appointments, function($a) use ($today) { 
  return $a['appointment_date'] === $today; 
});

// Upcoming appointments (next 7 days)
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$upcomingAppointments = array_filter($appointments, function($a) use ($today, $nextWeek) { 
  return $a['appointment_date'] >= $today && 
         $a['appointment_date'] <= $nextWeek && 
         $a['status'] !== 'completed' && 
         $a['status'] !== 'cancelled'; 
});

// Appointment types statistics
$typeStats = [];
foreach ($appointments as $appointment) {
  $type = $appointment['type'];
  if (!isset($typeStats[$type])) {
    $typeStats[$type] = 0;
  }
  $typeStats[$type]++;
}

// Blood type statistics for appointments
$bloodTypeStats = [];
foreach ($appointments as $appointment) {
  $type = $appointment['blood_type'];
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
  <title>BloodSync - Appointment Management</title>
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

    .appointment-card {
      transition: all 0.3s ease;
    }

    .appointment-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .type-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-confirmed { background-color: #d1fae5; color: #065f46; }
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-completed { background-color: #dbeafe; color: #1e40af; }
    .status-cancelled { background-color: #fee2e2; color: #991b1b; }
    .status-no_show { background-color: #f3f4f6; color: #374151; }

    .type-blood_donation { background-color: #fee2e2; color: #dc2626; }
    .type-plasma_donation { background-color: #e0e7ff; color: #4f46e5; }
    .type-platelet_donation { background-color: #f0fdf4; color: #16a34a; }
    .type-testing { background-color: #fef3c7; color: #d97706; }

    .calendar-day {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 9999px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .calendar-day:hover {
      background-color: #f3f4f6;
    }

    .calendar-day.today {
      background-color: #fee2e2;
      color: #dc2626;
      font-weight: 600;
    }

    .calendar-day.selected {
      background-color: #dc2626;
      color: white;
    }

    .calendar-day.has-appointments {
      position: relative;
    }

    .calendar-day.has-appointments::after {
      content: '';
      position: absolute;
      bottom: 4px;
      left: 50%;
      transform: translateX(-50%);
      width: 4px;
      height: 4px;
      border-radius: 9999px;
      background-color: #dc2626;
    }

    .time-slot {
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 0.75rem;
      margin-bottom: 0.5rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .time-slot:hover {
      border-color: #dc2626;
      background-color: #fef2f2;
    }

    .time-slot.selected {
      border-color: #dc2626;
      background-color: #fee2e2;
    }

    .time-slot.booked {
      background-color: #f3f4f6;
      border-color: #d1d5db;
      cursor: not-allowed;
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
      <a href="blood_requests.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-clipboard-list w-6 text-center mr-3"></i>
        <span>Blood Requests</span>
      </a>
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-box w-6 text-center mr-3"></i>
        <span>Inventory</span>
      </a>
      <a href="appointments.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
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
          <h1 class="text-2xl font-bold text-gray-900">Appointment Management</h1>
          <p class="text-gray-600">Schedule and manage donor appointments</p>
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
        <!-- Total Appointments Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Appointments</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalAppointments; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 22%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last week</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
              <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Today's Appointments Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Today's Appointments</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo count($todayAppointments); ?></h3>
              <p class="text-gray-400 text-sm mt-2">
                <?php 
                $completedToday = count(array_filter($todayAppointments, function($a) { 
                  return $a['status'] === 'completed'; 
                }));
                echo "$completedToday completed";
                ?>
              </p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-calendar-day text-green-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Confirmed Appointments Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Confirmed</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $confirmedAppointments; ?></h3>
              <p class="text-gray-400 text-sm mt-2">
                <?php echo round(($confirmedAppointments/$totalAppointments)*100); ?>% of total
              </p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center">
              <i class="fas fa-check-circle text-purple-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Pending Appointments Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Pending Approval</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $pendingAppointments; ?></h3>
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
      </div>

      <!-- Appointment Types Distribution -->
      <div class="bg-white rounded-xl shadow mb-6 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Appointment Types Distribution</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <?php 
          $typeColors = [
            'blood_donation' => 'bg-red-500',
            'plasma_donation' => 'bg-blue-500',
            'platelet_donation' => 'bg-green-500',
            'testing' => 'bg-yellow-500'
          ];
          
          $typeNames = [
            'blood_donation' => 'Blood Donation',
            'plasma_donation' => 'Plasma Donation',
            'platelet_donation' => 'Platelet Donation',
            'testing' => 'Testing'
          ];
          
          foreach ($typeStats as $type => $count): 
          ?>
          <div class="text-center">
            <div class="w-16 h-16 rounded-full <?php echo $typeColors[$type]; ?> mx-auto flex items-center justify-center mb-2">
              <span class="text-white font-bold text-xl"><?php echo $count; ?></span>
            </div>
            <p class="text-sm font-medium text-gray-900"><?php echo $typeNames[$type]; ?></p>
            <p class="text-xs text-gray-600"><?php echo round(($count/$totalAppointments)*100); ?>%</p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Calendar Section -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Appointment Calendar</h3>
              <div class="flex space-x-2">
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <span class="px-4 py-2 font-medium text-gray-900">October 2023</span>
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                  <i class="fas fa-chevron-right"></i>
                </button>
                <button onclick="openScheduleModal()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 ml-2">
                  <i class="fas fa-plus mr-2"></i> Schedule
                </button>
              </div>
            </div>

            <div class="grid grid-cols-7 gap-2 mb-4">
              <?php
              $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
              foreach ($days as $day):
              ?>
              <div class="text-center font-medium text-gray-500 py-2">
                <?php echo $day; ?>
              </div>
              <?php endforeach; ?>
              
              <!-- Calendar days -->
              <?php
              // Generate calendar days (simplified version)
              for ($i = 1; $i <= 31; $i++):
                $date = sprintf('2023-10-%02d', $i);
                $hasAppointments = count(array_filter($appointments, function($a) use ($date) {
                  return $a['appointment_date'] === $date;
                })) > 0;
                $isToday = $date === $today;
              ?>
              <div class="calendar-day <?php echo $isToday ? 'today' : ''; ?> <?php echo $hasAppointments ? 'has-appointments' : ''; ?>"
                   onclick="selectDate('<?php echo $date; ?>')">
                <?php echo $i; ?>
              </div>
              <?php endfor; ?>
            </div>

            <div class="mt-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-medium text-gray-900">Today's Schedule</h4>
                <span class="text-sm text-gray-500"><?php echo date('F d, Y'); ?></span>
              </div>
              
              <div class="space-y-3">
                <?php if (count($todayAppointments) > 0): ?>
                  <?php foreach ($todayAppointments as $appointment): ?>
                  <div class="appointment-card bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-start">
                      <div>
                        <div class="flex items-center mb-2">
                          <span class="status-badge status-<?php echo $appointment['status']; ?> mr-3">
                            <?php echo ucfirst($appointment['status']); ?>
                          </span>
                          <span class="type-badge type-<?php echo $appointment['type']; ?>">
                            <?php 
                            $typeNames = [
                              'blood_donation' => 'Blood Donation',
                              'plasma_donation' => 'Plasma Donation',
                              'platelet_donation' => 'Platelet Donation',
                              'testing' => 'Testing'
                            ];
                            echo $typeNames[$appointment['type']];
                            ?>
                          </span>
                        </div>
                        <h5 class="font-semibold text-gray-900"><?php echo $appointment['donor_name']; ?></h5>
                        <p class="text-sm text-gray-600">
                          <i class="fas fa-hospital mr-1"></i> <?php echo $appointment['hospital']; ?>
                        </p>
                      </div>
                      <div class="text-right">
                        <p class="text-lg font-bold text-gray-900"><?php echo $appointment['appointment_time']; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $appointment['duration']; ?></p>
                      </div>
                    </div>
                    <div class="mt-3 flex justify-between items-center">
                      <div class="flex items-center">
                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold mr-2">
                          <?php echo $appointment['blood_type']; ?>
                        </span>
                        <span class="text-sm text-gray-600"><?php echo $appointment['contact']; ?></span>
                      </div>
                      <div class="flex space-x-2">
                        <button onclick="viewAppointment(<?php echo $appointment['id']; ?>)" 
                                class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                          View
                        </button>
                        <?php if ($appointment['status'] === 'pending'): ?>
                        <button onclick="confirmAppointment(<?php echo $appointment['id']; ?>)" 
                                class="px-3 py-1 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                          Confirm
                        </button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-calendar-times text-3xl mb-3"></i>
                    <p>No appointments scheduled for today</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Upcoming Appointments -->
          <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Upcoming Appointments (Next 7 Days)</h3>
              <a href="#" class="text-red-600 text-sm font-medium hover:text-red-700">View All</a>
            </div>
            
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Date & Time</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Donor</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Hospital</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Type</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach (array_slice($upcomingAppointments, 0, 5) as $appointment): ?>
                  <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-4 px-4">
                      <p class="font-medium text-gray-900"><?php echo date('M d', strtotime($appointment['appointment_date'])); ?></p>
                      <p class="text-sm text-gray-500"><?php echo $appointment['appointment_time']; ?></p>
                    </td>
                    <td class="py-4 px-4">
                      <p class="font-medium text-gray-900"><?php echo $appointment['donor_name']; ?></p>
                      <p class="text-sm text-gray-500"><?php echo $appointment['donor_id']; ?></p>
                    </td>
                    <td class="py-4 px-4">
                      <p class="text-gray-700"><?php echo $appointment['hospital']; ?></p>
                    </td>
                    <td class="py-4 px-4">
                      <span class="type-badge type-<?php echo $appointment['type']; ?>">
                        <?php 
                        $typeNames = [
                          'blood_donation' => 'Blood',
                          'plasma_donation' => 'Plasma',
                          'platelet_donation' => 'Platelet',
                          'testing' => 'Test'
                        ];
                        echo $typeNames[$appointment['type']];
                        ?>
                      </span>
                    </td>
                    <td class="py-4 px-4">
                      <span class="status-badge status-<?php echo $appointment['status']; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                      </span>
                    </td>
                    <td class="py-4 px-4">
                      <div class="flex space-x-2">
                        <button onclick="viewAppointment(<?php echo $appointment['id']; ?>)" 
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editAppointment(<?php echo $appointment['id']; ?>)" 
                                class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="sendReminder(<?php echo $appointment['id']; ?>)" 
                                class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg">
                          <i class="fas fa-bell"></i>
                        </button>
                      </div>
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
          <!-- Quick Schedule -->
          <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Schedule</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
                <input type="date" id="quickDate" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                       value="<?php echo date('Y-m-d'); ?>">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Available Time Slots</label>
                <div class="space-y-2" id="timeSlots">
                  <!-- Time slots will be loaded here -->
                </div>
              </div>
              <button onclick="quickSchedule()" 
                      class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-calendar-plus mr-2"></i> Schedule Appointment
              </button>
            </div>
          </div>

          <!-- Appointment Stats -->
          <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Appointment Statistics</h3>
            <div class="space-y-4">
              <div>
                <div class="flex justify-between mb-1">
                  <span class="text-sm text-gray-600">Completion Rate</span>
                  <span class="text-sm font-medium text-gray-900">
                    <?php echo round(($completedAppointments/$totalAppointments)*100); ?>%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-green-600 h-2 rounded-full" 
                       style="width: <?php echo ($completedAppointments/$totalAppointments)*100; ?>%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between mb-1">
                  <span class="text-sm text-gray-600">Cancellation Rate</span>
                  <span class="text-sm font-medium text-gray-900">
                    <?php 
                    $cancelled = count(array_filter($appointments, function($a) { 
                      return $a['status'] === 'cancelled'; 
                    }));
                    echo round(($cancelled/$totalAppointments)*100); ?>%
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-red-600 h-2 rounded-full" 
                       style="width: <?php echo ($cancelled/$totalAppointments)*100; ?>%"></div>
                </div>
              </div>
              <div class="pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-2">Blood Types Distribution</p>
                <div class="space-y-2">
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
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700"><?php echo $type; ?></span>
                    <div class="flex items-center">
                      <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $count; ?></span>
                      <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full <?php echo $bloodColors[$type]; ?>" 
                             style="width: <?php echo ($count/array_sum($bloodTypeStats))*100; ?>%"></div>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div class="space-y-3">
              <div class="flex items-start">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3 mt-1">
                  <i class="fas fa-check text-green-600 text-sm"></i>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Appointment Completed</p>
                  <p class="text-xs text-gray-500">Kamal Perera at 2:00 PM</p>
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-1">
                  <i class="fas fa-calendar-plus text-blue-600 text-sm"></i>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">New Appointment Scheduled</p>
                  <p class="text-xs text-gray-500">Rajitha Herath for Oct 27</p>
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3 mt-1">
                  <i class="fas fa-times text-red-600 text-sm"></i>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Appointment Cancelled</p>
                  <p class="text-xs text-gray-500">Sunil Rathnayake due to illness</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Schedule Appointment Modal -->
  <div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200 sticky top-0 bg-white">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Schedule New Appointment</h3>
          <button onclick="closeScheduleModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <div id="scheduleForm">
          <!-- Form will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Appointment Details Modal -->
  <div id="appointmentDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Appointment Details</h3>
          <button onclick="closeAppointmentDetailsModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <div id="appointmentDetailsContent">
          <!-- Details will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // Initialize time slots
    function loadTimeSlots() {
      const timeSlots = [
        { time: '08:00 AM', available: true },
        { time: '09:00 AM', available: true },
        { time: '10:00 AM', available: false },
        { time: '11:00 AM', available: true },
        { time: '01:00 PM', available: true },
        { time: '02:00 PM', available: false },
        { time: '03:00 PM', available: true },
        { time: '04:00 PM', available: true }
      ];
      
      const container = document.getElementById('timeSlots');
      container.innerHTML = '';
      
      timeSlots.forEach(slot => {
        const slotDiv = document.createElement('div');
        slotDiv.className = `time-slot ${slot.available ? '' : 'booked'}`;
        if (!slot.available) slotDiv.title = 'Already booked';
        
        slotDiv.innerHTML = `
          <div class="flex justify-between items-center">
            <span class="font-medium ${slot.available ? 'text-gray-900' : 'text-gray-400'}">
              ${slot.time}
            </span>
            <span class="text-sm ${slot.available ? 'text-green-600' : 'text-red-600'}">
              ${slot.available ? 'Available' : 'Booked'}
            </span>
          </div>
        `;
        
        if (slot.available) {
          slotDiv.addEventListener('click', () => selectTimeSlot(slotDiv, slot.time));
        }
        
        container.appendChild(slotDiv);
      });
    }

    let selectedTimeSlot = null;
    
    function selectTimeSlot(element, time) {
      // Remove selection from all slots
      document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
      });
      
      // Add selection to clicked slot
      element.classList.add('selected');
      selectedTimeSlot = time;
    }

    function selectDate(date) {
      alert(`Selected date: ${date}`);
      // In real app, load appointments for selected date
    }

    function quickSchedule() {
      const date = document.getElementById('quickDate').value;
      if (!selectedTimeSlot) {
        alert('Please select a time slot');
        return;
      }
      
      alert(`Scheduling appointment for ${date} at ${selectedTimeSlot}`);
      // In real app, open scheduling form with pre-filled date/time
      openScheduleModal(date, selectedTimeSlot);
    }

    // Modal Functions
    function openScheduleModal(date = null, time = null) {
      const formContent = `
        <div class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Donor *</label>
              <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">Select Donor</option>
                <option value="1">John Doe (D001)</option>
                <option value="2">Jane Smith (D002)</option>
                <option value="3">Kamal Perera (D003)</option>
                <option value="4">Nimal Silva (D004)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Hospital *</label>
              <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">Select Hospital</option>
                <option value="1">Colombo General Hospital</option>
                <option value="2">Kandy Teaching Hospital</option>
                <option value="3">Galle General Hospital</option>
                <option value="4">Jaffna Teaching Hospital</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Date *</label>
              <input type="date" required value="${date || '<?php echo date('Y-m-d'); ?>'}" 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Time *</label>
              <input type="time" required value="${time || '09:00'}" 
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Appointment Type *</label>
              <select required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="blood_donation">Blood Donation</option>
                <option value="plasma_donation">Plasma Donation</option>
                <option value="platelet_donation">Platelet Donation</option>
                <option value="testing">Testing</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
              <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="30">30 minutes</option>
                <option value="45" selected>45 minutes</option>
                <option value="60">60 minutes</option>
                <option value="90">90 minutes</option>
              </select>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea rows="3" 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                      placeholder="Any special instructions or notes..."></textarea>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Send Notification</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500" checked>
                <span class="ml-2 text-sm text-gray-700">Send confirmation email to donor</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-700">Send reminder SMS 24 hours before</span>
              </label>
            </div>
          </div>
        </div>
      `;
      
      document.getElementById('scheduleForm').innerHTML = formContent;
      document.getElementById('scheduleModal').classList.remove('hidden');
      document.getElementById('scheduleModal').classList.add('flex');
    }

    function closeScheduleModal() {
      document.getElementById('scheduleModal').classList.remove('flex');
      document.getElementById('scheduleModal').classList.add('hidden');
    }

    function viewAppointment(appointmentId) {
      // Sample appointment details
      const details = `
        <div class="space-y-6">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="text-lg font-bold text-gray-900">Appointment #APPT${String(appointmentId).padStart(4, '0')}</h4>
              <p class="text-gray-600">Scheduled appointment details</p>
            </div>
            <span class="status-badge status-confirmed">Confirmed</span>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h5 class="font-semibold text-gray-700 mb-3">Donor Information</h5>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Name:</span>
                  <span class="font-medium">John Doe</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Donor ID:</span>
                  <span class="font-medium">D001</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Blood Type:</span>
                  <span class="font-bold bg-red-500 text-white px-2 py-1 rounded">O+</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Contact:</span>
                  <span class="font-medium">071-2345678</span>
                </div>
              </div>
            </div>
            
            <div>
              <h5 class="font-semibold text-gray-700 mb-3">Appointment Details</h5>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Date & Time:</span>
                  <span class="font-medium">Oct 20, 2023 • 09:00 AM</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Hospital:</span>
                  <span class="font-medium">Colombo General Hospital</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Type:</span>
                  <span class="type-badge type-blood_donation">Blood Donation</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Duration:</span>
                  <span class="font-medium">45 minutes</span>
                </div>
              </div>
            </div>
          </div>
          
          <div>
            <h5 class="font-semibold text-gray-700 mb-3">Notes</h5>
            <div class="p-4 bg-gray-50 rounded-lg">
              <p class="text-gray-700">Regular donor, prefers morning appointments. Last donated on Sep 15, 2023.</p>
            </div>
          </div>
          
          <div class="flex space-x-3 pt-6 border-t border-gray-200">
            <button onclick="editAppointment(${appointmentId})" 
                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
              <i class="fas fa-edit mr-2"></i> Edit
            </button>
            <button onclick="sendReminder(${appointmentId})" 
                    class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
              <i class="fas fa-bell mr-2"></i> Send Reminder
            </button>
            <button onclick="cancelAppointment(${appointmentId})" 
                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
              <i class="fas fa-times mr-2"></i> Cancel
            </button>
          </div>
        </div>
      `;
      
      document.getElementById('appointmentDetailsContent').innerHTML = details;
      document.getElementById('appointmentDetailsModal').classList.remove('hidden');
      document.getElementById('appointmentDetailsModal').classList.add('flex');
    }

    function closeAppointmentDetailsModal() {
      document.getElementById('appointmentDetailsModal').classList.remove('flex');
      document.getElementById('appointmentDetailsModal').classList.add('hidden');
    }

    // Appointment Action Functions
    function confirmAppointment(appointmentId) {
      if (confirm('Confirm this appointment?')) {
        alert(`Appointment ${appointmentId} confirmed`);
        // In real app, update appointment status via AJAX
      }
    }

    function editAppointment(appointmentId) {
      alert(`Editing appointment ${appointmentId}`);
      // In real app, open edit form
    }

    function sendReminder(appointmentId) {
      alert(`Reminder sent for appointment ${appointmentId}`);
      // In real app, send email/SMS reminder
    }

    function cancelAppointment(appointmentId) {
      if (confirm('Cancel this appointment?')) {
        const reason = prompt('Please provide cancellation reason:');
        if (reason) {
          alert(`Appointment ${appointmentId} cancelled: ${reason}`);
          // In real app, update appointment status via AJAX
        }
      }
    }

    // Initialize time slots on page load
    document.addEventListener('DOMContentLoaded', function() {
      loadTimeSlots();
      
      // Set today's date in quick schedule
      document.getElementById('quickDate').min = new Date().toISOString().split('T')[0];
      
      // Close modals when clicking outside
      document.getElementById('scheduleModal').addEventListener('click', function(e) {
        if (e.target === this) closeScheduleModal();
      });
      
      document.getElementById('appointmentDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) closeAppointmentDetailsModal();
      });
    });
  </script>
</body>

</html>