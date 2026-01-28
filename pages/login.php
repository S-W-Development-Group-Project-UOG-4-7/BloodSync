<?php
require_once '../config/database.php';
require_once __DIR__ . '/../autoload.php'; // Make sure this file exists
require_once 'includes/header.php';
require_once 'includes/nav.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (Auth::isLoggedIn()) {
    $user = Auth::getUser();
    if ($user && $user['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($user && $user['role'] === 'hospital') {
        header('Location: hospital/hospital_dashboard.php');
    } elseif ($user) {
        header('Location: dashboard.php');
    }
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = Auth::login($email, $password);
    
    if ($result['success']) {
        // Get fresh user data after login
        $user = Auth::getUser();
        
        if (!$user) {
            $_SESSION['login_error'] = 'Unable to retrieve user information.';
        } else {
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['role'] === 'hospital') {
                header('Location: hospital/hospital_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        }
    } else {
        $_SESSION['login_error'] = $result['message'];
    }
}
?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto">
        
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $_SESSION['login_error']; ?>
                <?php unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
            <p class="text-gray-600 mb-8">Login to your account</p>
            
            <form method="POST" action="">
                <!-- Email -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Email Address</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                           placeholder="john@example.com">
                </div>
                
                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="login_password" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none pr-12"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePassword('login_password')" 
                                class="absolute right-3 top-3 text-gray-500 hover:text-gray-700 password-toggle">
                            <i id="login_password_icon" class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-lg transition duration-300">
                    Login
                </button>
                
                <p class="text-center text-gray-600 mt-6">
                    Don't have an account? 
                    <a href="register.php" class="text-red-600 font-bold hover:underline">Register here</a>
                </p>
            </form>
            
            <!-- Test Credentials -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 font-medium mb-2">Test Credentials:</p>
                <div class="text-xs text-gray-500 space-y-1">
                    <p>Admin: admin@bloodsync.com / admin123</p>
                    <p>Donor: donor@bloodsync.com / donor123</p>
                    <p>Hospital: hospital@bloodsync.com / hospital123</p>
                </div>
            </div>
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

<?php require_once 'includes/footer.php'; ?>