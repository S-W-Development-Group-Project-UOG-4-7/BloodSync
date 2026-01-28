<?php
require_once '../../config/database.php';
require_once '../includes/header.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $blood_type = $_POST['blood_type'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';
    
    // Basic validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($event_id) || $event_id <= 0) {
        $errors[] = 'Invalid event';
    }
    
    try {
        $pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if event exists and is approved
        $stmt = $pdo->prepare("SELECT id, title FROM events WHERE id = ? AND approval_status = 'approved'");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            $errors[] = 'Event not found or not approved';
        }
        
        // If no errors, save registration
        if (empty($errors)) {
            // Check if already registered with this email
            $stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ?");
            $stmt->execute([$event_id, $email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'You have already registered for this event with this email';
            } else {
                // Insert registration
                $stmt = $pdo->prepare("
                    INSERT INTO event_registrations 
                    (event_id, full_name, email, phone, blood_type, time_slot, status, registered_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW())
                ");
                
                $stmt->execute([
                    $event_id,
                    $full_name,
                    $email,
                    $phone,
                    $blood_type,
                    $time_slot
                ]);
                
                // Get the inserted ID for success message
                $registration_id = $pdo->lastInsertId();
                
                // Update registered_donors count
                $stmt = $pdo->prepare("
                    UPDATE events 
                    SET registered_donors = registered_donors + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$event_id]);
                
                // Success - redirect to success page or show success message
                header('Location: registration_success.php?id=' . $registration_id . '&event_id=' . $event_id);
                exit();
            }
        }
        
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
} else {
    // Not a POST request, redirect to events page
    header('Location: events.php');
    exit();
}

// If we get here, there were errors
require_once '../includes/header.php';
?>

<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-6 md:px-20 py-12">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <i class="ri-error-warning-line text-5xl text-red-500 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Registration Error</h1>
                <p class="text-gray-600">There were issues with your registration:</p>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <ul class="list-disc pl-5 space-y-2">
                    <?php foreach ($errors as $error): ?>
                    <li class="text-red-700"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="text-center space-y-4">
                <a href="event-detail.php?id=<?php echo htmlspecialchars($event_id); ?>" 
                   class="inline-block bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700">
                    <i class="ri-arrow-left-line mr-2"></i> Go Back to Event
                </a>
                <p class="text-gray-600">or</p>
                <a href="events.php" class="inline-block text-red-600 hover:text-red-800 font-medium">
                    Browse Other Events <i class="ri-arrow-right-line ml-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>