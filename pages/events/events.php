<?php
require_once '../../config/database.php';
require_once '../includes/header.php';
require_once '../includes/event_nav.php';

try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch only approved events from database
    $stmt = $pdo->prepare("
        SELECT e.*, 
               array_agg(er.requirement) as requirements
        FROM events e
        LEFT JOIN event_requirements er ON e.id = er.event_id
        WHERE e.approval_status = 'approved' 
          AND e.event_date >= CURRENT_DATE
          AND e.status = 'upcoming'
        GROUP BY e.id
        ORDER BY e.event_date ASC, e.event_start_time ASC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch past events (for past events tab)
    $stmt = $pdo->prepare("
        SELECT e.*
        FROM events e
        WHERE e.approval_status = 'approved' 
          AND e.event_date < CURRENT_DATE
        ORDER BY e.event_date DESC
        LIMIT 6
    ");
    $stmt->execute();
    $past_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $events = [];
    $past_events = [];
}
?>

<div class="bg-gray-50 min-h-screen">
    <!-- Events Header -->
    <section class="bg-red-600 text-white py-16">
        <div class="container mx-auto px-6 md:px-20 text-center">
            <h1 class="text-5xl font-bold">Blood Donation Events</h1>
            <p class="mt-4 text-xl">Join our community events and make a difference</p>
            
            <div class="mt-8 flex justify-center space-x-4">
                <a href="#upcoming" class="bg-white text-red-600 px-6 py-2 rounded-lg hover:bg-gray-100">
                    Upcoming Events (<?php echo count($events); ?>)
                </a>
                <a href="#past" class="border border-white px-6 py-2 rounded-lg hover:bg-red-700">
                    Past Events (<?php echo count($past_events); ?>)
                </a>
                <a href="create-event.php" class="border border-white px-6 py-2 rounded-lg hover:bg-red-700">
                    + Create Event
                </a>
            </div>
        </div>
    </section>

    <!-- Search and Filter -->
    <section class="py-8 px-6 md:px-20 bg-white">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="text-2xl font-bold text-gray-800">
                    Upcoming Events <span class="text-red-600">(<?php echo count($events); ?>)</span>
                </div>
                
                <form method="GET" class="flex space-x-4">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               placeholder="Search events..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               class="border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-red-500">
                        <i class="ri-search-line absolute right-3 top-3 text-gray-400"></i>
                    </div>
                    <select name="location" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500">
                        <option value="">All Locations</option>
                        <option value="Colombo" <?php echo ($_GET['location'] ?? '') == 'Colombo' ? 'selected' : ''; ?>>Colombo</option>
                        <option value="Kandy" <?php echo ($_GET['location'] ?? '') == 'Kandy' ? 'selected' : ''; ?>>Kandy</option>
                        <option value="Galle" <?php echo ($_GET['location'] ?? '') == 'Galle' ? 'selected' : ''; ?>>Galle</option>
                        <option value="Jaffna" <?php echo ($_GET['location'] ?? '') == 'Jaffna' ? 'selected' : ''; ?>>Jaffna</option>
                    </select>
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                        Filter
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Events Grid -->
    <section class="py-12 px-6 md:px-20" id="upcoming">
        <div class="container mx-auto">
            <?php if (empty($events)): ?>
                <div class="text-center py-16">
                    <i class="ri-calendar-line text-6xl text-gray-300 mb-6"></i>
                    <h3 class="text-2xl font-bold text-gray-700 mb-2">No Upcoming Events</h3>
                    <p class="text-gray-600 mb-8">There are currently no approved upcoming events.</p>
                    <a href="create-event.php" class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 inline-flex items-center">
                        <i class="ri-add-circle-line mr-2"></i>
                        Create an Event
                    </a>
                </div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($events as $event): 
                        // Format time
                        $start_time = date('g:i A', strtotime($event['event_start_time']));
                        $end_time = date('g:i A', strtotime($event['event_end_time']));
                        $time_duration = $start_time . ' - ' . $end_time;
                        
                        // Get requirements array
                        $requirements = $event['requirements'] ?? [];
                        if (is_string($requirements)) {
                            $requirements = json_decode($requirements, true) ?: [];
                        }
                    ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Event Image -->
                        <div class="relative">
                            <?php if ($event['image']): 
                                $image_src = $event['image'];
                                if (!preg_match('/^(https?:|\/)/', $image_src)) {
                                    $image_src = '/' . ltrim($image_src, '/');
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" 
                                alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                class="w-full h-56 object-cover">
                            <?php else: ?>
                            <div class="w-full h-56 bg-red-100 flex items-center justify-center">
                                <i class="ri-heart-pulse-line text-4xl text-red-300"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Event Content -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($event['title']); ?></h3>
                            
                            <p class="text-gray-600 mb-4 line-clamp-2">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </p>
                            
                            <!-- Event Details -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-gray-500">
                                    <i class="ri-user-3-line mr-3 text-red-500"></i>
                                    <span>Organized by: <strong><?php echo htmlspecialchars($event['organizer_name']); ?></strong></span>
                                </div>
                                <div class="flex items-center text-gray-500">
                                    <i class="ri-calendar-line mr-3 text-red-500"></i>
                                    <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                                </div>
                                <div class="flex items-center text-gray-500">
                                    <i class="ri-time-line mr-3 text-red-500"></i>
                                    <span><?php echo $time_duration; ?></span>
                                </div>
                                <div class="flex items-center text-gray-500">
                                    <i class="ri-map-pin-line mr-3 text-red-500"></i>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex justify-between items-center">
                                <a href="event-detail.php?id=<?php echo $event['id']; ?>" 
                                   class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                    View Details
                                </a>
                                <span class="text-green-600 text-sm font-medium">
                                    <i class="ri-check-double-line"></i> Approved
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Past Events Section -->
    <?php if (!empty($past_events)): ?>
    <section class="py-12 px-6 md:px-20 bg-gray-100" id="past">
        <div class="container mx-auto">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Past Events</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($past_events as $event): ?>
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                            <?php echo htmlspecialchars($event['description']); ?>
                        </p>
                        <div class="flex items-center text-gray-500 text-sm mb-4">
                            <i class="ri-calendar-line mr-2"></i>
                            <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="text-gray-500 text-sm">
                            <i class="ri-user-3-line mr-2"></i>
                            <span><?php echo htmlspecialchars($event['organizer_name']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Create Event CTA -->
    <section class="py-16 px-6 md:px-20 bg-red-50">
        <div class="container mx-auto text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Organizing a Blood Donation Event?</h2>
            <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                Create an event to reach thousands of potential donors in your area. 
                Our platform helps you organize, promote, and manage blood donation drives effectively.
            </p>
            <a href="create-event.php" 
               class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 inline-flex items-center">
                <i class="ri-add-circle-line mr-2"></i>
                Create Your Event
            </a>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>