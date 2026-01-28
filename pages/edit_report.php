<?php
require_once __DIR__ . '/../autoload.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';
require_once  'MedicalReport.php';

Auth::requireAuth('login.php');

$user = Auth::getUser();
$profile = Auth::getDonorProfile();
$medicalReport = new MedicalReport();

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$report = $medicalReport->getById($report_id, $profile['id']);

if (!$report) {
    header('Location: medical_reports.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'],
        'notes' => $_POST['notes']
    ];
    
    if ($medicalReport->update($report_id, $profile['id'], $data)) {
        $_SESSION['success_message'] = 'Report updated successfully!';
        header('Location: medical_reports.php');
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to update report.';
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Medical Report</h1>
            <p class="text-gray-600">Update report information</p>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Report Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($report['title']); ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Report Type</label>
                    <input type="text" value="<?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                    <p class="text-xs text-gray-500 mt-1">Report type cannot be changed</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Report Date</label>
                    <input type="text" value="<?php echo date('F j, Y', strtotime($report['report_date'])); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Current File</label>
                    <div class="flex items-center space-x-2">
                        <i class="ri-file-text-line text-gray-600"></i>
                        <span class="text-gray-700"><?php echo htmlspecialchars($report['file_path']); ?></span>
                        <a href="download_report.php?id=<?php echo $report['id']; ?>" 
                            target="_blank"
                            class="text-sm text-red-600 hover:text-red-800 font-medium">
                            <i class="ri-download-line mr-1"></i> Download
                        </a>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Notes</label>
                    <textarea name="notes" rows="4" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"><?php echo htmlspecialchars($report['notes']); ?></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <a href="medical_reports.php" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-3 rounded-lg transition">
                        Update Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>