<?php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin - only admins can create events
Auth::requireAdmin('../login.php');

$user = Auth::getUser();

require_once '../includes/header.php';
require_once 'admin_nav.php';

// Check if editing existing event
$isEdit = isset($_GET['edit']);
$eventId = $isEdit ? (int)$_GET['edit'] : 0;
$event = null;

if ($isEdit && $eventId > 0) {
    try {
        $event = Database::fetch(
            "SELECT e.* FROM events e WHERE e.id = ?",
            [$eventId]
        );
    } catch (Exception $e) {
        error_log('Fetch event error: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to load event.';
        header('Location: manage_events.php');
        exit();
    }
    
    if (!$event) {
        $_SESSION['error'] = 'Event not found.';
        header('Location: manage_events.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $errors = [];
    
    // Validate required fields
    $requiredFields = ['title', 'description', 'event_date', 'start_time', 'end_time', 'location'];
    foreach ($requiredFields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Validate dates
    $eventDate = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    
    if ($eventDate && strtotime($eventDate) < strtotime('today')) {
        $errors[] = 'Event date cannot be in the past.';
    }
    
    if ($startTime && $endTime && strtotime($endTime) <= strtotime($startTime)) {
        $errors[] = 'End time must be after start time.';
    }
    
    // Validate max participants
    $maxParticipants = $_POST['max_participants'] ?? '';
    if ($maxParticipants !== '' && (!is_numeric($maxParticipants) || $maxParticipants < 1)) {
        $errors[] = 'Max participants must be a positive number.';
    }
    
    if (empty($errors)) {
        try {
            // Prepare data for insertion/update
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $fullDescription = trim($_POST['description']); // Using same as description for now
            $organizerName = $user['name'];
            $organizerNIC = 'ADMIN-' . $user['id']; // Generate a NIC for admin
            $organizerEmail = $user['email'];
            $organizerContact = trim($_POST['organizer_phone'] ?? 'Not provided');
            $organizerDescription = "Event created by BloodSync admin";
            $location = trim($_POST['location']);
            $address = trim($_POST['address'] ?? $location); // Use address field or location
            $city = trim($_POST['city'] ?? 'Colombo');
            $targetDonations = (int)($maxParticipants ?: 100);
            
            if ($isEdit && $eventId > 0) {
                // Update existing event
                $sql = "UPDATE events SET 
                        title = ?, 
                        description = ?, 
                        full_description = ?,
                        organizer_name = ?,
                        organizer_nic = ?,
                        organizer_email = ?,
                        organizer_contact = ?,
                        organizer_description = ?,
                        event_date = ?,
                        event_start_time = ?,
                        event_end_time = ?,
                        location = ?,
                        address = ?,
                        city = ?,
                        target_donations = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?";
                
                $params = [
                    $title,
                    $description,
                    $fullDescription,
                    $organizerName,
                    $organizerNIC,
                    $organizerEmail,
                    $organizerContact,
                    $organizerDescription,
                    $eventDate,
                    $startTime,
                    $endTime,
                    $location,
                    $address,
                    $city,
                    $targetDonations,
                    $eventId
                ];
                
                $result = Database::execute($sql, $params); // CHANGED: execute() instead of exec()
                
                if ($result) {
                    $_SESSION['success'] = 'Event updated successfully!';
                    header('Location: manage_events.php');
                    exit();
                } else {
                    $errors[] = 'Failed to update event. Please try again.';
                }
            } else {
                // Create new event - FIXED: Using Database::execute() instead of Database::exec()
                $sql = "INSERT INTO events (
                    title, 
                    description, 
                    full_description,
                    organizer_name,
                    organizer_nic,
                    organizer_email,
                    organizer_contact,
                    organizer_description,
                    event_date,
                    event_start_time,
                    event_end_time,
                    location,
                    address,
                    city,
                    target_donations,
                    status,
                    approval_status,
                    created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', 'approved', ?, CURRENT_TIMESTAMP)";
                
                $params = [
                    $title,
                    $description,
                    $fullDescription,
                    $organizerName,
                    $organizerNIC,
                    $organizerEmail,
                    $organizerContact,
                    $organizerDescription,
                    $eventDate,
                    $startTime,
                    $endTime,
                    $location,
                    $address,
                    $city,
                    $targetDonations,
                    $user['id']
                ];
                
                $result = Database::execute($sql, $params); // CHANGED: execute() instead of exec()
                
                if ($result) {
                    $_SESSION['success'] = 'Event created successfully!';
                    header('Location: manage_events.php');
                    exit();
                } else {
                    $errors[] = 'Failed to create event. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log('Save event error: ' . $e->getMessage());
            $errors[] = 'An error occurred. Please try again: ' . $e->getMessage();
        }
    }
    
    // If there are errors, preserve form data
    $formData = $_POST;
}

// Use existing event data for editing
if ($isEdit && $event) {
    $formData = $event;
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <?php echo $isEdit ? 'Edit Event' : 'Create New Event'; ?>
        </h1>
        <p class="text-gray-600">
            <?php echo $isEdit ? 'Update event details' : 'Add a new event to the system'; ?>
        </p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-green-100 border border-green-300 rounded-xl p-4">
            <div class="flex items-center">
                <i class="ri-checkbox-circle-line text-green-600 text-xl mr-3"></i>
                <p class="text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-100 border border-red-300 rounded-xl p-4">
            <div class="flex items-center">
                <i class="ri-error-warning-line text-red-600 text-xl mr-3"></i>
                <p class="text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-100 border border-red-300 rounded-xl p-4">
            <div class="flex items-start">
                <i class="ri-error-warning-line text-red-600 text-xl mr-3 mt-0.5"></i>
                <div>
                    <p class="font-medium text-red-800 mb-2">Please fix the following errors:</p>
                    <ul class="list-disc list-inside text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="POST" action="" id="eventForm">
            <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'create'; ?>">
            
            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Event Title -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Event Title *
                        </label>
                        <input type="text" 
                               name="title" 
                               value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter event title"
                               required>
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Description *
                        </label>
                        <textarea name="description" 
                                  rows="6"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Describe the event..."
                                  required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Event Date & Time -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Event Date *
                            </label>
                            <input type="date" 
                                   name="event_date" 
                                   value="<?php echo htmlspecialchars($formData['event_date'] ?? ''); ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Target Donations *
                            </label>
                            <input type="number" 
                                   name="max_participants" 
                                   value="<?php echo htmlspecialchars($formData['target_donations'] ?? 100); ?>"
                                   min="1"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Target number of donations"
                                   required>
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Start Time *
                            </label>
                            <input type="time" 
                                   name="start_time" 
                                   value="<?php echo htmlspecialchars($formData['event_start_time'] ?? '09:00'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                End Time *
                            </label>
                            <input type="time" 
                                   name="end_time" 
                                   value="<?php echo htmlspecialchars($formData['event_end_time'] ?? '17:00'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Location -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Location *
                        </label>
                        <input type="text" 
                               name="location" 
                               value="<?php echo htmlspecialchars($formData['location'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter event location"
                               required>
                    </div>
                    
                    <!-- Address -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Full Address *
                        </label>
                        <textarea name="address" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter full address with street, city, etc."
                                  required><?php echo htmlspecialchars($formData['address'] ?? $formData['location'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Organizer Information -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h3 class="font-medium text-gray-900 mb-3">Organizer Information</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    Organizer Name *
                                </label>
                                <input type="text" 
                                       name="organizer_name"
                                       value="<?php echo htmlspecialchars($formData['organizer_name'] ?? $user['name']); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Name of the event organizer</p>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-medium mb-2">
                                        Organizer Email *
                                    </label>
                                    <input type="email" 
                                           name="organizer_email"
                                           value="<?php echo htmlspecialchars($formData['organizer_email'] ?? $user['email']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 text-sm font-medium mb-2">
                                        Contact Phone *
                                    </label>
                                    <input type="tel" 
                                           name="organizer_phone" 
                                           value="<?php echo htmlspecialchars($formData['organizer_contact'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter contact phone number"
                                           required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    Organizer NIC *
                                </label>
                                <input type="text" 
                                       name="organizer_nic"
                                       value="<?php echo htmlspecialchars($formData['organizer_nic'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter organizer NIC number"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- City -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            City *
                        </label>
                        <input type="text" 
                               name="city" 
                               value="<?php echo htmlspecialchars($formData['city'] ?? 'Colombo'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter city"
                               required>
                    </div>
                    
                    <!-- Preview -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h3 class="font-medium text-blue-900 mb-3">Event Preview</h3>
                        <div id="eventPreview" class="space-y-2 text-sm text-gray-700">
                            <p><strong>Status:</strong> <span class="text-green-600 font-medium">Will be auto-approved</span></p>
                            <p><strong>Created by:</strong> <?php echo htmlspecialchars($user['name']); ?> (Admin)</p>
                            <p class="text-xs text-gray-500">This event will be immediately visible to users after creation.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-between">
                <a href="manage_events.php" 
                   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                
                <div class="flex space-x-4">
                    <?php if ($isEdit): ?>
                        <a href="?edit=<?php echo $eventId; ?>" 
                           class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Reset
                        </a>
                    <?php endif; ?>
                    
                    <button type="submit" 
                            class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors flex items-center">
                        <i class="ri-save-line mr-2"></i>
                        <?php echo $isEdit ? 'Update Event' : 'Create Event'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation and preview
document.getElementById('eventForm').addEventListener('submit', function(e) {
    const eventDate = document.querySelector('input[name="event_date"]').value;
    const startTime = document.querySelector('input[name="start_time"]').value;
    const endTime = document.querySelector('input[name="end_time"]').value;
    
    // Validate event date is not in the past
    if (eventDate && new Date(eventDate) < new Date().setHours(0, 0, 0, 0)) {
        e.preventDefault();
        alert('Event date cannot be in the past.');
        return;
    }
    
    // Validate end time is after start time
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        if (end <= start) {
            e.preventDefault();
            alert('End time must be after start time.');
            return;
        }
    }
    
    // Validate max participants if provided
    const maxParticipants = document.querySelector('input[name="max_participants"]').value;
    if (maxParticipants && (isNaN(maxParticipants) || parseInt(maxParticipants) < 1)) {
        e.preventDefault();
        alert('Target donations must be a positive number.');
        return;
    }
});

// Preview updates
function updatePreview() {
    const title = document.querySelector('input[name="title"]').value;
    const date = document.querySelector('input[name="event_date"]').value;
    const startTime = document.querySelector('input[name="start_time"]').value;
    const endTime = document.querySelector('input[name="end_time"]').value;
    const location = document.querySelector('input[name="location"]').value;
    const targetDonations = document.querySelector('input[name="max_participants"]').value;
    
    const preview = document.getElementById('eventPreview');
    if (title || date || location) {
        let previewHTML = '';
        
        if (title) {
            previewHTML += `<p><strong>Title:</strong> ${title}</p>`;
        }
        
        if (date) {
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
            previewHTML += `<p><strong>Date:</strong> ${formattedDate}</p>`;
        }
        
        if (startTime && endTime) {
            const formattedStart = formatTime(startTime);
            const formattedEnd = formatTime(endTime);
            previewHTML += `<p><strong>Time:</strong> ${formattedStart} - ${formattedEnd}</p>`;
        }
        
        if (location) {
            previewHTML += `<p><strong>Location:</strong> ${location}</p>`;
        }
        
        if (targetDonations) {
            previewHTML += `<p><strong>Target Donations:</strong> ${targetDonations} units</p>`;
        }
        
        preview.innerHTML = previewHTML + `
            <p><strong>Status:</strong> <span class="text-green-600 font-medium">Will be auto-approved</span></p>
            <p><strong>Created by:</strong> <?php echo htmlspecialchars($user['name']); ?> (Admin)</p>
            <p class="text-xs text-gray-500">This event will be immediately visible to users after creation.</p>
        `;
    }
}

// Format time for display
function formatTime(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Update preview on input changes
document.querySelectorAll('#eventForm input, #eventForm textarea').forEach(element => {
    element.addEventListener('input', updatePreview);
    element.addEventListener('change', updatePreview);
});

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', updatePreview);
</script>

<?php require_once '../includes/footer.php'; ?>