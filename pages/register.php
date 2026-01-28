<?php
require_once '../config/database.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: dashboard.php');
    exit();
}
?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-2xl mx-auto">
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Join BloodSync</h1>
            <p class="text-gray-600 mb-8">Create your donor account and start saving lives</p>
            
            <form action="../handlers/register_process.php" method="POST">
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <!-- Full Name -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Full Name *</label>
                        <input type="text" name="fullname" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="John Doe" value="<?php echo $_SESSION['form_data']['fullname'] ?? ''; ?>">
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Email Address *</label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="john@example.com" value="<?php echo $_SESSION['form_data']['email'] ?? ''; ?>">
                    </div>
                    
                    <!-- Phone -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Phone Number *</label>
                        <input type="tel" name="phone" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="0712345678" value="<?php echo $_SESSION['form_data']['phone'] ?? ''; ?>">
                    </div>
                    
                    <!-- NIC -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">NIC Number *</label>
                        <input type="text" name="nic" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="199012345678" value="<?php echo $_SESSION['form_data']['nic'] ?? ''; ?>">
                    </div>
                    
                    <!-- Blood Type -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Blood Type *</label>
                        <select name="blood_type" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                            <option value="">Select Blood Type</option>
                            <option value="A+" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo ($_SESSION['form_data']['blood_type'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>
                    
                    <!-- Date of Birth -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Date of Birth *</label>
                        <input type="date" name="dob" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               value="<?php echo $_SESSION['form_data']['dob'] ?? ''; ?>">
                    </div>
                    
                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Address</label>
                        <input type="text" name="address" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="123 Main Street, Colombo" value="<?php echo $_SESSION['form_data']['address'] ?? ''; ?>">
                    </div>
                    
                    <!-- City -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">City</label>
                        <input type="text" name="city" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="Colombo" value="<?php echo $_SESSION['form_data']['city'] ?? 'Colombo'; ?>">
                    </div>
                    
                    <!-- Weight -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Weight (kg)</label>
                        <input type="number" name="weight" step="0.1" min="40" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                               placeholder="65" value="<?php echo $_SESSION['form_data']['weight'] ?? '65'; ?>">
                    </div>
                    
                    <!-- Password -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Password *</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none pr-12"
                                   placeholder="••••••••">
                            <button type="button" onclick="togglePassword('password')" 
                                    class="absolute right-3 top-3 text-gray-500 hover:text-gray-700 password-toggle">
                                <i id="password_icon" class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Confirm Password *</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirm_password" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none pr-12"
                                   placeholder="••••••••">
                            <button type="button" onclick="togglePassword('confirm_password')" 
                                    class="absolute right-3 top-3 text-gray-500 hover:text-gray-700 password-toggle">
                                <i id="confirm_password_icon" class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-lg transition duration-300">
                    Register as Donor
                </button>
                
                <p class="text-center text-gray-600 mt-6">
                    Already have an account? 
                    <a href="login.php" class="text-red-600 font-bold hover:underline">Login here</a>
                </p>

                <p class="text-center text-gray-600 mt-4">
                    Register as a hospital? 
                    <a href="register_hospital.php" class="text-red-600 font-bold hover:underline">Click here</a>
                </p>
                
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'ri-eye-off-line';
    } else {
        field.type = 'password';
        icon.className = 'ri-eye-line';
    }
}
</script>

<?php 
// Clear form data from session
unset($_SESSION['form_data']);
require_once 'includes/footer.php'; 
?>