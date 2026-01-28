<?php
require_once __DIR__ . '/../autoload.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';

Auth::requireAuth('login.php');

$user = Auth::getUser();
$profile = Auth::getDonorProfile();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare update data
    $data = [
        ':first_name' => $_POST['first_name'],
        ':last_name' => $_POST['last_name'],
        ':contact_number' => $_POST['contact_number'],
        ':address' => $_POST['address'],
        ':city' => $_POST['city'],
        ':weight' => $_POST['weight'],
        ':blood_type' => $_POST['blood_type'],
        ':id' => $profile['id']
    ];
    
    $query = "UPDATE donors SET 
              first_name = :first_name,
              last_name = :last_name,
              contact_number = :contact_number,
              address = :address,
              city = :city,
              weight = :weight,
              blood_type = :blood_type,
              updated_at = CURRENT_TIMESTAMP
              WHERE id = :id";
    
    try {
        // Get database connection - assuming you have a Database class or config
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute($data)) {
            $_SESSION['success_message'] = 'Profile updated successfully!';
            
            // Also update the user's session/profile data
            if (isset($profile['user_id'])) {
                // Update session data if needed
                $_SESSION['user_profile'] = array_merge($_SESSION['user_profile'] ?? [], [
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'blood_type' => $_POST['blood_type']
                ]);
            }
            
            header('Location: profile.php');
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to update profile.';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
}

// Display any error messages
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
            <p class="text-gray-600">Update your personal information</p>
        </div>
        
        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <p class="font-medium"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Edit Form -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST">
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">Phone Number *</label>
                        <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($profile['contact_number']); ?>" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">Blood Type *</label>
                        <select name="blood_type" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="A+" <?php echo ($profile['blood_type'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo ($profile['blood_type'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo ($profile['blood_type'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo ($profile['blood_type'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="O+" <?php echo ($profile['blood_type'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo ($profile['blood_type'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                            <option value="AB+" <?php echo ($profile['blood_type'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo ($profile['blood_type'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">Weight (kg) *</label>
                        <input type="number" name="weight" min="40" max="200" step="0.1" 
                               value="<?php echo htmlspecialchars($profile['weight'] ?? '50.0'); ?>" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <p class="text-xs text-gray-500 mt-1">Minimum 40kg required for blood donation</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">City *</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-1">Address *</label>
                        <textarea name="address" rows="3" required 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Non-editable fields (for information only) -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium text-gray-700 mb-2">Information (Cannot be edited)</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">NIC</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['nic'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg" disabled>
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">Date of Birth</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>" 
                                   class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg" disabled>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">To change NIC or Date of Birth, please contact administrator.</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="profile.php" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-3 rounded-lg transition">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>