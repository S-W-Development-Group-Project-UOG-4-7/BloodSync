<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database configuration
require_once __DIR__ . '/../config/database.php';

// Database connection function
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Helper functions for database operations
function dbExecute($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function dbFetch($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function dbFetchAll($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get user ID from session
$userId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'donor';

// Get donor profile
try {
    $sql = "SELECT d.* FROM donors d WHERE d.user_id = :user_id";
    $profile = dbFetch($sql, ['user_id' => $userId]) ?: [];
} catch (Exception $e) {
    $profile = [];
}

// Get events with filters
$status = $_GET['status'] ?? 'upcoming';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = ["e.approval_status = 'approved'"];
$params = [];

if ($status === 'upcoming') {
    $where[] = "e.event_date >= CURRENT_DATE";
} elseif ($status === 'past') {
    $where[] = "e.event_date < CURRENT_DATE";
} elseif ($status === 'ongoing') {
    $where[] = "e.event_date = CURRENT_DATE AND e.status = 'ongoing'";
}

if ($category) {
    $where[] = "ecm.category_id IN (
        SELECT id FROM event_categories WHERE category_name = :category
    )";
    $params['category'] = $category;
}

if ($search) {
    $where[] = "(e.title ILIKE :search OR e.description ILIKE :search OR e.location ILIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get events
try {
    $sql = "SELECT 
                e.*,
                STRING_AGG(DISTINCT ec.category_name, ', ') as categories,
                STRING_AGG(DISTINCT er.requirement, '|') as requirements,
                (SELECT COUNT(*) FROM event_registrations er2 WHERE er2.event_id = e.id) as registered_count
            FROM events e
            LEFT JOIN event_category_mapping ecm ON e.id = ecm.event_id
            LEFT JOIN event_categories ec ON ecm.category_id = ec.id
            LEFT JOIN event_requirements er ON e.id = er.event_id
            $whereClause
            GROUP BY e.id
            ORDER BY e.event_date ASC, e.event_start_time ASC";
    
    $events = dbFetchAll($sql, $params);
} catch (Exception $e) {
    $events = [];
    $error = "Error loading events: " . $e->getMessage();
}

// Get event categories for filter
try {
    $categories = dbFetchAll("SELECT * FROM event_categories ORDER BY category_name");
} catch (Exception $e) {
    $categories = [];
}

// Check if donor is registered for events
$registeredEvents = [];
if (!empty($events)) {
    $eventIds = array_column($events, 'id');
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    
    try {
        $sql = "SELECT event_id FROM event_registrations 
                WHERE donor_id = ? AND event_id IN ($placeholders)";
        $params = array_merge([$profile['id'] ?? 0], $eventIds);
        
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        $registeredEvents = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Exception $e) {
        // Silently fail, registered events will be empty
    }
}

// Handle event registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $eventId = $_POST['event_id'] ?? 0;
    $timeSlot = $_POST['time_slot'] ?? '';
    
    if ($eventId && isset($profile['id'])) {
        try {
            // Check if already registered
            $checkSql = "SELECT id FROM event_registrations WHERE event_id = :event_id AND donor_id = :donor_id";
            $checkResult = dbFetch($checkSql, [
                'event_id' => $eventId,
                'donor_id' => $profile['id']
            ]);
            
            if ($checkResult) {
                $_SESSION['error_message'] = "You are already registered for this event";
            } else {
                // Register for event
                $insertSql = "INSERT INTO event_registrations 
                              (event_id, donor_id, user_id, full_name, email, phone, blood_type, time_slot, status)
                              VALUES (:event_id, :donor_id, :user_id, :full_name, :email, :phone, :blood_type, :time_slot, 'confirmed')";
                
                dbExecute($insertSql, [
                    'event_id' => $eventId,
                    'donor_id' => $profile['id'],
                    'user_id' => $userId,
                    'full_name' => $profile['first_name'] . ' ' . $profile['last_name'],
                    'email' => $userEmail,
                    'phone' => $profile['contact_number'] ?? '',
                    'blood_type' => $profile['blood_type'] ?? '',
                    'time_slot' => $timeSlot
                ]);
                
                // Update registered count
                $updateSql = "UPDATE events SET registered_donors = registered_donors + 1 WHERE id = :event_id";
                dbExecute($updateSql, ['event_id' => $eventId]);
                
                $_SESSION['success_message'] = "Successfully registered for the event!";
                
                // Create notification
                $event = dbFetch("SELECT title FROM events WHERE id = :id", ['id' => $eventId]);
                if ($event) {
                    $notifSql = "INSERT INTO notifications (user_id, type, title, message)
                                VALUES (:user_id, 'event', 'Event Registration', :message)";
                    dbExecute($notifSql, [
                        'user_id' => $userId,
                        'message' => 'You have successfully registered for: ' . $event['title']
                    ]);
                }
                
                header('Location: events.php');
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error registering for event: " . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = "Events - BloodSync";
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
        .event-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
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
                    <a href="events.php" class="text-red-600 font-medium hover:text-red-700">
                        <i class="ri-calendar-event-line mr-1"></i>Events
                    </a>
                    <a href="history.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-history-line mr-1"></i>History
                    </a>
                    <a href="profile.php" class="text-gray-700 hover:text-red-600">
                        <i class="ri-user-line mr-1"></i>Profile
                    </a>
                    <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
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
                    <h1 class="text-3xl font-bold text-gray-900">Blood Donation Events</h1>
                    <p class="text-gray-600">Find and register for upcoming blood donation events in your area</p>
                </div>
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="../admin/manage_events.php" 
                       class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="ri-settings-2-line mr-2"></i>Manage Events
                    </a>
                    <?php endif; ?>
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

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <form method="GET" class="grid md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Event Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="this.form.submit()">
                        <option value="upcoming" <?php echo $status == 'upcoming' ? 'selected' : ''; ?>>Upcoming Events</option>
                        <option value="ongoing" <?php echo $status == 'ongoing' ? 'selected' : ''; ?>>Ongoing Events</option>
                        <option value="past" <?php echo $status == 'past' ? 'selected' : ''; ?>>Past Events</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Category</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category_name']); ?>" 
                            <?php echo $category == $cat['category_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Search</label>
                    <div class="flex">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg"
                               placeholder="Search events...">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-r-lg">
                            <i class="ri-search-line"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-end">
                    <a href="events.php" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-center hover:bg-gray-50">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-calendar-event-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Events</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($events); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-user-heart-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Your Registrations</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($registeredEvents); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-group-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Upcoming</p>
                        <p class="text-3xl font-bold text-gray-900">
                            <?php 
                            $upcomingCount = array_filter($events, function($e) {
                                return strtotime($e['event_date']) >= time();
                            });
                            echo count($upcomingCount);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mr-4">
                        <i class="ri-history-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Past Events</p>
                        <p class="text-3xl font-bold text-gray-900">
                            <?php 
                            $pastCount = array_filter($events, function($e) {
                                return strtotime($e['event_date']) < time();
                            });
                            echo count($pastCount);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Grid -->
        <?php if (empty($events)): ?>
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <i class="ri-calendar-line text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No events found</h3>
                <p class="text-gray-600">
                    <?php if ($status === 'upcoming'): ?>
                        No upcoming events scheduled at the moment.
                    <?php elseif ($status === 'past'): ?>
                        No past events found.
                    <?php else: ?>
                        No events match your search criteria.
                    <?php endif; ?>
                </p>
                <?php if ($search || $category): ?>
                <a href="events.php" class="inline-block mt-4 text-red-600 hover:text-red-800 font-medium">
                    Clear filters and view all events
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($events as $event): 
                    $isRegistered = in_array($event['id'], $registeredEvents);
                    $isUpcoming = strtotime($event['event_date']) >= time();
                    $isToday = $event['event_date'] == date('Y-m-d');
                    $eventDate = date('M d, Y', strtotime($event['event_date']));
                    $startTime = date('g:i A', strtotime($event['event_start_time']));
                    $endTime = date('g:i A', strtotime($event['event_end_time']));
                    
                    $categoryArray = $event['categories'] ? explode(', ', $event['categories']) : [];
                    $requirements = $event['requirements'] ? explode('|', $event['requirements']) : [];
                    
                    // Calculate progress
                    $progress = $event['target_donations'] > 0 
                        ? min(100, ($event['registered_count'] / $event['target_donations']) * 100)
                        : 0;
                ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden event-card">
                    <!-- Event Image/Header -->
                    <div class="relative">
                        <?php if ($event['image']): ?>
                        <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>"
                             class="w-full h-48 object-cover">
                        <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-r from-red-500 to-red-700 flex items-center justify-center">
                            <i class="ri-heart-pulse-line text-6xl text-white opacity-50"></i>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            <?php if ($isRegistered): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                <i class="ri-check-line mr-1"></i>Registered
                            </span>
                            <?php elseif ($isToday): ?>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                                <i class="ri-time-line mr-1"></i>Today
                            </span>
                            <?php elseif (!$isUpcoming): ?>
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">
                                <i class="ri-history-line mr-1"></i>Completed
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Event Content -->
                    <div class="p-6">
                        <!-- Categories -->
                        <?php if (!empty($categoryArray)): ?>
                        <div class="mb-3">
                            <?php foreach ($categoryArray as $cat): 
                                $color = match($cat) {
                                    'Emergency Blood Drive' => 'bg-red-100 text-red-800',
                                    'Blood Donation Camp' => 'bg-blue-100 text-blue-800',
                                    'Awareness Campaign' => 'bg-green-100 text-green-800',
                                    'Corporate Drive' => 'bg-purple-100 text-purple-800',
                                    'University Drive' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>
                            <span class="category-badge <?php echo $color; ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Title -->
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($event['title']); ?></h3>
                        
                        <!-- Description -->
                        <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($event['description']); ?></p>
                        
                        <!-- Event Details -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-gray-600">
                                <i class="ri-calendar-line mr-2"></i>
                                <span><?php echo $eventDate; ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="ri-time-line mr-2"></i>
                                <span><?php echo $startTime; ?> - <?php echo $endTime; ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="ri-map-pin-line mr-2"></i>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Progress -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Registrations</span>
                                <span><?php echo $event['registered_count']; ?> / <?php echo $event['target_donations']; ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Requirements -->
                        <?php if (!empty($requirements)): ?>
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Requirements:</p>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <?php foreach (array_slice($requirements, 0, 3) as $req): ?>
                                <li class="flex items-center">
                                    <i class="ri-check-line text-green-500 mr-2"></i>
                                    <?php echo htmlspecialchars($req); ?>
                                </li>
                                <?php endforeach; ?>
                                <?php if (count($requirements) > 3): ?>
                                <li class="text-gray-500 text-sm">+<?php echo count($requirements) - 3; ?> more requirements</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <?php if ($isUpcoming && !$isRegistered): ?>
                            <button onclick="openRegistrationModal(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>')"
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition">
                                Register Now
                            </button>
                            <?php elseif ($isRegistered): ?>
                            <button class="flex-1 bg-green-100 text-green-800 px-4 py-2 rounded-lg font-medium cursor-default">
                                <i class="ri-check-line mr-2"></i>Registered
                            </button>
                            <?php else: ?>
                            <button class="flex-1 bg-gray-100 text-gray-800 px-4 py-2 rounded-lg font-medium cursor-default">
                                Event Completed
                            </button>
                            <?php endif; ?>
                            
                            <button onclick="openEventModal(<?php echo $event['id']; ?>)"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Registration Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <form method="POST" action="">
                <input type="hidden" name="event_id" id="modalEventId">
                <input type="hidden" name="register_event" value="1">
                
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-2" id="modalEventTitle"></h3>
                    <p class="text-gray-600 text-sm mb-4">Complete your registration for this event</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Preferred Time Slot</label>
                            <select name="time_slot" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                <option value="">Select Time Slot</option>
                                <option value="morning">Morning (9 AM - 12 PM)</option>
                                <option value="afternoon">Afternoon (2 PM - 5 PM)</option>
                                <option value="evening">Evening (5 PM - 8 PM)</option>
                                <option value="full_day">Full Day (Any Time)</option>
                            </select>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-800 mb-2">Registration Information</h4>
                            <p class="text-sm text-blue-700">
                                You will be registered as:<br>
                                <strong><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></strong><br>
                                Blood Type: <?php echo htmlspecialchars($profile['blood_type'] ?? 'Unknown'); ?><br>
                                Contact: <?php echo htmlspecialchars($profile['contact_number'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeRegistrationModal()" 
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                                Confirm Registration
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="eventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div id="eventModalContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="mt-6 text-center">
                <button onclick="closeEventModal()" 
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Close
                </button>
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
        // Registration Modal Functions
        function openRegistrationModal(eventId, eventTitle) {
            document.getElementById('modalEventId').value = eventId;
            document.getElementById('modalEventTitle').textContent = 'Register for: ' + eventTitle;
            document.getElementById('registrationModal').classList.remove('hidden');
        }
        
        function closeRegistrationModal() {
            document.getElementById('registrationModal').classList.add('hidden');
        }
        
        // Event Details Modal Functions
        function openEventModal(eventId) {
            // Load event details via AJAX
            fetch(`event_details.php?id=${eventId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('eventModalContent').innerHTML = html;
                    document.getElementById('eventModal').classList.remove('hidden');
                })
                .catch(error => {
                    document.getElementById('eventModalContent').innerHTML = 
                        '<div class="text-center py-8">Error loading event details</div>';
                    document.getElementById('eventModal').classList.remove('hidden');
                });
        }
        
        function closeEventModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const registrationModal = document.getElementById('registrationModal');
            const eventModal = document.getElementById('eventModal');
            
            if (event.target === registrationModal) {
                closeRegistrationModal();
            }
            if (event.target === eventModal) {
                closeEventModal();
            }
        }
    </script>
</body>
</html>