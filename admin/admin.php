<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// In a real application, you would fetch these from a database
$totalDonors = 1247;
$totalHospitals = 42;
$activeRequests = 18;
$totalDonations = 5489;
$availableBloodUnits = 287;

// Chart data
$donationsByMonth = [
  'Jan' => 420,
  'Feb' => 480,
  'Mar' => 510,
  'Apr' => 390,
  'May' => 520,
  'Jun' => 610
];

$bloodTypeDistribution = [
  'A+' => 32,
  'A-' => 7,
  'B+' => 25,
  'B-' => 6,
  'O+' => 28,
  'O-' => 5,
  'AB+' => 2,
  'AB-' => 1
];

$recentActivities = [
  ['user' => 'John Doe', 'action' => 'New donor registered', 'time' => '10 mins ago'],
  ['user' => 'General Hospital', 'action' => 'Blood request posted', 'time' => '25 mins ago'],
  ['user' => 'Jane Smith', 'action' => 'Appointment completed', 'time' => '1 hour ago'],
  ['user' => 'Central Blood Bank', 'action' => 'Inventory updated', 'time' => '2 hours ago'],
  ['user' => 'Admin', 'action' => 'System settings updated', 'time' => '3 hours ago']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #e11d48;
      --primary-light: #fecdd3;
      --secondary-color: #64748b;
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

    .blood-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 600;
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

    .chart-container {
      background: white;
      border-radius: 0.75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .progress-bar-custom {
      transition: width 0.5s ease;
    }

    .custom-select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
      background-position: right 0.75rem center;
      background-repeat: no-repeat;
      background-size: 1rem;
      padding-right: 2.5rem;
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
      <a href="admin.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
        <i class="fas fa-tachometer-alt w-6 text-center mr-3"></i>
        <span>Dashboard</span>
      </a>
      <a href="manage_donors.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-users w-6 text-center mr-3"></i>
        <span>Manage Donors</span>
      </a>


      <a href="hospital.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-hospital w-6 text-center mr-3"></i>
        <span>Hospitals</span>
      </a>
      <a href="blood_requests.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
        <i class="fas fa-clipboard-list w-6 text-center mr-3"></i>
        <span>Blood Requests</span>
      </a>

      <a href="inventory.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
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
          <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
          <p class="text-gray-600">Welcome back, Admin</p>
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
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Donors</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($totalDonors); ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 12%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last month</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
              <i class="fas fa-users text-red-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Active Requests Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Active Requests</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $activeRequests; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-red-500 text-sm flex items-center">
                  <i class="fas fa-exclamation-triangle mr-1"></i> 3 urgent
                </span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
              <i class="fas fa-clipboard-list text-orange-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Total Donations Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Donations</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($totalDonations); ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 8%
                </span>
                <span class="text-gray-400 text-sm ml-2">from last month</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
              <i class="fas fa-tint text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Available Blood Units Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Available Blood Units</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $availableBloodUnits; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Last updated: 2 hours ago</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-heartbeat text-green-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Monthly Donations Chart - ENHANCED -->
        <div class="lg:col-span-2 chart-container p-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div>
              <h3 class="text-xl font-bold text-gray-900">Monthly Donations</h3>
              <p class="text-gray-600 text-sm mt-1">Track donation trends over time</p>
            </div>
            <div class="flex items-center space-x-2 mt-3 sm:mt-0">
              <button
                class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-download mr-2"></i> Export
              </button>
              <div class="relative">
                <select
                  class="custom-select bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                  <option value="6">Last 6 Months</option>
                  <option value="12">Last Year</option>
                  <option value="24">Last 2 Years</option>
                  <option value="all">All Time</option>
                </select>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-4 mb-6">
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
              <span class="text-sm text-gray-600">Donations</span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
              <span class="text-sm text-gray-600">Target</span>
            </div>
            <div class="text-sm text-green-600 font-medium">
              <i class="fas fa-arrow-up mr-1"></i> 15% increase from last month
            </div>
          </div>

          <div class="relative h-64">
            <canvas id="donationsChart"></canvas>
          </div>

          <div class="mt-6 pt-6 border-t border-gray-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">610</p>
                <p class="text-sm text-gray-600">Current Month</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">3,420</p>
                <p class="text-sm text-gray-600">Last 6 Months</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">548</p>
                <p class="text-sm text-gray-600">Monthly Average</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-green-600">+8.2%</p>
                <p class="text-sm text-gray-600">Growth Rate</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Blood Type Distribution Chart - ENHANCED -->
        <div class="chart-container p-6">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h3 class="text-xl font-bold text-gray-900">Blood Type Distribution</h3>
              <p class="text-gray-600 text-sm mt-1">Across all registered donors</p>
            </div>
            <button class="text-red-600 hover:text-red-700">
              <i class="fas fa-redo text-sm"></i>
            </button>
          </div>

          <div class="relative h-64">
            <canvas id="bloodTypeChart"></canvas>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="text-center p-3 bg-red-50 rounded-lg">
              <p class="text-sm text-gray-600">Most Common</p>
              <p class="text-xl font-bold text-gray-900">A+</p>
              <p class="text-sm text-gray-600">32% of donors</p>
            </div>
            <div class="text-center p-3 bg-blue-50 rounded-lg">
              <p class="text-sm text-gray-600">Rarest Type</p>
              <p class="text-xl font-bold text-gray-900">AB-</p>
              <p class="text-sm text-gray-600">1% of donors</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tables and Additional Info -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activities -->
        <div class="lg:col-span-2 chart-container p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activities</h3>
            <a href="#" class="text-red-600 text-sm font-medium hover:text-red-700">View All</a>
          </div>

          <div class="space-y-4">
            <?php foreach ($recentActivities as $activity): ?>
              <div class="flex items-start border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3 mt-1">
                  <i class="fas fa-user text-red-600 text-sm"></i>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900"><?php echo $activity['user']; ?></p>
                  <p class="text-gray-600 text-sm"><?php echo $activity['action']; ?></p>
                </div>
                <div class="text-right">
                  <p class="text-gray-500 text-sm"><?php echo $activity['time']; ?></p>
                  <span class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full mt-1">
                    Completed
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Quick Actions & Blood Stock -->
        <div class="space-y-6">
          <!-- Quick Actions -->
          <div class="chart-container p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
              <button
                class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-3 rounded-lg flex items-center justify-center">
                <i class="fas fa-plus-circle mr-2"></i> Add New Donor
              </button>
              <button
                class="w-full border border-red-600 text-red-600 hover:bg-red-50 font-medium py-3 rounded-lg flex items-center justify-center">
                <i class="fas fa-hospital mr-2"></i> Manage Hospitals
              </button>
              <button
                class="w-full border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-3 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-bar mr-2"></i> Generate Report
              </button>
              <button
                class="w-full border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-3 rounded-lg flex items-center justify-center">
                <i class="fas fa-cog mr-2"></i> System Settings
              </button>
            </div>
          </div>

          <!-- Blood Stock Status -->
          <div class="chart-container p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Blood Stock Status</h3>
            <div class="space-y-4">
              <?php
              $bloodColors = [
                'A+' => ['bg' => 'bg-red-500', 'text' => 'text-white'],
                'B+' => ['bg' => 'bg-blue-500', 'text' => 'text-white'],
                'O+' => ['bg' => 'bg-yellow-500', 'text' => 'text-gray-900'],
                'AB+' => ['bg' => 'bg-green-500', 'text' => 'text-white'],
                'A-' => ['bg' => 'bg-red-300', 'text' => 'text-gray-900'],
                'B-' => ['bg' => 'bg-blue-300', 'text' => 'text-gray-900'],
                'O-' => ['bg' => 'bg-yellow-300', 'text' => 'text-gray-900'],
                'AB-' => ['bg' => 'bg-green-300', 'text' => 'text-gray-900']
              ];

              foreach ($bloodTypeDistribution as $type => $percentage):
                ?>
                <div>
                  <div class="flex justify-between items-center mb-1">
                    <span
                      class="blood-badge <?php echo $bloodColors[$type]['bg']; ?> <?php echo $bloodColors[$type]['text']; ?>">
                      <?php echo $type; ?>
                    </span>
                    <span class="text-sm font-medium text-gray-700"><?php echo $percentage; ?>%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="progress-bar-custom h-2 rounded-full <?php echo $bloodColors[$type]['bg']; ?>"
                      style="width: <?php echo $percentage; ?>%"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <footer class="mt-8 pt-6 border-t border-gray-200">
        <div class="flex flex-col md:flex-row justify-between items-center text-gray-500 text-sm">
          <p>BloodSync Admin Dashboard v1.0 &copy; <?php echo date('Y'); ?></p>
          <p>Last updated: <?php echo date('F j, Y, g:i a'); ?></p>
        </div>
      </footer>
    </main>
  </div>

  <script>
    // Donations Chart with enhanced features
    const donationsCtx = document.getElementById('donationsChart').getContext('2d');
    const donationsChart = new Chart(donationsCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode(array_keys($donationsByMonth)); ?>,
        datasets: [
          {
            label: 'Donations',
            data: <?php echo json_encode(array_values($donationsByMonth)); ?>,
            borderColor: '#e11d48',
            backgroundColor: 'rgba(225, 29, 72, 0.05)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#e11d48',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
          },
          {
            label: 'Target',
            data: [400, 450, 500, 450, 500, 550],
            borderColor: '#3b82f6',
            backgroundColor: 'transparent',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            tension: 0.2,
            pointRadius: 0
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            cornerRadius: 6,
            callbacks: {
              label: function (context) {
                return `${context.dataset.label}: ${context.parsed.y} donations`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false
            },
            ticks: {
              stepSize: 100,
              font: {
                size: 12
              }
            },
            title: {
              display: true,
              text: 'Number of Donations',
              font: {
                size: 12,
                weight: 'normal'
              },
              color: '#6b7280'
            }
          },
          x: {
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false
            },
            ticks: {
              font: {
                size: 12
              }
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        },
        elements: {
          line: {
            tension: 0.3
          }
        }
      }
    });

    // Add event listener for time range selection
    document.querySelector('select').addEventListener('change', function (e) {
      // In a real application, this would fetch new data based on the selected range
      console.log('Time range changed to:', e.target.value);

      // Update chart with new data
      // donationsChart.data.labels = newLabels;
      // donationsChart.data.datasets[0].data = newData;
      // donationsChart.update();
    });

    // Blood Type Chart
    const bloodTypeCtx = document.getElementById('bloodTypeChart').getContext('2d');
    const bloodTypeChart = new Chart(bloodTypeCtx, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode(array_keys($bloodTypeDistribution)); ?>,
        datasets: [{
          data: <?php echo json_encode(array_values($bloodTypeDistribution)); ?>,
          backgroundColor: [
            '#ef4444', '#f87171', '#3b82f6', '#60a5fa',
            '#eab308', '#fbbf24', '#10b981', '#34d399'
          ],
          borderWidth: 0,
          hoverOffset: 15
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `${context.label}: ${context.parsed}% of donors`;
              }
            }
          }
        },
        cutout: '70%',
        rotation: -90,
        circumference: 360,
        animation: {
          animateRotate: true,
          animateScale: true
        }
      }
    });

    // Mobile sidebar toggle (optional)
    document.addEventListener('DOMContentLoaded', function () {
      // Add mobile sidebar toggle functionality if needed
      const sidebar = document.querySelector('.sidebar');
      // You can add a hamburger menu for mobile if needed
    });
  </script>
</body>

</html>