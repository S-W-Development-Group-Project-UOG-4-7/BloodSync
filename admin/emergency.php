<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Check user role for permissions
$isAdmin = ($_SESSION['user_role'] === 'admin');
$isHospital = ($_SESSION['user_role'] === 'hospital');
$isLabTech = ($_SESSION['user_role'] === 'lab_tech');
$isDonor = ($_SESSION['user_role'] === 'donor');

// Sample emergency broadcasts
$emergencyBroadcasts = [
  [
    'id' => 1,
    'title' => 'CRITICAL: O- Blood Shortage',
    'hospital' => 'National Hospital',
    'blood_type' => 'O-',
    'units_needed' => 15,
    'urgency' => 'critical',
    'location' => 'Colombo',
    'radius' => '50km',
    'posted_by' => 'Dr. Perera',
    'posted_time' => '10 minutes ago',
    'expires_in' => '2 hours',
    'status' => 'active',
    'responded_donors' => 8,
    'units_collected' => 6
  ],
  [
    'id' => 2,
    'title' => 'URGENT: B+ Needed for Surgery',
    'hospital' => 'General Hospital Kandy',
    'blood_type' => 'B+',
    'units_needed' => 5,
    'urgency' => 'urgent',
    'location' => 'Kandy',
    'radius' => '30km',
    'posted_by' => 'Dr. Silva',
    'posted_time' => '45 minutes ago',
    'expires_in' => '5 hours',
    'status' => 'active',
    'responded_donors' => 3,
    'units_collected' => 2
  ],
  [
    'id' => 3,
    'title' => 'Emergency: Multiple Trauma Cases',
    'hospital' => 'Teaching Hospital Karapitiya',
    'blood_type' => 'All Types',
    'units_needed' => 25,
    'urgency' => 'emergency',
    'location' => 'Galle',
    'radius' => '100km',
    'posted_by' => 'Emergency Dept.',
    'posted_time' => '2 hours ago',
    'expires_in' => '8 hours',
    'status' => 'active',
    'responded_donors' => 15,
    'units_collected' => 12
  ]
];

$activeEmergencies = array_filter($emergencyBroadcasts, function ($broadcast) {
  return $broadcast['status'] === 'active';
});

$activeCount = count($activeEmergencies);
$criticalCount = count(array_filter($activeEmergencies, function ($broadcast) {
  return $broadcast['urgency'] === 'critical';
}));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - Emergency Broadcast</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
    }

    .emergency-pulse {
      animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.7;
      }
    }

    .blood-type-badge {
      @apply px-3 py-1 rounded-full font-bold text-white text-sm;
    }

    .urgency-badge {
      @apply px-3 py-1 rounded-full font-bold text-white text-sm;
    }

    .progress-bar {
      transition: width 0.5s ease-in-out;
    }

    .emergency-card {
      transition: all 0.3s ease;
      border-left: 4px solid #E53E3E;
    }

    .emergency-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .emergency-card.critical {
      border-left-color: #DC2626;
      background: linear-gradient(to right, #FEF2F2, white);
    }

    .emergency-card.urgent {
      border-left-color: #EA580C;
      background: linear-gradient(to right, #FFF7ED, white);
    }

    .emergency-card.emergency {
      border-left-color: #B91C1C;
      background: linear-gradient(to right, #FEE2E2, white);
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Header Navigation -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-2">
          <div class="bg-red-600 text-white p-2 rounded-lg">
            <i class="fas fa-broadcast-tower text-xl"></i>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Emergency Broadcast</h1>
            <p class="text-gray-600 text-sm">Real-time blood emergency alerts</p>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <?php if ($isAdmin || $isHospital): ?>
            <button
              class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold flex items-center space-x-2 transition duration-200">
              <i class="fas fa-plus-circle"></i>
              <span>New Alert</span>
            </button>
          <?php endif; ?>
          <a href="dashboard.php" class="text-gray-700 hover:text-red-600 font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Emergency Banner -->
  <?php if ($activeCount > 0): ?>
    <div class="bg-gradient-to-r from-red-600 to-red-700 text-white">
      <div class="container mx-auto px-6 py-3">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="flex items-center space-x-3 mb-2 md:mb-0">
            <div class="emergency-pulse">
              <i class="fas fa-exclamation-triangle text-2xl"></i>
            </div>
            <div>
              <h3 class="font-bold">ACTIVE EMERGENCY ALERTS</h3>
              <p class="text-sm opacity-90"><?php echo $criticalCount; ?> critical blood shortages require immediate
                attention</p>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="bg-white bg-opacity-20 px-4 py-1 rounded-full">
              <span class="font-bold"><?php echo $activeCount; ?></span> Active Alerts
            </div>
            <button class="text-sm opacity-90 hover:opacity-100">
              <i class="fas fa-times mr-1"></i> Dismiss
            </button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Main Content -->
  <main class="container mx-auto px-6 py-8">
    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600">Active Emergencies</p>
            <p class="text-3xl font-bold text-red-600"><?php echo $activeCount; ?></p>
          </div>
          <div class="bg-red-50 p-3 rounded-lg">
            <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600">Critical Level</p>
            <p class="text-3xl font-bold text-red-700"><?php echo $criticalCount; ?></p>
          </div>
          <div class="bg-red-100 p-3 rounded-lg">
            <i class="fas fa-skull-crossbones text-red-700 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600">Donors Responded</p>
            <p class="text-3xl font-bold text-blue-600">48</p>
          </div>
          <div class="bg-blue-50 p-3 rounded-lg">
            <i class="fas fa-users text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600">Units Collected</p>
            <p class="text-3xl font-bold text-green-600">127</p>
          </div>
          <div class="bg-green-50 p-3 rounded-lg">
            <i class="fas fa-heartbeat text-green-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions (For Donors) -->
    <?php if ($isDonor): ?>
      <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <a href="#matching"
            class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition duration-200 border-l-4 border-red-600">
            <div class="flex items-center space-x-3">
              <div class="bg-red-50 p-2 rounded-lg">
                <i class="fas fa-heartbeat text-red-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800">Matching Emergencies</h3>
                <p class="text-sm text-gray-600">View emergencies matching your blood type</p>
              </div>
            </div>
          </a>

          <a href="#centers"
            class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition duration-200 border-l-4 border-blue-600">
            <div class="flex items-center space-x-3">
              <div class="bg-blue-50 p-2 rounded-lg">
                <i class="fas fa-hospital text-blue-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800">Nearest Centers</h3>
                <p class="text-sm text-gray-600">Find donation centers near you</p>
              </div>
            </div>
          </a>

          <a href="#eligibility"
            class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition duration-200 border-l-4 border-green-600">
            <div class="flex items-center space-x-3">
              <div class="bg-green-50 p-2 rounded-lg">
                <i class="fas fa-clipboard-check text-green-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800">Check Eligibility</h3>
                <p class="text-sm text-gray-600">Verify you can donate now</p>
              </div>
            </div>
          </a>

          <a href="#schedule"
            class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition duration-200 border-l-4 border-orange-600">
            <div class="flex items-center space-x-3">
              <div class="bg-orange-50 p-2 rounded-lg">
                <i class="fas fa-calendar-alt text-orange-600"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800">Schedule Donation</h3>
                <p class="text-sm text-gray-600">Book your donation appointment</p>
              </div>
            </div>
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- Active Emergencies -->
    <section class="mb-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Active Emergencies</h2>
        <div class="text-sm text-gray-600">
          Showing <span class="font-bold text-red-600"><?php echo $activeCount; ?></span> active alerts
        </div>
      </div>

      <?php if (empty($activeEmergencies)): ?>
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-8 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-800 mb-2">No Active Emergencies</h3>
          <p class="text-gray-600">All blood requirements are currently met. Thank you for your support!</p>
        </div>
      <?php else: ?>
        <?php foreach ($activeEmergencies as $emergency): ?>
          <div class="emergency-card <?php echo $emergency['urgency']; ?> bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex flex-col lg:flex-row justify-between">
              <!-- Left Column: Emergency Info -->
              <div class="lg:w-2/3 mb-6 lg:mb-0 lg:pr-8">
                <div class="flex flex-wrap items-start justify-between mb-4">
                  <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo $emergency['title']; ?></h3>
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                      <div class="flex items-center text-gray-600">
                        <i class="fas fa-hospital mr-2"></i>
                        <span><?php echo $emergency['hospital']; ?></span>
                      </div>
                      <div class="flex items-center text-gray-600">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span><?php echo $emergency['location']; ?></span>
                      </div>
                      <div class="flex items-center text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        <span><?php echo $emergency['posted_time']; ?></span>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="urgency-badge bg-red-600"><?php echo strtoupper($emergency['urgency']); ?></span>
                    <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">
                      <i class="fas fa-clock mr-1"></i><?php echo $emergency['expires_in']; ?>
                    </span>
                  </div>
                </div>

                <!-- Blood Type & Requirements -->
                <div class="mb-6">
                  <div class="flex items-center space-x-4 mb-4">
                    <div>
                      <span class="blood-type-badge bg-red-600">
                        <i class="fas fa-tint mr-1"></i><?php echo $emergency['blood_type']; ?>
                      </span>
                    </div>
                    <div class="bg-red-50 px-4 py-2 rounded-lg">
                      <span class="font-bold text-red-700"><?php echo $emergency['units_needed']; ?></span>
                      <span class="text-gray-600 ml-1">units needed</span>
                    </div>
                    <div class="text-gray-600">
                      <i class="fas fa-user-md mr-1"></i>Posted by: <?php echo $emergency['posted_by']; ?>
                    </div>
                  </div>

                  <!-- Progress Bar -->
                  <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                      <span>Response Progress</span>
                      <span><?php echo $emergency['units_collected']; ?> / <?php echo $emergency['units_needed']; ?>
                        units</span>
                    </div>
                    <?php
                    $progress = ($emergency['units_collected'] / $emergency['units_needed']) * 100;
                    $progress = min($progress, 100);
                    ?>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                      <div class="bg-red-600 h-3 rounded-full progress-bar" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <div class="text-right text-sm text-gray-500 mt-1">
                      <?php echo round($progress); ?>% Complete
                    </div>
                  </div>

                  <!-- Donor Response -->
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center">
                      <div class="bg-white p-3 rounded-lg shadow-sm mr-4">
                        <div class="text-center">
                          <div class="text-2xl font-bold text-red-600"><?php echo $emergency['responded_donors']; ?></div>
                          <div class="text-xs text-gray-500">Donors Responded</div>
                        </div>
                      </div>
                      <div>
                        <p class="text-gray-700">
                          <i class="fas fa-users text-red-500 mr-1"></i>
                          <?php echo $emergency['responded_donors']; ?> donors have responded within
                          <?php echo $emergency['radius']; ?> radius
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Action Buttons -->
              <div class="lg:w-1/3 lg:pl-8 lg:border-l lg:border-gray-200">
                <div class="sticky top-6">
                  <?php if ($isDonor): ?>
                    <div class="space-y-4">
                      <button onclick="respondToEmergency(<?php echo $emergency['id']; ?>)"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-heartbeat"></i>
                        <span>I CAN HELP DONATE</span>
                      </button>

                      <a href="#"
                        class="block w-full border border-red-600 text-red-600 hover:bg-red-50 font-medium py-3 px-4 rounded-lg transition duration-200 text-center">
                        <i class="fas fa-share-alt mr-2"></i>Share Alert
                      </a>

                      <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-bold text-gray-800 mb-2">Your Eligibility</h4>
                        <div class="flex items-center text-green-600 mb-2">
                          <i class="fas fa-check-circle mr-2"></i>
                          <span class="font-medium">Eligible to donate</span>
                        </div>
                        <p class="text-sm text-gray-600">Last donation: 3 months ago</p>
                        <p class="text-sm text-gray-600">Blood type: O+ (Compatible)</p>
                      </div>
                    </div>
                  <?php elseif ($isHospital || $isAdmin): ?>
                    <div class="space-y-4">
                      <button onclick="viewEmergencyDetails(<?php echo $emergency['id']; ?>)"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-chart-line"></i>
                        <span>VIEW ANALYTICS</span>
                      </button>

                      <button onclick="updateEmergency(<?php echo $emergency['id']; ?>)"
                        class="w-full border border-yellow-500 text-yellow-600 hover:bg-yellow-50 font-medium py-3 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-edit mr-2"></i>Update Status
                      </button>

                      <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-bold text-gray-800 mb-2">Emergency Details</h4>
                        <div class="space-y-2 text-sm">
                          <div class="flex justify-between">
                            <span class="text-gray-600">Emergency ID:</span>
                            <span class="font-medium">#<?php echo $emergency['id']; ?></span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-gray-600">Radius:</span>
                            <span class="font-medium"><?php echo $emergency['radius']; ?></span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-gray-600">Donors Needed:</span>
                            <span
                              class="font-medium"><?php echo $emergency['units_needed'] - $emergency['units_collected']; ?>
                              more</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- Map Visualization -->
    <section class="mb-8">
      <div class="bg-gradient-to-r from-red-600 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
          <div>
            <h2 class="text-2xl font-bold mb-2">Emergency Map View</h2>
            <p>Active emergencies shown in real-time across the region</p>
          </div>
          <div class="mt-4 md:mt-0">
            <button
              class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-200">
              <i class="fas fa-expand-alt mr-2"></i>Full Screen View
            </button>
          </div>
        </div>

        <!-- Simplified Map -->
        <div class="bg-white bg-opacity-20 rounded-xl p-8 relative h-64 flex items-center justify-center">
          <div class="text-center">
            <i class="fas fa-map-marked-alt text-4xl mb-4 opacity-80"></i>
            <h3 class="text-xl font-bold mb-2">Live Emergency Map</h3>
            <p class="opacity-90">Showing <?php echo $activeCount; ?> active emergencies</p>
          </div>

          <!-- Map Markers -->
          <?php
          $markerPositions = [
            ['top' => '30%', 'left' => '40%', 'type' => 'critical', 'label' => 'Colombo'],
            ['top' => '60%', 'left' => '60%', 'type' => 'urgent', 'label' => 'Kandy'],
            ['top' => '40%', 'left' => '70%', 'type' => 'emergency', 'label' => 'Galle']
          ];
          foreach ($markerPositions as $marker):
            ?>
            <div class="absolute animate-pulse"
              style="top: <?php echo $marker['top']; ?>; left: <?php echo $marker['left']; ?>;">
              <div class="relative">
                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-red-500 rounded-full"></div>
                </div>
                <div
                  class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                  <?php echo $marker['label']; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto px-6">
      <div class="text-center">
        <div class="flex items-center justify-center space-x-2 mb-4">
          <div class="bg-red-600 p-2 rounded-lg">
            <i class="fas fa-broadcast-tower"></i>
          </div>
          <h3 class="text-xl font-bold">BloodSync Emergency System</h3>
        </div>
        <p class="text-gray-400 max-w-2xl mx-auto mb-6">
          Real-time emergency blood donation coordination system. Connecting donors with hospitals during critical
          shortages.
        </p>
        <div class="flex justify-center space-x-6">
          <a href="#" class="text-gray-400 hover:text-white transition duration-200">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white transition duration-200">
            <i class="fab fa-facebook"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white transition duration-200">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white transition duration-200">
            <i class="fab fa-linkedin"></i>
          </a>
        </div>
        <p class="text-gray-500 text-sm mt-6">© 2024 BloodSync. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- JavaScript -->
  <script>
    function respondToEmergency(id) {
      alert(`Thank you for responding to Emergency #${id}! A coordinator will contact you shortly.`);
      // In a real app, this would make an API call
    }

    function viewEmergencyDetails(id) {
      alert(`Viewing details for Emergency #${id}`);
      // In a real app, this would navigate to a detail page
    }

    function updateEmergency(id) {
      alert(`Updating status for Emergency #${id}`);
      // In a real app, this would open a modal
    }

    // Simple progress bar animation on load
    document.addEventListener('DOMContentLoaded', function () {
      const progressBars = document.querySelectorAll('.progress-bar');
      progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
          bar.style.width = width;
        }, 300);
      });
    });
  </script>
</body>

</html>