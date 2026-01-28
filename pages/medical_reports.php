<?php
require_once __DIR__ . '/../autoload.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';
require_once  'MedicalReport.php';

// Check authentication
Auth::requireAuth('login.php');

$user = Auth::getUser();
$profile = Auth::getDonorProfile();
$medicalReport = new MedicalReport();
$reports = $medicalReport->getByDonor($profile['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = __DIR__ . '/../uploads/medical_reports/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (isset($_FILES['medical_report']) && $_FILES['medical_report']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['medical_report'];
        
        // Validate file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error_message'] = 'Only PDF, JPEG, and PNG files are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $_SESSION['error_message'] = 'File size must be less than 5MB.';
        } else {
            // Generate unique filename
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'report_' . time() . '_' . $profile['id'] . '.' . $file_ext;
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                // Get user_id for the medical_reports table
                $user_id = $user['id'] ?? null;
                
                // Get file info
                $file_type = $file['type'];
                $file_size = $file['size'];
                $original_filename = basename($file['name']);
                
                // Save to database with all required fields
                $report_data = [
                    'donor_id' => $profile['id'],
                    'user_id' => $user_id,
                    'title' => $_POST['title'] ?? 'Medical Report',
                    'file_name' => $original_filename,  // Original file name
                    'file_path' => $filename,           // Stored file name
                    'file_type' => $file_type,
                    'file_size' => $file_size,
                    'report_date' => $_POST['report_date'] ?? date('Y-m-d'),
                    'report_type' => $_POST['report_type'] ?? 'health_check',
                    'notes' => $_POST['notes'] ?? ''
                ];
                
                if ($medicalReport->create($report_data)) {
                    $_SESSION['success_message'] = 'Medical report uploaded successfully!';
                    header('Location: medical_reports.php');
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Failed to save report to database.';
                    // Remove uploaded file if database save failed
                    if (file_exists($upload_dir . $filename)) {
                        unlink($upload_dir . $filename);
                    }
                }
            } else {
                $_SESSION['error_message'] = 'Failed to upload file.';
            }
        }
    } else {
        $_SESSION['error_message'] = 'Please select a valid file.';
        if (isset($_FILES['medical_report']['error'])) {
            $error_codes = [
                UPLOAD_ERR_INI_SIZE => 'File too large (server limit).',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit).',
                UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
                UPLOAD_ERR_NO_FILE => 'No file was selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
            ];
            $error_msg = $error_codes[$_FILES['medical_report']['error']] ?? 'Unknown error.';
            $_SESSION['error_message'] .= ' Error: ' . $error_msg;
        }
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $report_id = $_GET['delete'];
    if ($medicalReport->delete($report_id, $profile['id'])) {
        $_SESSION['success_message'] = 'Report deleted successfully!';
        header('Location: medical_reports.php');
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to delete report.';
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Medical Reports</h1>
            <p class="text-gray-600">Upload and manage your medical reports</p>
        </div>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Left Column: Upload Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Upload New Report</h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-1">Report Title *</label>
                                <input type="text" name="title" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-1">Report Type *</label>
                                <select name="report_type" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                    <option value="blood_test">Blood Test</option>
                                    <option value="health_check">Health Check</option>
                                    <option value="vaccination">Vaccination Record</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-1">Report Date *</label>
                                <input type="date" name="report_date" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-1">Medical Report File *</label>
                                <input type="file" name="medical_report" accept=".pdf,.jpg,.jpeg,.png" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (Max 5MB)</p>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Any additional information about this report..."></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-3 rounded-lg transition">
                            Upload Report
                        </button>
                    </form>
                </div>
                
                <!-- Reports Grid -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Your Medical Reports</h2>
                    
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-12">
                            <i class="ri-file-text-line text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600">No medical reports uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid md:grid-cols-2 gap-4">
                            <?php foreach ($reports as $report): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-red-300 transition">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($report['title']); ?></h3>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('F j, Y', strtotime($report['report_date'])); ?>
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <p class="text-sm text-gray-600">
                                            <i class="ri-file-text-line mr-1"></i>
                                            <?php echo htmlspecialchars($report['file_name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php 
                                            if ($report['file_size'] > 0) {
                                                echo 'Size: ' . round($report['file_size'] / 1024, 2) . ' KB';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    
                                    <?php if (!empty($report['notes'])): ?>
                                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($report['notes']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="flex space-x-2">
                                        <a href="download_report.php?id=<?php echo $report['id']; ?>" 
                                        target="_blank"
                                        class="text-sm text-red-600 hover:text-red-800 font-medium">
                                        <i class="ri-download-line mr-1"></i> Download
                                    </a>
                                        <a href="edit_report.php?id=<?php echo $report['id']; ?>" 
                                           class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            <i class="ri-edit-line mr-1"></i> Edit
                                        </a>
                                        <a href="medical_reports.php?delete=<?php echo $report['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this report?');"
                                           class="text-sm text-gray-600 hover:text-red-600 font-medium">
                                            <i class="ri-delete-bin-line mr-1"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column: Information -->
            <div class="space-y-6">
                <!-- Info Card -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="ri-information-line text-2xl text-blue-600 mr-2"></i>
                        <h3 class="text-lg font-bold text-blue-800">Information</h3>
                    </div>
                    <ul class="space-y-3 text-blue-700">
                        <li class="flex items-start">
                            <i class="ri-check-line text-blue-600 mr-2 mt-1"></i>
                            <span>Upload blood test results for verification</span>
                        </li>
                        <li class="flex items-start">
                            <i class="ri-check-line text-blue-600 mr-2 mt-1"></i>
                            <span>Keep health records up to date</span>
                        </li>
                        <li class="flex items-start">
                            <i class="ri-check-line text-blue-600 mr-2 mt-1"></i>
                            <span>Admin will review your reports</span>
                        </li>
                        <li class="flex items-start">
                            <i class="ri-check-line text-blue-600 mr-2 mt-1"></i>
                            <span>All fields are properly saved to database</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Stats Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Report Stats</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-500 text-sm">Total Reports</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($reports); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Latest Report</p>
                            <p class="text-lg font-medium text-gray-900">
                                <?php if (!empty($reports)): ?>
                                    <?php echo date('M d, Y', strtotime($reports[0]['report_date'])); ?>
                                <?php else: ?>
                                    None
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Status</p>
                            <p class="text-lg font-medium <?php echo (!empty($reports) && $reports[0]['status'] == 'approved') ? 'text-green-600' : 'text-yellow-600'; ?>">
                                <?php if (!empty($reports)): ?>
                                    <?php echo ucfirst($reports[0]['status']); ?>
                                <?php else: ?>
                                    No reports
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="dashboard.php" class="flex items-center text-red-600 hover:text-red-800">
                            <i class="ri-dashboard-line mr-2"></i> Dashboard
                        </a>
                        <a href="profile.php" class="flex items-center text-red-600 hover:text-red-800">
                            <i class="ri-user-line mr-2"></i> Profile
                        </a>
                        <a href="appointments.php" class="flex items-center text-red-600 hover:text-red-800">
                            <i class="ri-calendar-line mr-2"></i> Appointments
                        </a>
                        <a href="edit_profile.php" class="flex items-center text-red-600 hover:text-red-800">
                            <i class="ri-edit-line mr-2"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>