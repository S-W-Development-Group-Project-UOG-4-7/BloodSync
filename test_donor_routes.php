<?php
// Test file for donor routes with mock data
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/controllers/DonorController.php';

// Mock session data for testing
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set mock donor session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'donor';
$_SESSION['email'] = 'test@example.com';
$_SESSION['name'] = 'Test Donor';

$controller = new DonorController();

echo "<h2>Testing Donor Controller Methods</h2>\n";

// Test profile method
echo "<h3>Testing Profile Method:</h3>\n";
$profile = $controller->profile();
if ($profile) {
    echo "<pre>" . print_r($profile, true) . "</pre>\n";
} else {
    echo "Profile not found or error occurred\n";
}

// Test screening method
echo "<h3>Testing Screening Method:</h3>\n";
$screenings = $controller->screening();
echo "<pre>" . print_r($screenings, true) . "</pre>\n";

// Test appointments method
echo "<h3>Testing Appointments Method:</h3>\n";
$appointments = $controller->appointments();
echo "<pre>" . print_r($appointments, true) . "</pre>\n";

// Test badges method
echo "<h3>Testing Badges Method:</h3>\n";
$badges = $controller->badges();
echo "<pre>" . print_r($badges, true) . "</pre>\n";

// Test eligibility check
echo "<h3>Testing Eligibility Check:</h3>\n";
$eligibility = $controller->checkEligibility(1);
echo "<pre>" . print_r($eligibility, true) . "</pre>\n";

// Test booking an appointment (with mock data)
echo "<h3>Testing Appointment Booking:</h3>\n";
$bookingResult = $controller->bookAppointment(1, 1, date('Y-m-d', strtotime('+1 week')), '10:00:00');
echo "<pre>" . print_r($bookingResult, true) . "</pre>\n";

// Test cancelling an appointment (with mock data)
// First, let's get an existing appointment ID
try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE donor_id = ? AND status = 'scheduled' LIMIT 1");
    $stmt->execute([1]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        echo "<h3>Testing Appointment Cancellation:</h3>\n";
        $cancellationResult = $controller->cancelAppointment($appointment['id'], 1);
        echo "<pre>" . print_r($cancellationResult, true) . "</pre>\n";
    } else {
        echo "No existing appointments to test cancellation\n";
    }
} catch (Exception $e) {
    echo "Error testing cancellation: " . $e->getMessage() . "\n";
}

// Clean up mock session
unset($_SESSION['user_id']);
unset($_SESSION['role']);
unset($_SESSION['email']);
unset($_SESSION['name']);

echo "<h3>All tests completed!</h3>\n";