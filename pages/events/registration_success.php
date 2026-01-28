<?php
require_once '../../config/database.php';
require_once '../includes/header.php';

$registration_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$registration_id || !$event_id) {
    header('Location: events.php');
    exit();
}

try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get registration details
    $stmt = $pdo->prepare("
        SELECT er.*, e.title as event_title, e.event_date, e.event_start_time, e.location
        FROM event_registrations er
        JOIN events e ON er.event_id = e.id
        WHERE er.id = ? AND er.event_id = ?
    ");
    $stmt->execute([$registration_id, $event_id]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        header('Location: events.php');
        exit();
    }
    
    // Format date and time
    $event_date = date('F j, Y', strtotime($registration['event_date']));
    $event_time = date('g:i A', strtotime($registration['event_start_time']));
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-6 md:px-20 py-12">
        <div class="max-w-2xl mx-auto">
            <!-- Success Card -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="ri-checkbox-circle-line text-4xl text-green-500"></i>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Registration Successful!</h1>
                <p class="text-gray-600 text-lg mb-8">
                    Thank you for registering to donate blood. Your registration has been confirmed.
                </p>
                
                <!-- Registration Details -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Registration Details</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Registration ID:</span>
                            <span class="font-medium">REG-<?php echo str_pad($registration['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Event:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($registration['event_title']); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Date & Time:</span>
                            <span class="font-medium"><?php echo $event_date; ?> at <?php echo $event_time; ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Location:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($registration['location']); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Name:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($registration['full_name']); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($registration['email']); ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-gray-600">Status:</span>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                Confirmed
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Important Information -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-bold text-yellow-800 mb-3">
                        <i class="ri-information-line mr-2"></i> Important Information
                    </h3>
                    <ul class="list-disc pl-5 space-y-2 text-left text-yellow-700">
                        <li>Please arrive 15 minutes before your scheduled time</li>
                        <li>Bring a valid photo ID (NIC/Passport/Driver's License)</li>
                        <li>Eat a light meal before donating</li>
                        <li>Drink plenty of fluids before and after donation</li>
                        <li>Wear comfortable clothing with sleeves that can be rolled up</li>
                    </ul>
                </div>
                
                <!-- Actions -->
                <div class="space-y-4">
                    <a href="event-detail.php?id=<?php echo $event_id; ?>" 
                       class="inline-block bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700">
                        <i class="ri-calendar-line mr-2"></i> View Event Details
                    </a>
                    <p class="text-gray-600">or</p>
                    <a href="events.php" class="inline-block text-red-600 hover:text-red-800 font-medium">
                        Browse More Events <i class="ri-arrow-right-line ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Share Section -->
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Spread the Word!</h3>
                <p class="text-gray-600 mb-6">
                    Help save more lives by sharing this event with friends and family.
                </p>
                <div class="flex justify-center space-x-4">
                    <button class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                        <i class="ri-facebook-line mr-2"></i> Share on Facebook
                    </button>
                    <button class="bg-blue-400 text-white px-6 py-3 rounded-lg hover:bg-blue-500">
                        <i class="ri-twitter-line mr-2"></i> Share on Twitter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>