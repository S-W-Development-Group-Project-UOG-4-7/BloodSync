<?php
// Use your existing database setup
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../autoload.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user ID from session
$userId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'donor';

// Mark notification as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    try {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id";
        Database::execute($sql, [
            'id' => $_GET['id'],
            'user_id' => $userId
        ]);
        $_SESSION['success_message'] = "Notification marked as read";
        header('Location: notifications.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating notification: " . $e->getMessage();
    }
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    try {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE";
        Database::execute($sql, ['user_id' => $userId]);
        $_SESSION['success_message'] = "All notifications marked as read";
        header('Location: notifications.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating notifications: " . $e->getMessage();
    }
}

// Get notifications
try {
    $sql = "SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
            LIMIT 50";
    $notifications = Database::fetchAll($sql, ['user_id' => $userId]);
} catch (Exception $e) {
    $notifications = [];
    $_SESSION['error_message'] = "Error loading notifications: " . $e->getMessage();
}

// Count unread notifications
$unreadCount = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unreadCount++;
    }
}

// Get donor profile for navigation
try {
    $sql = "SELECT d.* FROM donors d WHERE d.user_id = :user_id";
    $profile = Database::fetch($sql, ['user_id' => $userId]) ?: [];
} catch (Exception $e) {
    $profile = [];
}

// Page title
$pageTitle = "Notifications - BloodSync";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        .notification-unread {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }
        .notification-read {
            background-color: #ffffff;
            border-left: 4px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold text-red-600">
                        <i class="ri-heart-pulse-line mr-2"></i>BloodSync
                    </a>
                    <span class="ml-4 text-gray-500">Donor Portal</span>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-dashboard-line mr-1"></i>Dashboard
                    </a>
                    <a href="appointments.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-calendar-line mr-1"></i>Appointments
                    </a>
                    <a href="medical_reports.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-file-medical-line mr-1"></i>Medical Reports
                    </a>
                    <a href="history.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-history-line mr-1"></i>History
                    </a>
                    <a href="profile.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-user-line mr-1"></i>Profile
                    </a>
                    <a href="notifications.php" class="text-red-600 font-medium hover:text-red-700">
                        <i class="ri-notification-3-line mr-1"></i>Notifications
                        <?php if ($unreadCount > 0): ?>
                            <span class="ml-1 bg-red-500 text-white text-xs rounded-full px-2 py-1">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="../logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                                <i class="ri-logout-box-line mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                    <p class="text-gray-600">Stay updated with your appointment and report status</p>
                </div>
                <div class="flex space-x-3">
                    <?php if ($unreadCount > 0): ?>
                    <a href="notifications.php?mark_all_read=1" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="ri-check-double-line mr-2"></i>Mark All as Read
                    </a>
                    <?php endif; ?>
                    <a href="dashboard.php" 
                       class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-50">
                        <i class="ri-arrow-left-line mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Notifications Summary -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-notification-3-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Notifications</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($notifications); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-notification-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Unread</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $unreadCount; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-calendar-event-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Appointment Alerts</p>
                        <p class="text-3xl font-bold text-gray-900">
                            <?php 
                            $appointmentAlerts = array_filter($notifications, function($n) {
                                return (isset($n['type']) && strpos(strtolower($n['type']), 'appointment') !== false) || 
                                       (isset($n['title']) && strpos(strtolower($n['title']), 'appointment') !== false);
                            });
                            echo count($appointmentAlerts);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-12">
                    <i class="ri-notification-off-line text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No notifications yet</h3>
                    <p class="text-gray-600">You'll see important updates here about your appointments and reports</p>
                    <a href="dashboard.php" class="inline-block mt-6 text-red-600 hover:text-red-800 font-medium">
                        <i class="ri-arrow-left-line mr-2"></i>Back to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($notifications as $notification): 
                        $icon = 'notification-line';
                        $color = 'text-gray-600';
                        
                        if (isset($notification['type'])) {
                            $icon = match($notification['type']) {
                                'appointment' => 'calendar-event-line',
                                'report' => 'file-medical-line',
                                'verification' => 'user-check-line',
                                'donation' => 'heart-pulse-line',
                                'system' => 'information-line',
                                default => 'notification-line'
                            };
                            
                            $color = match($notification['type']) {
                                'appointment' => 'text-blue-600',
                                'report' => 'text-green-600',
                                'verification' => 'text-purple-600',
                                'donation' => 'text-red-600',
                                'system' => 'text-gray-600',
                                default => 'text-gray-600'
                            };
                        }
                    ?>
                    <div class="p-6 <?php echo (isset($notification['is_read']) && !$notification['is_read']) ? 'notification-unread' : 'notification-read'; ?> hover:bg-gray-50 transition">
                        <div class="flex">
                            <div class="flex-shrink-0 mr-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo str_replace('text-', 'bg-', $color); ?>-100 <?php echo $color; ?>">
                                    <i class="ri-<?php echo $icon; ?>"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?></h4>
                                        <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="ri-time-line mr-1"></i>
                                            <?php echo isset($notification['created_at']) ? date('F j, Y \a\t g:i A', strtotime($notification['created_at'])) : 'Unknown date'; ?>
                                            <?php if (isset($notification['is_read']) && !$notification['is_read']): ?>
                                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">New</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if (isset($notification['is_read']) && !$notification['is_read'] && isset($notification['id'])): ?>
                                            <a href="notifications.php?mark_read=1&id=<?php echo $notification['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" 
                                               title="Mark as read">
                                                <i class="ri-check-line"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (isset($notification['type']) && $notification['type'] == 'appointment' && isset($notification['metadata'])): 
                                            $metadata = json_decode($notification['metadata'] ?? '{}', true);
                                            if (isset($metadata['appointment_id'])): ?>
                                                <a href="appointments.php?view=<?php echo $metadata['appointment_id']; ?>" 
                                                   class="text-green-600 hover:text-green-800" 
                                                   title="View Appointment">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Clear All Button -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600">
                            Showing <?php echo count($notifications); ?> notification<?php echo count($notifications) != 1 ? 's' : ''; ?>
                        </p>
                        <div class="flex space-x-3">
                            <?php if ($unreadCount > 0): ?>
                            <a href="notifications.php?mark_all_read=1" 
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="ri-check-double-line mr-2"></i>Mark all as read
                            </a>
                            <?php endif; ?>
                            <a href="dashboard.php" class="text-gray-600 hover:text-gray-800 font-medium">
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Notification Types Info -->
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-6">
            <h3 class="font-bold text-gray-900 mb-4">Notification Types</h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                        <span class="font-medium">Appointments</span>
                    </div>
                    <p class="text-sm text-gray-600">Schedule updates, reminders, and changes</p>
                </div>
                
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3">
                            <i class="ri-file-medical-line"></i>
                        </div>
                        <span class="font-medium">Medical Reports</span>
                    </div>
                    <p class="text-sm text-gray-600">Report uploads, reviews, and approvals</p>
                </div>
                
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                            <i class="ri-user-check-line"></i>
                        </div>
                        <span class="font-medium">Account Updates</span>
                    </div>
                    <p class="text-sm text-gray-600">Verification status and profile changes</p>
                </div>
                
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                            <i class="ri-heart-pulse-line"></i>
                        </div>
                        <span class="font-medium">Donation Alerts</span>
                    </div>
                    <p class="text-sm text-gray-600">Donation records and eligibility updates</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <a href="dashboard.php" class="text-xl font-bold text-red-600">
                        <i class="ri-heart-pulse-line mr-2"></i>BloodSync
                    </a>
                    <p class="text-gray-600 text-sm mt-2">Saving lives through blood donation</p>
                </div>
                <div class="text-sm text-gray-600">
                    <p>&copy; <?php echo date('Y'); ?> BloodSync. All rights reserved.</p>
                    <p class="mt-1">Donor Portal v1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Auto-mark notifications as read when viewed for 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const unreadNotifications = document.querySelectorAll('.notification-unread');
            unreadNotifications.forEach(function(notification) {
                setTimeout(function() {
                    const markReadLink = notification.querySelector('a[href*="mark_read"]');
                    if (markReadLink) {
                        // You could auto-mark as read here, but let's keep it manual for now
                    }
                }, 3000);
            });
        });
    </script>
</body>
</html>