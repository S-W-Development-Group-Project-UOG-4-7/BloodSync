<?php
require_once __DIR__ . '/../autoload.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';

// Check authentication
Auth::requireAuth('login.php');

$user = Auth::getUser();
$profile = Auth::getDonorProfile();

// Get urgent blood needs from database
try {
    $urgentNeeds = Database::fetchAll(
        "SELECT * FROM urgent_needs 
         WHERE status = 'active'
         AND expiry_date >= CURRENT_DATE
         ORDER BY priority DESC, created_at DESC"
    );
} catch (Exception $e) {
    $urgentNeeds = [];
    $error = "Unable to load urgent needs at this time.";
}

// Get hospitals - FIXED: using location instead of city column
try {
    $hospitals = Database::fetchAll(
        "SELECT id, hospital_name, location FROM hospitals WHERE is_verified = true ORDER BY hospital_name"
    );
    
    // Extract unique cities from hospital locations
    $cities = [];
    foreach ($hospitals as $hospital) {
        // Try to extract city from location (e.g., "123 Hospital Road, Colombo" -> "Colombo")
        $location = $hospital['location'];
        if (strpos($location, ',') !== false) {
            $parts = explode(',', $location);
            $city = trim(end($parts));
            if (!in_array($city, $cities)) {
                $cities[] = $city;
            }
        } else {
            if (!in_array($location, $cities)) {
                $cities[] = $location;
            }
        }
    }
    sort($cities);
    
} catch (Exception $e) {
    $hospitals = [];
    $cities = [];
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Urgent Blood Needs</h1>
                <p class="text-gray-600">Current urgent requests for blood donations</p>
            </div>
            <div class="text-sm text-gray-500">
                <i class="ri-alarm-warning-line mr-1"></i>
                Real-time updates
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Filter Options</h2>
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Type</label>
                <select id="filterBloodType" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Blood Types</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <select id="filterCity" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city); ?>">
                        <?php echo htmlspecialchars($city); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select id="filterPriority" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Priorities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="resetFilters" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-medium">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Urgent Needs List -->
    <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900">
                Current Urgent Needs
                <span class="text-sm font-normal text-gray-600">
                    (<?php echo count($urgentNeeds); ?> active requests)
                </span>
            </h2>
            <div class="text-sm">
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full">Critical</span>
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full ml-2">High</span>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full ml-2">Medium</span>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($urgentNeeds)): ?>
            <div class="text-center py-12 bg-white rounded-xl shadow-lg">
                <i class="ri-check-double-line text-5xl text-green-500 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No Urgent Needs</h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    There are currently no urgent blood needs. Check back later or view upcoming events.
                </p>
                <div class="mt-6">
                    <a href="events.php" class="text-red-600 font-medium hover:underline">
                        View upcoming events →
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($urgentNeeds as $need): 
                    $priorityClass = match($need['priority']) {
                        'critical' => 'border-red-300 bg-red-50',
                        'high' => 'border-yellow-300 bg-yellow-50',
                        'medium' => 'border-blue-300 bg-blue-50',
                        default => 'border-gray-200 bg-white'
                    };
                    $priorityText = match($need['priority']) {
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        default => 'Normal'
                    };
                    $priorityColor = match($need['priority']) {
                        'critical' => 'red',
                        'high' => 'yellow',
                        'medium' => 'blue',
                        default => 'gray'
                    };
                ?>
                <div class="border rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow <?php echo $priorityClass; ?> urgent-need-card"
                     data-blood-type="<?php echo htmlspecialchars($need['blood_type']); ?>"
                     data-city="<?php echo htmlspecialchars($need['city']); ?>"
                     data-priority="<?php echo htmlspecialchars($need['priority']); ?>">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="px-3 py-1 bg-<?php echo $priorityColor; ?>-100 text-<?php echo $priorityColor; ?>-800 rounded-full text-sm font-medium">
                                <?php echo $priorityText; ?>
                            </span>
                            <span class="ml-2 px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                <?php echo htmlspecialchars($need['blood_type']); ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">
                                <i class="ri-time-line mr-1"></i>
                                <?php echo date('M d', strtotime($need['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($need['hospital_name']); ?></h3>
                    <p class="text-gray-600 mb-4">
                        <i class="ri-map-pin-line mr-1"></i>
                        <?php echo htmlspecialchars($need['city']); ?>
                        <?php if ($need['address']): ?>
                            • <?php echo htmlspecialchars($need['address']); ?>
                        <?php endif; ?>
                    </p>
                    
                    <div class="mb-4">
                        <p class="text-gray-700 mb-2"><?php echo nl2br(htmlspecialchars($need['description'])); ?></p>
                        <?php if ($need['patient_info']): ?>
                            <p class="text-sm text-gray-600">
                                <span class="font-medium">Patient:</span> <?php echo htmlspecialchars($need['patient_info']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                <i class="ri-drop-line mr-1"></i>
                                Required: <?php echo htmlspecialchars($need['units_required']); ?> units
                            </p>
                            <?php if ($need['units_collected']): ?>
                                <p class="text-sm text-gray-600">
                                    Collected: <?php echo htmlspecialchars($need['units_collected']); ?> units
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">
                                Expires: <?php echo date('M d', strtotime($need['expiry_date'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if ($need['contact_person']): ?>
                            <p class="text-sm text-gray-600">
                                <i class="ri-user-line mr-1"></i>
                                Contact: <?php echo htmlspecialchars($need['contact_person']); ?>
                                <?php if ($need['contact_phone']): ?>
                                    • <?php echo htmlspecialchars($need['contact_phone']); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($need['additional_notes']): ?>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-sm text-gray-700">
                                    <i class="ri-information-line mr-1"></i>
                                    <?php echo nl2br(htmlspecialchars($need['additional_notes'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Important Notes -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-800 mb-3">
            <i class="ri-information-line mr-2"></i>Important Information
        </h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-blue-700 mb-2">Before Donating</h4>
                <ul class="space-y-2 text-blue-800 text-sm">
                    <li class="flex items-start">
                        <i class="ri-check-line mt-0.5 mr-2"></i>
                        Ensure you meet eligibility criteria
                    </li>
                    <li class="flex items-start">
                        <i class="ri-check-line mt-0.5 mr-2"></i>
                        Get enough rest and stay hydrated
                    </li>
                    <li class="flex items-start">
                        <i class="ri-check-line mt-0.5 mr-2"></i>
                        Eat a healthy meal before donation
                    </li>
                    <li class="flex items-start">
                        <i class="ri-check-line mt-0.5 mr-2"></i>
                        Bring your donor ID and medical reports
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-blue-700 mb-2">Emergency Contacts</h4>
                <ul class="space-y-2 text-blue-800 text-sm">
                    <li>
                        <span class="font-medium">National Blood Center:</span> 0112 345 678
                    </li>
                    <li>
                        <span class="font-medium">Emergency Hotline:</span> 1990
                    </li>
                    <li>
                        <span class="font-medium">Ambulance Service:</span> 1990
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBloodType = document.getElementById('filterBloodType');
    const filterCity = document.getElementById('filterCity');
    const filterPriority = document.getElementById('filterPriority');
    const resetFilters = document.getElementById('resetFilters');
    const needCards = document.querySelectorAll('.urgent-need-card');
    
    function filterCards() {
        const bloodType = filterBloodType.value;
        const city = filterCity.value;
        const priority = filterPriority.value;
        
        needCards.forEach(card => {
            const cardBloodType = card.getAttribute('data-blood-type');
            const cardCity = card.getAttribute('data-city');
            const cardPriority = card.getAttribute('data-priority');
            
            const showCard = 
                (bloodType === '' || cardBloodType === bloodType) &&
                (city === '' || cardCity === city) &&
                (priority === '' || cardPriority === priority);
            
            card.style.display = showCard ? 'block' : 'none';
        });
    }
    
    filterBloodType.addEventListener('change', filterCards);
    filterCity.addEventListener('change', filterCards);
    filterPriority.addEventListener('change', filterCards);
    
    resetFilters.addEventListener('click', function() {
        filterBloodType.value = '';
        filterCity.value = '';
        filterPriority.value = '';
        filterCards();
    });
    
    // Initial filter
    filterCards();
});
</script>

<?php require_once 'includes/footer.php'; ?>