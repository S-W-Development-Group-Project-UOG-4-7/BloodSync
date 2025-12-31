<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Sample hospital data
$hospitals = [
  [
    'id' => 1,
    'name' => 'Colombo General Hospital',
    'location' => 'Colombo 08',
    'contact' => '011-2691111',
    'email' => 'info@colombohospital.lk',
    'blood_units' => 245,
    'status' => 'active',
    'registered_date' => '2023-01-15'
  ],
  [
    'id' => 2,
    'name' => 'Kandy Teaching Hospital',
    'location' => 'Kandy',
    'contact' => '081-2232266',
    'email' => 'admin@kandyhospital.lk',
    'blood_units' => 189,
    'status' => 'active',
    'registered_date' => '2023-02-20'
  ],
  [
    'id' => 3,
    'name' => 'Galle General Hospital',
    'location' => 'Galle',
    'contact' => '091-2232266',
    'email' => 'contact@gallehospital.lk',
    'blood_units' => 132,
    'status' => 'active',
    'registered_date' => '2023-03-10'
  ],
  [
    'id' => 4,
    'name' => 'Jaffna Teaching Hospital',
    'location' => 'Jaffna',
    'contact' => '021-2222266',
    'email' => 'info@jaffnahospital.lk',
    'blood_units' => 98,
    'status' => 'active',
    'registered_date' => '2023-04-05'
  ],
  [
    'id' => 5,
    'name' => 'Kurunegala General Hospital',
    'location' => 'Kurunegala',
    'contact' => '037-2222266',
    'email' => 'admin@kurunegalahospital.lk',
    'blood_units' => 176,
    'status' => 'inactive',
    'registered_date' => '2023-05-12'
  ],
  [
    'id' => 6,
    'name' => 'Badulla General Hospital',
    'location' => 'Badulla',
    'contact' => '055-2222266',
    'email' => 'contact@badullahospital.lk',
    'blood_units' => 87,
    'status' => 'active',
    'registered_date' => '2023-06-18'
  ],
  [
    'id' => 7,
    'name' => 'Ratnapura General Hospital',
    'location' => 'Ratnapura',
    'contact' => '045-2222266',
    'email' => 'info@ratnapurahospital.lk',
    'blood_units' => 112,
    'status' => 'active',
    'registered_date' => '2023-07-22'
  ],
  [
    'id' => 8,
    'name' => 'Anuradhapura Teaching Hospital',
    'location' => 'Anuradhapura',
    'contact' => '025-2222266',
    'email' => 'admin@anuradhapurahospital.lk',
    'blood_units' => 154,
    'status' => 'active',
    'registered_date' => '2023-08-30'
  ]
];

// Statistics
$totalHospitals = count($hospitals);
$activeHospitals = count(array_filter($hospitals, function ($h) {
  return $h['status'] === 'active'; }));
$totalBloodUnits = array_sum(array_column($hospitals, 'blood_units'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodSync - Manage Hospitals</title>
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

    .hospital-card {
      transition: all 0.3s ease;
    }

    .hospital-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
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
      <a href="hospitals.php" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700 active">
        <i class="fas fa-hospital w-6 text-center mr-3"></i>
        <span>Hospitals</span>
      </a>
      <a href="#" class="nav-link flex items-center px-4 py-3 rounded-lg text-gray-700">
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
          <h1 class="text-2xl font-bold text-gray-900">Manage Hospitals</h1>
          <p class="text-gray-600">View and manage all registered hospitals</p>
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
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Hospitals Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Hospitals</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalHospitals; ?></h3>
              <div class="flex items-center mt-2">
                <span class="text-green-500 text-sm flex items-center">
                  <i class="fas fa-arrow-up mr-1"></i> 3 new
                </span>
                <span class="text-gray-400 text-sm ml-2">this month</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
              <i class="fas fa-hospital text-blue-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Active Hospitals Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Active Hospitals</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $activeHospitals; ?></h3>
              <p class="text-gray-400 text-sm mt-2"><?php echo round(($activeHospitals / $totalHospitals) * 100); ?>% of
                total</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center">
              <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Total Blood Units Card -->
        <div class="stat-card bg-white rounded-xl shadow p-6 border-l-4 border-red-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-500 text-sm">Total Blood Units</p>
              <h3 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalBloodUnits; ?></h3>
              <p class="text-gray-400 text-sm mt-2">Across all hospitals</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
              <i class="fas fa-tint text-red-600 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Action Bar -->
      <div class="bg-white rounded-xl shadow mb-6 p-4">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <div class="w-full md:w-auto">
            <div class="relative">
              <input type="text" placeholder="Search hospitals..."
                class="w-full md:w-80 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
              <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
          </div>
          <div class="flex space-x-2">
            <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <button onclick="openAddHospitalModal()"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
              <i class="fas fa-plus mr-2"></i> Add Hospital
            </button>
          </div>
        </div>
      </div>

      <!-- Hospitals Table -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Hospital Name</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Location</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Contact</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Blood Units</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Status</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($hospitals as $hospital): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="py-4 px-4">
                    <div>
                      <p class="font-medium text-gray-900"><?php echo $hospital['name']; ?></p>
                      <p class="text-sm text-gray-500"><?php echo $hospital['email']; ?></p>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <p class="text-gray-700"><?php echo $hospital['location']; ?></p>
                  </td>
                  <td class="py-4 px-4">
                    <p class="text-gray-700"><?php echo $hospital['contact']; ?></p>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex items-center">
                      <span class="text-lg font-bold text-red-600"><?php echo $hospital['blood_units']; ?></span>
                      <span class="ml-2 text-sm text-gray-500">units</span>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <span
                      class="px-3 py-1 text-xs font-medium rounded-full 
                    <?php echo $hospital['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                      <?php echo ucfirst($hospital['status']); ?>
                    </span>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex space-x-2">
                      <button onclick="viewHospital(<?php echo $hospital['id']; ?>)"
                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button onclick="editHospital(<?php echo $hospital['id']; ?>)"
                        class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button onclick="deleteHospital(<?php echo $hospital['id']; ?>)"
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

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-100">
          <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
              Showing 1 to 8 of <?php echo $totalHospitals; ?> hospitals
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

      <!-- Hospital Cards Grid (Alternative View) -->
      <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Hospital Locations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <?php foreach (array_slice($hospitals, 0, 4) as $hospital): ?>
            <div class="hospital-card bg-white rounded-xl shadow p-6 border border-gray-100">
              <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center">
                  <i class="fas fa-hospital text-red-600 text-xl"></i>
                </div>
                <span
                  class="px-3 py-1 text-xs font-medium rounded-full 
                <?php echo $hospital['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                  <?php echo ucfirst($hospital['status']); ?>
                </span>
              </div>
              <h3 class="font-bold text-lg text-gray-900 mb-2"><?php echo $hospital['name']; ?></h3>
              <p class="text-gray-600 text-sm mb-4">
                <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                <?php echo $hospital['location']; ?>
              </p>
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-gray-500">Blood Units</p>
                  <p class="text-xl font-bold text-red-600"><?php echo $hospital['blood_units']; ?></p>
                </div>
                <button onclick="viewHospital(<?php echo $hospital['id']; ?>)"
                  class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100">
                  View Details
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Hospital Modal -->
  <div id="addHospitalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold text-gray-900">Add New Hospital</h3>
          <button onclick="closeAddHospitalModal()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <form id="hospitalForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Hospital Name</label>
              <input type="text" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
              <input type="text" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
              <input type="tel" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
              <input type="email" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Initial Blood Units</label>
              <input type="number" value="0"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
            <textarea rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
          </div>
        </form>
      </div>
      <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
        <button onclick="closeAddHospitalModal()"
          class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Cancel
        </button>
        <button onclick="saveHospital()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Save Hospital
        </button>
      </div>
    </div>
  </div>

  <script>
    // Modal Functions
    function openAddHospitalModal() {
      document.getElementById('addHospitalModal').classList.remove('hidden');
      document.getElementById('addHospitalModal').classList.add('flex');
    }

    function closeAddHospitalModal() {
      document.getElementById('addHospitalModal').classList.remove('flex');
      document.getElementById('addHospitalModal').classList.add('hidden');
      document.getElementById('hospitalForm').reset();
    }

    // Hospital Action Functions
    function viewHospital(id) {
      alert(`Viewing hospital details for ID: ${id}`);
      // In real app, redirect to hospital details page or show modal
    }

    function editHospital(id) {
      alert(`Editing hospital with ID: ${id}`);
      // In real app, open edit modal with pre-filled data
    }

    function deleteHospital(id) {
      if (confirm('Are you sure you want to delete this hospital?')) {
        alert(`Deleting hospital with ID: ${id}`);
        // In real app, send AJAX request to delete
      }
    }

    function saveHospital() {
      alert('Hospital saved successfully!');
      closeAddHospitalModal();
      // In real app, send form data via AJAX
    }

    // Search functionality
    document.querySelector('input[type="text"]').addEventListener('input', function (e) {
      const searchTerm = e.target.value.toLowerCase();
      // Filter table rows based on search term
      // Implement search logic here
    });

    // Close modal when clicking outside
    document.getElementById('addHospitalModal').addEventListener('click', function (e) {
      if (e.target === this) {
        closeAddHospitalModal();
      }
    });
  </script>
</body>

</html>