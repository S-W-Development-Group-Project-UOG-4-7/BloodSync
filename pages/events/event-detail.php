<?php
require_once '../../config/database.php';
require_once '../includes/header.php';
require_once '../includes/event_nav.php';

// Get event ID from URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch event from database
    $stmt = $pdo->prepare("
        SELECT e.*, 
               array_agg(er.requirement) as requirements
        FROM events e
        LEFT JOIN event_requirements er ON e.id = er.event_id
        WHERE e.id = :event_id 
          AND e.approval_status = 'approved'
        GROUP BY e.id
    ");
    $stmt->execute(['event_id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        // Event not found or not approved
        header('Location: events.php');
        exit();
    }
    
    // Format time
    $start_time = date('g:i A', strtotime($event['event_start_time']));
    $end_time = date('g:i A', strtotime($event['event_end_time']));
    $time_duration = $start_time . ' - ' . $end_time;
    
    // Get requirements
    $requirements = $event['requirements'] ?? [];
    if (is_string($requirements)) {
        $requirements = json_decode($requirements, true) ?: [];
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="bg-gray-50 min-h-screen">
    <!-- Event Header -->
    <div class="bg-red-600 text-white">
        <div class="container mx-auto px-6 md:px-20 py-8">
            <nav class="text-sm mb-4">
                <a href="events.php" class="hover:underline">Events</a> 
                <span class="mx-2">/</span>
                <span><?php echo htmlspecialchars($event['title']); ?></span>
            </nav>
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-4xl font-bold"><?php echo htmlspecialchars($event['title']); ?></h1>
                    <p class="mt-2 text-red-100">Organized by <?php echo htmlspecialchars($event['organizer_name']); ?></p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="#register" class="bg-white text-red-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">
                        Register to Donate
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 md:px-20 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Left Column - Event Details -->
            <div class="lg:w-2/3">
                <!-- Event Image -->
                <div class="rounded-xl overflow-hidden shadow-lg mb-8">
                    <?php 
                        $image_src = $event['image'] ?? '';
                        if (!empty($image_src)) {
                            // Add leading slash if it doesn't start with http or /
                            if (!preg_match('/^(https?:|\/)/', $image_src)) {
                            $image_src = '/' . ltrim($image_src, '/');
                            }
                    ?>
                        <img src="<?php echo htmlspecialchars($image_src); ?>" 
                        alt="<?php echo htmlspecialchars($event['title']); ?>" 
                        class="w-full h-96 object-cover">
                        <?php } else { ?>
                        <div class="w-full h-96 bg-red-100 flex items-center justify-center">
                        <i class="ri-heart-pulse-line text-8xl text-red-300"></i>
                        </div>
                    <?php } ?>
                </div>

                <!-- Event Stats -->
                <div class="bg-white rounded-xl shadow p-6 mb-8">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600"><?php echo $event['registered_donors']; ?></div>
                            <div class="text-gray-600">Registered Donors</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600"><?php echo $event['target_donations']; ?></div>
                            <div class="text-gray-600">Target Donations</div>
                        </div>
                        <div class="text-center">
                            <?php 
                                $days_left = max(0, floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24)));
                            ?>
                            <div class="text-3xl font-bold text-red-600"><?php echo $days_left; ?></div>
                            <div class="text-gray-600">Days Left</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600">3</div>
                            <div class="text-gray-600">Lives/Donation</div>
                        </div>
                    </div>
                </div>

                <!-- Event Description -->
                <div class="bg-white rounded-xl shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">About This Event</h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($event['description']); ?></p>
                        <?php if ($event['full_description']): ?>
                        <div class="whitespace-pre-line text-gray-700">
                            <?php echo htmlspecialchars($event['full_description']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requirements -->
                <?php if (!empty($requirements)): ?>
                <div class="bg-white rounded-xl shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Donor Requirements</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($requirements as $requirement): ?>
                        <div class="flex items-center">
                            <i class="ri-checkbox-circle-line text-green-500 mr-3"></i>
                            <span><?php echo htmlspecialchars($requirement); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="lg:w-1/3">
                <!-- Event Info Card -->
                <div class="bg-white rounded-xl shadow p-6 sticky top-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Event Details</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <i class="ri-calendar-line text-red-500 mt-1 mr-3"></i>
                            <div>
                                <div class="font-semibold">Date & Time</div>
                                <div class="text-gray-600">
                                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?><br>
                                    <?php echo $time_duration; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="ri-map-pin-line text-red-500 mt-1 mr-3"></i>
                            <div>
                                <div class="font-semibold">Location</div>
                                <div class="text-gray-600"><?php echo htmlspecialchars($event['location']); ?></div>
                                <?php if ($event['address']): ?>
                                <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($event['address']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="ri-user-3-line text-red-500 mt-1 mr-3"></i>
                            <div>
                                <div class="font-semibold">Organizer</div>
                                <div class="text-gray-600"><?php echo htmlspecialchars($event['organizer_name']); ?></div>
                                <?php if ($event['organizer_description']): ?>
                                <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($event['organizer_description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <i class="ri-customer-service-line text-red-500 mt-1 mr-3"></i>
                            <div>
                                <div class="font-semibold">Contact Information</div>
                                <div class="text-gray-600">
                                    <?php if ($event['organizer_contact']): ?>
                                    <div><?php echo htmlspecialchars($event['organizer_contact']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($event['organizer_email']): ?>
                                    <div><?php echo htmlspecialchars($event['organizer_email']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($event['website']): ?>
                                    <div><?php echo htmlspecialchars($event['website']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registration Form -->
                    <div class="mt-8 pt-8 border-t" id="register">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Register for This Event</h3>
                        <form method="POST" action="register_event.php" class="space-y-4">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Full Name *</label>
                                <input type="text" name="full_name" required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                                <input type="email" name="email" required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number *</label>
                                <input type="tel" name="phone" required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Blood Type</label>
                                <select name="blood_type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500">
                                    <option value="">Select Blood Type</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Preferred Time Slot</label>
                                <select name="time_slot" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500">
                                    <option value="9:00 AM - 10:00 AM">9:00 AM - 10:00 AM</option>
                                    <option value="10:00 AM - 11:00 AM">10:00 AM - 11:00 AM</option>
                                    <option value="11:00 AM - 12:00 PM">11:00 AM - 12:00 PM</option>
                                    <option value="2:00 PM - 3:00 PM">2:00 PM - 3:00 PM</option>
                                    <option value="3:00 PM - 4:00 PM">3:00 PM - 4:00 PM</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700">
                                Confirm Registration
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Share Event -->
                <div class="bg-white rounded-xl shadow p-6 mt-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Share This Event</h3>
                    <div class="flex space-x-4">
                        <button class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                            <i class="ri-facebook-line"></i> Facebook
                        </button>
                        <button class="flex-1 bg-blue-400 text-white py-2 rounded-lg hover:bg-blue-500">
                            <i class="ri-twitter-line"></i> Twitter
                        </button>
                        <button class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-600">
                            <i class="ri-whatsapp-line"></i> WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>