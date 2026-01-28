<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed");
        }
    }
    return $pdo;
}

$eventId = $_GET['id'] ?? 0;

if (!$eventId) {
    die('Invalid event ID');
}

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
            WHERE e.id = :id
            GROUP BY e.id";
    
    $stmt = getDB()->prepare($sql);
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        die('Event not found');
    }
} catch (Exception $e) {
    die('Error loading event details');
}

$eventDate = date('F j, Y', strtotime($event['event_date']));
$startTime = date('g:i A', strtotime($event['event_start_time']));
$endTime = date('g:i A', strtotime($event['event_end_time']));
$categoryArray = $event['categories'] ? explode(', ', $event['categories']) : [];
$requirements = $event['requirements'] ? explode('|', $event['requirements']) : [];
$progress = $event['target_donations'] > 0 
    ? min(100, ($event['registered_count'] / $event['target_donations']) * 100)
    : 0;
?>

<div class="p-4">
    <h3 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($event['title']); ?></h3>
    
    <!-- Categories -->
    <?php if (!empty($categoryArray)): ?>
    <div class="mb-4">
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
        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium <?php echo $color; ?> mr-2 mb-2">
            <?php echo htmlspecialchars($cat); ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Description -->
    <div class="mb-6">
        <h4 class="font-medium text-gray-900 mb-2">Description</h4>
        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($event['full_description'] ?: $event['description'])); ?></p>
    </div>
    
    <!-- Event Details -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Event Details</h4>
            <div class="space-y-3">
                <div class="flex items-center">
                    <i class="ri-calendar-line text-gray-400 mr-3"></i>
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium"><?php echo $eventDate; ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <i class="ri-time-line text-gray-400 mr-3"></i>
                    <div>
                        <p class="text-sm text-gray-500">Time</p>
                        <p class="font-medium"><?php echo $startTime; ?> - <?php echo $endTime; ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <i class="ri-map-pin-line text-gray-400 mr-3"></i>
                    <div>
                        <p class="text-sm text-gray-500">Location</p>
                        <p class="font-medium"><?php echo htmlspecialchars($event['location']); ?></p>
                        <?php if ($event['address']): ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($event['address']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Organizer Information</h4>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Organizer</p>
                    <p class="font-medium"><?php echo htmlspecialchars($event['organizer_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Contact</p>
                    <p class="font-medium"><?php echo htmlspecialchars($event['organizer_contact']); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($event['organizer_email']); ?></p>
                </div>
                <?php if ($event['organizer_description']): ?>
                <div>
                    <p class="text-sm text-gray-500">About Organizer</p>
                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($event['organizer_description']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Progress -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <h4 class="font-medium text-gray-900">Registration Progress</h4>
            <span class="text-sm text-gray-600">
                <?php echo $event['registered_count']; ?> / <?php echo $event['target_donations']; ?> donors
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-red-600 h-3 rounded-full" style="width: <?php echo $progress; ?>%"></div>
        </div>
        <p class="text-sm text-gray-600 mt-2">
            <?php echo $event['target_donations'] - $event['registered_count']; ?> spots remaining
        </p>
    </div>
    
    <!-- Requirements -->
    <?php if (!empty($requirements)): ?>
    <div class="mb-6">
        <h4 class="font-medium text-gray-900 mb-3">Donor Requirements</h4>
        <ul class="space-y-2">
            <?php foreach ($requirements as $req): ?>
            <li class="flex items-start">
                <i class="ri-check-line text-green-500 mt-1 mr-3"></i>
                <span class="text-gray-700"><?php echo htmlspecialchars($req); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Additional Info -->
    <?php if ($event['website'] || $event['notes']): ?>
    <div class="mt-6 pt-6 border-t border-gray-200">
        <h4 class="font-medium text-gray-900 mb-3">Additional Information</h4>
        <?php if ($event['website']): ?>
        <div class="mb-3">
            <p class="text-sm text-gray-500">Website</p>
            <a href="<?php echo htmlspecialchars($event['website']); ?>" 
               target="_blank" class="text-red-600 hover:text-red-800">
                <?php echo htmlspecialchars($event['website']); ?>
            </a>
        </div>
        <?php endif; ?>
        <?php if ($event['notes']): ?>
        <div>
            <p class="text-sm text-gray-500">Notes</p>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($event['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>