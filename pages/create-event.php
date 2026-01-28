<?php
require_once '../../config/database.php';
require_once '../includes/header.php';
require_once '../includes/event_nav.php';

// NO LOGIN REQUIRED - Guest access allowed
$user_id = null; // Guest users have no user ID
$user_email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate form data
    $organizer_name = trim($_POST['organizer_name'] ?? '');
    $organizer_nic = trim($_POST['organizer_nic'] ?? '');
    $organizer_email = trim($_POST['organizer_email'] ?? '');
    $organizer_contact = trim($_POST['organizer_contact'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $full_description = trim($_POST['full_description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $target_donations = intval($_POST['target_donations'] ?? 0);
    
    // Validate required fields
    if (empty($organizer_name)) $errors[] = "Organizer name is required";
    if (empty($organizer_nic)) $errors[] = "Organizer NIC is required";
    if (empty($organizer_email) || !filter_var($organizer_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid organizer email is required";
    if (empty($organizer_contact)) $errors[] = "Organizer contact is required";
    if (empty($title)) $errors[] = "Event title is required";
    if (empty($description)) $errors[] = "Event description is required";
    if (empty($event_date)) $errors[] = "Event date is required";
    if (empty($start_time)) $errors[] = "Start time is required";
    if (empty($end_time)) $errors[] = "End time is required";
    if (empty($location)) $errors[] = "Location is required";
    
    // Validate date
    if ($event_date && strtotime($event_date) < strtotime('today')) {
        $errors[] = "Event date cannot be in the past";
    }
    
    // Validate time
    if ($start_time && $end_time && strtotime($end_time) <= strtotime($start_time)) {
        $errors[] = "End time must be after start time";
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['event_image']['type'];
        $file_size = $_FILES['event_image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF, and WebP images are allowed";
        } elseif ($file_size > $max_size) {
            $errors[] = "Image size must be less than 5MB";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../../assets/uploads/events/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $destination)) {
                $image_path = '/assets/uploads/events/' . $filename;
            } else {
                    $errors[] = "Failed to upload image";
            }
        }
    } else {
        $errors[] = "Event image is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $pdo = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $pdo->beginTransaction();
            
            // Insert event with NULL created_by for guests
            $sql = "
                INSERT INTO events (
                    title, description, full_description, image,
                    organizer_name, organizer_nic, organizer_email, organizer_contact, organizer_description,
                    event_date, event_start_time, event_end_time,
                    location, address, city, target_donations,
                    status, approval_status, created_by, created_ip, created_at
                ) VALUES (
                    :title, :description, :full_description, :image,
                    :organizer_name, :organizer_nic, :organizer_email, :organizer_contact, :organizer_description,
                    :event_date, :start_time, :end_time,
                    :location, :address, :city, :target_donations,
                    'upcoming', 'pending', :created_by, :created_ip, CURRENT_TIMESTAMP
                )
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'full_description' => $full_description,
                'image' => $image_path,
                'organizer_name' => $organizer_name,
                'organizer_nic' => $organizer_nic,
                'organizer_email' => $organizer_email,
                'organizer_contact' => $organizer_contact,
                'organizer_description' => $_POST['organizer_description'] ?? '',
                'event_date' => $event_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'location' => $location,
                'address' => $address,
                'city' => $city,
                'target_donations' => $target_donations,
                'created_by' => $user_id, // NULL for guests
                'created_ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            $event_id = $pdo->lastInsertId();
            
            // Add event requirements if provided
            if (isset($_POST['requirements']) && is_array($_POST['requirements'])) {
                foreach ($_POST['requirements'] as $requirement) {
                    $req = trim($requirement);
                    if (!empty($req)) {
                        $sql = "INSERT INTO event_requirements (event_id, requirement) VALUES (:event_id, :requirement)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute(['event_id' => $event_id, 'requirement' => $req]);
                    }
                }
            }
            
            // Add default requirements if none provided
            $check_requirements = $pdo->query("SELECT COUNT(*) FROM event_requirements WHERE event_id = $event_id")->fetchColumn();
            if ($check_requirements == 0) {
                $default_requirements = [
                    'Age 18-65 years',
                    'Weight > 50kg',
                    'Good general health',
                    'No recent illnesses',
                    'Valid government ID required'
                ];
                
                foreach ($default_requirements as $requirement) {
                    $sql = "INSERT INTO event_requirements (event_id, requirement) VALUES (:event_id, :requirement)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['event_id' => $event_id, 'requirement' => $requirement]);
                }
            }
            
            // Create notification for admin
            $sql = "
                INSERT INTO admin_notifications (
                    notification_type, title, message, related_id, related_type, priority
                ) VALUES (
                    'event_submission', 'New Event Submitted', 
                    :message, :event_id, 'event', 'high'
                )
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'message' => "A new event '{$title}' has been submitted by {$organizer_name} (Guest).",
                'event_id' => $event_id
            ]);
            
            $pdo->commit();
            
            // Send confirmation email to organizer
            $to = $organizer_email;
            $subject = "Your Blood Donation Event Submission Confirmation";
            $message = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { padding: 30px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-top: none; }
                        .event-details { background-color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Event Submission Confirmed</h1>
                        </div>
                        <div class='content'>
                            <h2>Thank you for submitting your blood donation event!</h2>
                            <p>We've received your event submission and it's now under review by our admin team.</p>
                            
                            <div class='event-details'>
                                <h3>Event Details:</h3>
                                <p><strong>Event Title:</strong> {$title}</p>
                                <p><strong>Date:</strong> " . date('F j, Y', strtotime($event_date)) . "</p>
                                <p><strong>Time:</strong> {$start_time} - {$end_time}</p>
                                <p><strong>Location:</strong> {$location}</p>
                                <p><strong>Organizer:</strong> {$organizer_name}</p>
                                <p><strong>Target Donations:</strong> {$target_donations} units</p>
                            </div>
                            
                            <h3>What happens next?</h3>
                            <ol>
                                <li>Our admin team will review your event within 24-48 hours</li>
                                <li>You'll receive another email once your event is approved</li>
                                <li>If we need additional information, we'll contact you</li>
                                <li>Once approved, your event will appear on our public events page</li>
                            </ol>
                            
                            <p><strong>Important:</strong> Please keep this email for your records. Your event ID is: <code>{$event_id}</code></p>
                            
                            <p>If you have any questions or need to make changes to your submission, please reply to this email.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message from BloodSync Event System.</p>
                            <p>&copy; " . date('Y') . " BloodSync. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: BloodSync Events <events@bloodsync.com>" . "\r\n";
            $headers .= "Reply-To: noreply@bloodsync.com" . "\r\n";
            
            mail($to, $subject, $message, $headers);
            
            // Show success message
            $success = true;
            $success_message = "Event submitted successfully! We've sent a confirmation email to {$organizer_email}. Your event will be visible after admin approval.";
            
        } catch (PDOException $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4 md:px-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-10 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Create Blood Donation Event</h1>
            <p class="text-gray-600 text-lg">Submit your event for approval. No registration required!</p>
        </div>
        
        <!-- Guest Info Alert -->
        <div class="mb-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start">
                <i class="ri-information-line text-blue-600 text-2xl mr-3 mt-1"></i>
                <div>
                    <h3 class="font-bold text-blue-800 mb-2">Guest Event Submission</h3>
                    <p class="text-blue-700 mb-2">You're submitting this event as a guest. No registration or login required!</p>
                    <ul class="text-blue-700 space-y-1 text-sm">
                        <li class="flex items-center"><i class="ri-check-line mr-2 text-green-500"></i> All fields marked with * are required</li>
                        <li class="flex items-center"><i class="ri-check-line mr-2 text-green-500"></i> You'll receive email confirmation immediately</li>
                        <li class="flex items-center"><i class="ri-check-line mr-2 text-green-500"></i> Admin approval typically takes 24-48 hours</li>
                        <li class="flex items-center"><i class="ri-check-line mr-2 text-green-500"></i> Already have an account? <a href="../login.php?redirect=create-event.php" class="font-bold underline ml-1">Login here</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Success Message -->
        <?php if (isset($success) && $success): ?>
        <div class="mb-8 bg-green-50 border border-green-200 text-green-800 rounded-xl p-6 text-center">
            <div class="flex items-center justify-center mb-4">
                <i class="ri-checkbox-circle-line text-4xl text-green-500"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Event Submitted Successfully!</h3>
            <p class="mb-4"><?php echo htmlspecialchars($success_message); ?></p>
            <div class="flex justify-center space-x-4">
                <a href="events.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                    View All Events
                </a>
                <a href="create-event.php" class="border border-green-600 text-green-600 hover:bg-green-50 px-6 py-2 rounded-lg">
                    Create Another Event
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="mb-8 bg-red-50 border border-red-200 text-red-800 rounded-xl p-6">
            <div class="flex items-center mb-4">
                <i class="ri-error-warning-line text-2xl mr-3"></i>
                <h3 class="text-xl font-bold">Please fix the following errors:</h3>
            </div>
            <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Event Creation Form (only show if not successful) -->
        <?php if (!isset($success) || !$success): ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-8">
            <!-- Organizer Information Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <i class="ri-user-3-line"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Organizer Information</h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="organizer_name">
                            Organizer Name *
                        </label>
                        <input type="text" 
                               id="organizer_name" 
                               name="organizer_name"
                               value="<?php echo htmlspecialchars($organizer_name ?? ''); ?>"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        <p class="text-gray-500 text-xs mt-1">Name of the person or organization</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="organizer_nic">
                            Organizer NIC *
                        </label>
                        <input type="text" 
                               id="organizer_nic" 
                               name="organizer_nic"
                               value="<?php echo htmlspecialchars($organizer_nic ?? ''); ?>"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="organizer_email">
                            Organizer Email *
                        </label>
                        <input type="email" 
                               id="organizer_email" 
                               name="organizer_email"
                               value="<?php echo htmlspecialchars($organizer_email ?? ''); ?>"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        <p class="text-gray-500 text-xs mt-1">We'll send confirmation to this email</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="organizer_contact">
                            Organizer Contact Number *
                        </label>
                        <input type="tel" 
                               id="organizer_contact" 
                               name="organizer_contact"
                               value="<?php echo htmlspecialchars($organizer_contact ?? ''); ?>"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        <p class="text-gray-500 text-xs mt-1">Phone number for event inquiries</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="organizer_description">
                            Organizer Description
                        </label>
                        <textarea 
                            id="organizer_description" 
                            name="organizer_description"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                            placeholder="Brief description about your organization or yourself..."
                        ><?php echo htmlspecialchars($_POST['organizer_description'] ?? ''); ?></textarea>
                        <p class="text-gray-500 text-xs mt-1">Tell us about your organization (optional)</p>
                    </div>
                </div>
            </div>
            
            <!-- Event Information Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <i class="ri-calendar-event-line"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Event Information</h2>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                            Event Title *
                        </label>
                        <input type="text" 
                               id="title" 
                               name="title"
                               value="<?php echo htmlspecialchars($title ?? ''); ?>"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                               placeholder="e.g., Annual Blood Donation Camp">
                        <p class="text-gray-500 text-xs mt-1">Make it descriptive and appealing</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                            Short Description *
                        </label>
                        <textarea 
                            id="description" 
                            name="description"
                            rows="3"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                            placeholder="Brief description that will appear on the event card..."
                        ><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        <p class="text-gray-500 text-xs mt-1">Maximum 200 characters</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="full_description">
                            Full Event Description
                        </label>
                        <textarea 
                            id="full_description" 
                            name="full_description"
                            rows="6"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                            placeholder="Detailed description with all the information about the event..."
                        ><?php echo htmlspecialchars($full_description ?? ''); ?></textarea>
                        <p class="text-gray-500 text-xs mt-1">Include all details like facilities, benefits, etc.</p>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="event_date">
                                Event Date *
                            </label>
                            <input type="date" 
                                   id="event_date" 
                                   name="event_date"
                                   value="<?php echo htmlspecialchars($event_date ?? ''); ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                                Start Time *
                            </label>
                            <input type="time" 
                                   id="start_time" 
                                   name="start_time"
                                   value="<?php echo htmlspecialchars($start_time ?? '09:00'); ?>"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">
                                End Time *
                            </label>
                            <input type="time" 
                                   id="end_time" 
                                   name="end_time"
                                   value="<?php echo htmlspecialchars($end_time ?? '17:00'); ?>"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="target_donations">
                            Target Donations (Units)
                        </label>
                        <input type="number" 
                               id="target_donations" 
                               name="target_donations"
                               value="<?php echo htmlspecialchars($target_donations ?? 100); ?>"
                               min="1"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200">
                        <p class="text-gray-500 text-xs mt-1">Estimated number of blood units you aim to collect</p>
                    </div>
                </div>
            </div>
            
            <!-- Event Location Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <i class="ri-map-pin-line"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Event Location</h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                            Location Name *
                        </label>
                        <input type="text" 
                               id="location" 
                               name="location"
                               value="<?php echo htmlspecialchars($location ?? ''); ?>"
                               required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                               placeholder="e.g., Community Center, Hospital Name">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="city">
                            City
                        </label>
                        <input type="text" 
                               id="city" 
                               name="city"
                               value="<?php echo htmlspecialchars($city ?? ''); ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                               placeholder="e.g., Colombo">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                            Full Address
                        </label>
                        <textarea 
                            id="address" 
                            name="address"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200"
                            placeholder="Complete address for the event venue..."
                        ><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                        <p class="text-gray-500 text-xs mt-1">Include street address, landmarks, etc.</p>
                    </div>
                </div>
            </div>
            
            <!-- Event Image Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <i class="ri-image-line"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Event Image</h2>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="event_image">
                        Upload Event Banner *
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-red-300 transition-colors">
                        <div class="mb-4">
                            <i class="ri-image-add-line text-4xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-600 mb-2">Click to upload or drag and drop</p>
                        <p class="text-gray-500 text-sm mb-4">PNG, JPG, GIF or WebP. Max 5MB.</p>
                        <input type="file" 
                               id="event_image" 
                               name="event_image"
                               accept="image/*"
                               required
                               class="hidden"
                               onchange="previewImage(event)">
                        <button type="button" 
                                onclick="document.getElementById('event_image').click()"
                                class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
                            Choose Image
                        </button>
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="imagePreview" class="mt-4 hidden">
                        <p class="text-gray-700 text-sm font-bold mb-2">Preview:</p>
                        <div class="border border-gray-300 rounded-lg p-4">
                            <img id="preview" class="max-h-64 mx-auto rounded-lg">
                            <button type="button" 
                                    onclick="removeImage()"
                                    class="mt-4 text-red-600 hover:text-red-800 text-sm font-medium">
                                <i class="ri-delete-bin-line mr-1"></i> Remove Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Requirements Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <i class="ri-file-list-line"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Donor Requirements</h2>
                </div>
                
                <div>
                    <p class="text-gray-600 mb-4">Specify requirements for donors attending this event:</p>
                    
                    <div id="requirementsContainer" class="space-y-3">
                        <!-- Default requirement -->
                        <div class="flex items-center">
                            <input type="text" 
                                   name="requirements[]" 
                                   value="Age 18-65 years"
                                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500"
                                   placeholder="e.g., Age 18-65 years">
                            <button type="button" 
                                    onclick="removeRequirement(this)"
                                    class="ml-2 text-red-600 hover:text-red-800">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="text" 
                                   name="requirements[]" 
                                   value="Weight > 50kg"
                                   class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500"
                                   placeholder="e.g., Weight > 50kg">
                            <button type="button" 
                                    onclick="removeRequirement(this)"
                                    class="ml-2 text-red-600 hover:text-red-800">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" 
                            onclick="addRequirement()"
                            class="mt-4 flex items-center text-red-600 hover:text-red-800 font-medium">
                        <i class="ri-add-line mr-2"></i> Add Another Requirement
                    </button>
                </div>
            </div>
            
            <!-- Form Submission -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3">
                        <i class="ri-send-plane-line"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Submit for Approval</h2>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <div class="flex items-start">
                        <i class="ri-information-line text-blue-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <h3 class="font-bold text-blue-800 mb-2">Important Information</h3>
                            <ul class="text-blue-700 space-y-1 list-disc pl-5">
                                <li>Your event will be reviewed by our admin team before being published</li>
                                <li>Approval typically takes 24-48 hours</li>
                                <li>You'll receive an email notification once approved or if changes are needed</li>
                                <li>Ensure all information is accurate before submitting</li>
                                <li>By submitting, you confirm that you have permission to use the uploaded image</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Simple CAPTCHA (Optional - Add Google reCAPTCHA for production) -->
                <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="ri-shield-check-line text-yellow-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-bold text-yellow-800 mb-2">Security Check</h4>
                            <p class="text-yellow-700 text-sm mb-2">Are you a human?</p>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="captcha" value="yes" required class="mr-2">
                                    <span class="text-yellow-700">Yes, I'm human</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="captcha" value="no" class="mr-2">
                                    <span class="text-yellow-700">No</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between">
                    <div class="mb-4 md:mb-0">
                        <p class="text-gray-600">
                            By submitting, you agree to our 
                            <a href="terms.php" class="text-red-600 hover:underline">Event Guidelines</a> and 
                            <a href="privacy.php" class="text-red-600 hover:underline">Privacy Policy</a>
                        </p>
                    </div>
                    
                    <div class="flex space-x-4">
                        <a href="events.php" 
                           class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-8 py-3 rounded-lg font-medium">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-medium flex items-center">
                            <i class="ri-send-plane-line mr-2"></i>
                            Submit for Approval
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Image preview functionality
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    const input = document.getElementById('event_image');
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    input.value = '';
    preview.src = '';
    previewContainer.classList.add('hidden');
}

// Requirements functionality
function addRequirement() {
    const container = document.getElementById('requirementsContainer');
    const div = document.createElement('div');
    div.className = 'flex items-center';
    div.innerHTML = `
        <input type="text" 
               name="requirements[]" 
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-red-500"
               placeholder="e.g., Valid government ID required">
        <button type="button" 
                onclick="removeRequirement(this)"
                class="ml-2 text-red-600 hover:text-red-800">
            <i class="ri-delete-bin-line"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeRequirement(button) {
    const container = document.getElementById('requirementsContainer');
    const requirementDivs = container.getElementsByClassName('flex items-center');
    
    // Don't remove if there's only one requirement left
    if (requirementDivs.length > 1) {
        button.parentElement.remove();
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const captcha = document.querySelector('input[name="captcha"]:checked');
    
    if (description.length > 200) {
        e.preventDefault();
        alert('Short description must be 200 characters or less');
        document.getElementById('description').focus();
        return false;
    }
    
    // if (!captcha || captcha.value !== 'yes') {
    //     e.preventDefault();
    //     alert('Please confirm you are human by selecting "Yes, I\'m human"');
    //     return false;
    // }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i> Submitting...';
    
    return true;
});

// Real-time character count for description
const descriptionField = document.getElementById('description');
const charCount = document.createElement('div');
charCount.className = 'text-gray-500 text-xs mt-1 text-right';
charCount.innerHTML = '<span id="charCount">0</span>/200 characters';
descriptionField.parentElement.appendChild(charCount);

descriptionField.addEventListener('input', function() {
    const length = this.value.length;
    const charCountSpan = document.getElementById('charCount');
    charCountSpan.textContent = length;
    
    if (length > 200) {
        charCountSpan.className = 'text-red-600 font-bold';
    } else if (length > 180) {
        charCountSpan.className = 'text-yellow-600';
    } else {
        charCountSpan.className = 'text-gray-600';
    }
});

// Initialize character count
descriptionField.dispatchEvent(new Event('input'));
</script>

<?php require_once '../includes/footer.php'; ?>