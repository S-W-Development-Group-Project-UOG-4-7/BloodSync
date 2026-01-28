<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>BloodSync - Complete Database Setup</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css' rel='stylesheet'/>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-8'>
        <h1 class='text-3xl font-bold text-red-600 mb-6'>BloodSync Database Setup</h1>";
        
try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2 class='text-xl font-bold mb-4'>Creating database tables...</h2>";
    
    // Create users table - UPDATED with all needed columns
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'donor',
        is_verified BOOLEAN DEFAULT FALSE,
        verification_status VARCHAR(20) DEFAULT 'pending',
        verification_notes TEXT,
        rejection_reason TEXT,
        verified_at TIMESTAMP,
        verified_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Users table created (updated with all columns)</p>";
    
    // Create donors table
    $sql = "CREATE TABLE IF NOT EXISTS donors (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        nic VARCHAR(20) UNIQUE NOT NULL,
        date_of_birth DATE NOT NULL,
        blood_type VARCHAR(5) NOT NULL,
        contact_number VARCHAR(15) NOT NULL,
        address TEXT,
        city VARCHAR(50),
        weight DECIMAL(5,2) DEFAULT 50.0,
        last_donation_date DATE,
        is_eligible BOOLEAN DEFAULT TRUE,
        health_conditions TEXT,
        total_donations INTEGER DEFAULT 0,
        total_units_donated INTEGER DEFAULT 0,
        profile_picture VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Donors table created</p>";
    
    // Create medical_reports table (UPDATED WITH DEFAULT VALUES FOR TITLE AND REPORT_DATE)
    $sql = "CREATE TABLE IF NOT EXISTS medical_reports (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        title VARCHAR(255) NOT NULL DEFAULT 'Medical Report',
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50),
        file_size INTEGER,
        report_date DATE NOT NULL DEFAULT CURRENT_DATE,
        report_type VARCHAR(50) DEFAULT 'health_check',
        notes TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        reviewed_by INTEGER REFERENCES users(id),
        reviewed_at TIMESTAMP,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Medical reports table created (with default values for title and report_date)</p>";
    
    // Create appointments table - UPDATED FOR MEDICAL REPORT COLLECTION
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        hospital_name VARCHAR(100) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        appointment_type VARCHAR(50) DEFAULT 'medical_report',
        purpose VARCHAR(255) DEFAULT 'Medical Report Collection',
        status VARCHAR(20) DEFAULT 'scheduled',
        notes TEXT,
        doctor_name VARCHAR(100),
        doctor_contact VARCHAR(15),
        created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
        notified_at TIMESTAMP,
        reminder_sent BOOLEAN DEFAULT FALSE,
        cancelled_by INTEGER REFERENCES users(id),
        cancelled_at TIMESTAMP,
        cancellation_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Appointments table created (updated for medical reports)</p>";
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        metadata JSONB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Notifications table created</p>";

    // Create blood_stocks table
    $sql = "CREATE TABLE IF NOT EXISTS blood_stocks (
        id SERIAL PRIMARY KEY,
        blood_type VARCHAR(5) NOT NULL UNIQUE,
        units_available INTEGER DEFAULT 0,
        units_used INTEGER DEFAULT 0,
        units_received INTEGER DEFAULT 0,
        minimum_level INTEGER DEFAULT 10,
        maximum_level INTEGER DEFAULT 100,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_by INTEGER REFERENCES users(id),
        notes TEXT
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Blood stocks table created</p>";

    // Create blood_transactions table
    $sql = "CREATE TABLE IF NOT EXISTS blood_transactions (
        id SERIAL PRIMARY KEY,
        blood_type VARCHAR(5) NOT NULL,
        transaction_type VARCHAR(20) NOT NULL, -- 'received', 'used', 'adjusted'
        units INTEGER NOT NULL,
        source_destination VARCHAR(255), -- Hospital name, donor name, etc.
        purpose TEXT,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        performed_by INTEGER REFERENCES users(id),
        notes TEXT
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Blood transactions table created</p>";

    // Create donor_donations table
    $sql = "CREATE TABLE IF NOT EXISTS donor_donations (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        donation_date DATE NOT NULL,
        units_donated INTEGER NOT NULL,
        blood_type VARCHAR(5) NOT NULL,
        hospital_location VARCHAR(255),
        notes TEXT,
        verified_by INTEGER REFERENCES users(id),
        verified_at TIMESTAMP,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Donor donations table created</p>";

    // Create admin_notifications table for admin dashboard
    $sql = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id SERIAL PRIMARY KEY,
        notification_type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        related_id INTEGER,
        related_type VARCHAR(50),
        is_read BOOLEAN DEFAULT FALSE,
        priority VARCHAR(20) DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Admin notifications table created</p>";

    // Create uploads directory tracking (optional but useful)
    $sql = "CREATE TABLE IF NOT EXISTS uploaded_files (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50),
        file_size INTEGER,
        upload_type VARCHAR(50) DEFAULT 'medical_report',
        status VARCHAR(20) DEFAULT 'active',
        uploaded_by INTEGER REFERENCES users(id),
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Uploaded files tracking table created</p>";

    // Create donor_medical_history table for comprehensive health tracking
    $sql = "CREATE TABLE IF NOT EXISTS donor_medical_history (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        record_type VARCHAR(50) NOT NULL, -- 'blood_test', 'vaccination', 'health_check', 'allergy', 'medication'
        record_date DATE NOT NULL,
        test_name VARCHAR(255),
        result_value VARCHAR(100),
        result_unit VARCHAR(20),
        reference_range VARCHAR(100),
        is_normal BOOLEAN DEFAULT TRUE,
        doctor_name VARCHAR(100),
        hospital_clinic VARCHAR(255),
        notes TEXT,
        attachment_path VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Donor medical history table created</p>";

    // Create donor_eligibility_log table
    $sql = "CREATE TABLE IF NOT EXISTS donor_eligibility_log (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        eligibility_status VARCHAR(20) NOT NULL,
        reason TEXT,
        effective_date DATE NOT NULL,
        reviewed_by INTEGER REFERENCES users(id),
        reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Donor eligibility log table created</p>";

    // Create hospitals table
    $sql = "CREATE TABLE IF NOT EXISTS hospitals (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        hospital_name VARCHAR(255) NOT NULL,
        registration_number VARCHAR(100) UNIQUE NOT NULL,
        location VARCHAR(255) NOT NULL,
        contact_person VARCHAR(100) NOT NULL,
        contact_email VARCHAR(100),
        contact_phone VARCHAR(15) NOT NULL,
        license_number VARCHAR(100),
        license_expiry DATE,
        is_verified BOOLEAN DEFAULT FALSE,
        verification_status VARCHAR(20) DEFAULT 'pending',
        verification_note TEXT,
        verified_at TIMESTAMP,
        verified_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Hospitals table created</p>";

    // Create blood_requests table
    $sql = "CREATE TABLE IF NOT EXISTS blood_requests (
        id SERIAL PRIMARY KEY,
        hospital_id INTEGER REFERENCES hospitals(id) ON DELETE CASCADE,
        request_number VARCHAR(50) UNIQUE NOT NULL,
        patient_name VARCHAR(100) NOT NULL,
        patient_age INTEGER NOT NULL,
        patient_sex VARCHAR(10) NOT NULL,
        patient_ward VARCHAR(100),
        blood_type VARCHAR(5) NOT NULL,
        units_required INTEGER NOT NULL,
        urgency_level VARCHAR(20) DEFAULT 'normal',
        reason TEXT,
        required_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        admin_notes TEXT,
        approved_by INTEGER REFERENCES users(id),
        approved_at TIMESTAMP,
        fulfilled_by INTEGER REFERENCES users(id),
        fulfilled_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Blood requests table created</p>";

    // CREATE ADMIN_LOGS TABLE - ADDED
    $sql = "CREATE TABLE IF NOT EXISTS admin_logs (
        id SERIAL PRIMARY KEY,
        admin_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        action VARCHAR(255) NOT NULL,
        target_user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        target_entity_type VARCHAR(50), -- 'donor', 'hospital', 'event', etc.
        target_entity_id INTEGER,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Admin logs table created</p>";

    // Create urgent_needs table - NEW ADDITION
    $sql = "CREATE TABLE IF NOT EXISTS urgent_needs (
        id SERIAL PRIMARY KEY,
        hospital_name VARCHAR(255) NOT NULL,
        city VARCHAR(100) NOT NULL,
        address TEXT,
        blood_type VARCHAR(5) NOT NULL,
        units_required INTEGER NOT NULL,
        units_collected INTEGER DEFAULT 0,
        priority VARCHAR(20) CHECK (priority IN ('critical', 'high', 'medium')) DEFAULT 'medium',
        description TEXT,
        patient_info TEXT,
        contact_person VARCHAR(255),
        contact_phone VARCHAR(20),
        additional_notes TEXT,
        expiry_date DATE NOT NULL,
        status VARCHAR(20) CHECK (status IN ('active', 'fulfilled', 'expired')) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Urgent needs table created</p>";

    // ====================================================
    // EVENTS TABLES - ADDED FOR EVENTS FEATURE
    // ====================================================
    
    // Create events table
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        full_description TEXT,
        image VARCHAR(500),
        organizer_name VARCHAR(255) NOT NULL,
        organizer_nic VARCHAR(20) NOT NULL,
        organizer_email VARCHAR(255) NOT NULL,
        organizer_contact VARCHAR(20) NOT NULL,
        organizer_description TEXT,
        event_date DATE NOT NULL,
        event_start_time TIME NOT NULL,
        event_end_time TIME NOT NULL,
        location VARCHAR(255) NOT NULL,
        address TEXT,
        city VARCHAR(100),
        target_donations INTEGER DEFAULT 100,
        status VARCHAR(20) DEFAULT 'upcoming',
        approval_status VARCHAR(20) DEFAULT 'pending',
        rejection_reason TEXT,
        approved_by INTEGER REFERENCES users(id),
        approved_at TIMESTAMP,
        created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_ip VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        website VARCHAR(255),
        registered_donors INTEGER DEFAULT 0,
        units_collected INTEGER DEFAULT 0,
        CHECK (target_donations > 0),
        CHECK (status IN ('upcoming', 'ongoing', 'completed', 'cancelled')),
        CHECK (approval_status IN ('pending', 'approved', 'rejected'))
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Events table created</p>";

    // Create event_requirements table
    $sql = "CREATE TABLE IF NOT EXISTS event_requirements (
        id SERIAL PRIMARY KEY,
        event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
        requirement VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Event requirements table created</p>";

    // Create event_registrations table
    $sql = "CREATE TABLE IF NOT EXISTS event_registrations (
        id SERIAL PRIMARY KEY,
        event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id),
        donor_id INTEGER REFERENCES donors(id),
        full_name VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(20),
        blood_type VARCHAR(5),
        time_slot VARCHAR(50),
        status VARCHAR(20) DEFAULT 'pending',
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attended BOOLEAN DEFAULT FALSE,
        attended_at TIMESTAMP,
        notes TEXT
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Event registrations table created</p>";

    // Create event_categories table (for categorizing events)
    $sql = "CREATE TABLE IF NOT EXISTS event_categories (
        id SERIAL PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        icon VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Event categories table created</p>";

    // Create event_category_mapping table
    $sql = "CREATE TABLE IF NOT EXISTS event_category_mapping (
        event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
        category_id INTEGER REFERENCES event_categories(id) ON DELETE CASCADE,
        PRIMARY KEY (event_id, category_id)
    )";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Event category mapping table created</p>";

    // Insert initial blood stock data
    $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    foreach ($bloodTypes as $type) {
        $check = $pdo->query("SELECT COUNT(*) FROM blood_stocks WHERE blood_type = '{$type}'")->fetchColumn();
        if ($check == 0) {
            $sql = "INSERT INTO blood_stocks (blood_type, units_available, minimum_level, maximum_level) 
                    VALUES (:type, 0, 10, 100)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['type' => $type]);
        }
    }
    echo "<p class='text-green-600 mb-2'>✅ Initial blood stock data added</p>";
    
    // Insert urgent needs sample data
    $sql = "INSERT INTO urgent_needs (hospital_name, city, blood_type, units_required, priority, description, expiry_date, status) 
            SELECT 
                'General Hospital, Colombo',
                'Colombo',
                'O+',
                5,
                'critical',
                'Emergency surgeries, multiple trauma cases',
                CURRENT_DATE + INTERVAL '3 days',
                'active'
            WHERE NOT EXISTS (SELECT 1 FROM urgent_needs WHERE hospital_name = 'General Hospital, Colombo' AND blood_type = 'O+')";
    
    $pdo->exec($sql);
    
    $sql = "INSERT INTO urgent_needs (hospital_name, city, blood_type, units_required, priority, description, expiry_date, status) 
            SELECT 
                'Kandy Teaching Hospital',
                'Kandy',
                'All',
                10,
                'high',
                'Blood drive for upcoming surgeries',
                CURRENT_DATE + INTERVAL '7 days',
                'active'
            WHERE NOT EXISTS (SELECT 1 FROM urgent_needs WHERE hospital_name = 'Kandy Teaching Hospital' AND blood_type = 'All')";
    
    $pdo->exec($sql);
    echo "<p class='text-green-600 mb-2'>✅ Sample urgent needs data added</p>";
    
    // Insert report types into medical_reports (if not exists)
    $reportTypes = ['blood_test', 'health_check', 'vaccination', 'allergy_test', 'medication_record', 'other'];
    foreach ($reportTypes as $type) {
        // This is for reference, actual types are stored as strings
        echo "<p class='text-blue-600 mb-1'>✅ Report type configured: {$type}</p>";
    }
    
    // Insert event categories
    $eventCategories = [
        ['Blood Donation Camp', 'Community blood donation drives', 'ri-heart-pulse-line'],
        ['Emergency Blood Drive', 'Urgent blood collection events', 'ri-alarm-warning-line'],
        ['Awareness Campaign', 'Blood donation awareness programs', 'ri-megaphone-line'],
        ['Corporate Drive', 'Workplace blood donation events', 'ri-building-line'],
        ['University Drive', 'Campus blood donation events', 'ri-graduation-cap-line'],
        ['Plasma Collection', 'Plasma donation specific events', 'ri-drop-line']
    ];
    
    foreach ($eventCategories as $category) {
        $check = $pdo->query("SELECT COUNT(*) FROM event_categories WHERE category_name = '{$category[0]}'")->fetchColumn();
        if ($check == 0) {
            $sql = "INSERT INTO event_categories (category_name, description, icon) 
                    VALUES (:name, :desc, :icon)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $category[0],
                'desc' => $category[1],
                'icon' => $category[2]
            ]);
            echo "<p class='text-blue-600 mb-1'>✅ Event category added: {$category[0]}</p>";
        }
    }
    
    // Create indexes - ADDED medical_reports user_id and file_name indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_verified ON users(is_verified, verification_status)",
        "CREATE INDEX IF NOT EXISTS idx_donors_user_id ON donors(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_donors_blood_type ON donors(blood_type)",
        "CREATE INDEX IF NOT EXISTS idx_donors_eligible ON donors(is_eligible)",
        "CREATE INDEX IF NOT EXISTS idx_medical_reports_donor_id ON medical_reports(donor_id)",
        "CREATE INDEX IF NOT EXISTS idx_medical_reports_user_id ON medical_reports(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_medical_reports_file_name ON medical_reports(file_name)",
        "CREATE INDEX IF NOT EXISTS idx_medical_reports_status ON medical_reports(status)",
        "CREATE INDEX IF NOT EXISTS idx_medical_reports_date ON medical_reports(report_date DESC)",
        "CREATE INDEX IF NOT EXISTS idx_medical_reports_type ON medical_reports(report_type)",
        // Appointment indexes
        "CREATE INDEX IF NOT EXISTS idx_appointments_donor_id ON appointments(donor_id)",
        "CREATE INDEX IF NOT EXISTS idx_appointments_user_id ON appointments(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date, appointment_time)",
        "CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status)",
        "CREATE INDEX IF NOT EXISTS idx_appointments_created_by ON appointments(created_by)",
        "CREATE INDEX IF NOT EXISTS idx_appointments_type ON appointments(appointment_type)",
        // Urgent needs indexes - NEW
        "CREATE INDEX IF NOT EXISTS idx_urgent_needs_status ON urgent_needs(status, expiry_date)",
        "CREATE INDEX IF NOT EXISTS idx_urgent_needs_blood_type ON urgent_needs(blood_type)",
        "CREATE INDEX IF NOT EXISTS idx_urgent_needs_priority ON urgent_needs(priority)",
        "CREATE INDEX IF NOT EXISTS idx_urgent_needs_city ON urgent_needs(city)",
        // Other indexes
        "CREATE INDEX IF NOT EXISTS idx_donor_donations_donor_id ON donor_donations(donor_id)",
        "CREATE INDEX IF NOT EXISTS idx_donor_donations_status ON donor_donations(status)",
        "CREATE INDEX IF NOT EXISTS idx_donor_donations_date ON donor_donations(donation_date DESC)",
        "CREATE INDEX IF NOT EXISTS idx_blood_stocks_type ON blood_stocks(blood_type)",
        "CREATE INDEX IF NOT EXISTS idx_blood_transactions_date ON blood_transactions(transaction_date)",
        "CREATE INDEX IF NOT EXISTS idx_medical_history_donor_id ON donor_medical_history(donor_id)",
        "CREATE INDEX IF NOT EXISTS idx_medical_history_date ON donor_medical_history(record_date DESC)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id, is_read)",
        "CREATE INDEX IF NOT EXISTS idx_uploaded_files_donor_id ON uploaded_files(donor_id)",
        "CREATE INDEX IF NOT EXISTS idx_eligibility_log_donor_id ON donor_eligibility_log(donor_id, effective_date DESC)",
        "CREATE INDEX IF NOT EXISTS idx_hospitals_user_id ON hospitals(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_hospitals_verified ON hospitals(is_verified, verification_status)",
        "CREATE INDEX IF NOT EXISTS idx_blood_requests_hospital_id ON blood_requests(hospital_id)",
        "CREATE INDEX IF NOT EXISTS idx_blood_requests_status ON blood_requests(status)",
        "CREATE INDEX IF NOT EXISTS idx_blood_requests_date ON blood_requests(required_date, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_blood_requests_blood_type ON blood_requests(blood_type)",
        "CREATE INDEX IF NOT EXISTS idx_blood_requests_request_number ON blood_requests(request_number)",
        // Admin logs indexes
        "CREATE INDEX IF NOT EXISTS idx_admin_logs_admin_id ON admin_logs(admin_id)",
        "CREATE INDEX IF NOT EXISTS idx_admin_logs_created_at ON admin_logs(created_at DESC)",
        "CREATE INDEX IF NOT EXISTS idx_admin_logs_target_user ON admin_logs(target_user_id)",
        // Events indexes
        "CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date, event_start_time)",
        "CREATE INDEX IF NOT EXISTS idx_events_status ON events(status)",
        "CREATE INDEX IF NOT EXISTS idx_events_organizer ON events(organizer_name)",
        "CREATE INDEX IF NOT EXISTS idx_events_created_by ON events(created_by)",
        "CREATE INDEX IF NOT EXISTS idx_event_requirements_event_id ON event_requirements(event_id)",
        "CREATE INDEX IF NOT EXISTS idx_event_registrations_event_id ON event_registrations(event_id)",
        "CREATE INDEX IF NOT EXISTS idx_event_registrations_user_id ON event_registrations(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_event_registrations_donor_id ON event_registrations(donor_id)",
        "CREATE INDEX IF NOT EXISTS idx_event_registrations_status ON event_registrations(status)",
        "CREATE INDEX IF NOT EXISTS idx_event_categories_name ON event_categories(category_name)",
        "CREATE INDEX IF NOT EXISTS idx_event_category_mapping_event ON event_category_mapping(event_id)",
        "CREATE INDEX IF NOT EXISTS idx_event_category_mapping_category ON event_category_mapping(category_id)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "<p class='text-blue-600 mb-1'>✅ Index created: " . substr($index, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            echo "<p class='text-yellow-600 mb-1'>⚠️ Index may already exist: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "...</p>";
        }
    }
    
    // Create triggers for updated_at timestamps
    echo "<h3 class='text-lg font-bold mt-6 mb-3'>Creating triggers and functions...</h3>";
    
    try {
        // Function to update timestamp
        $sql = "CREATE OR REPLACE FUNCTION update_updated_at_column()
                RETURNS TRIGGER AS $$
                BEGIN
                    NEW.updated_at = CURRENT_TIMESTAMP;
                    RETURN NEW;
                END;
                $$ language 'plpgsql'";
        $pdo->exec($sql);
        echo "<p class='text-blue-600 mb-1'>✅ Created update timestamp function</p>";
        
        // Triggers for each table
        $triggers = [
            'users' => 'users_updated_at',
            'donors' => 'donors_updated_at',
            'medical_reports' => 'medical_reports_updated_at',
            'appointments' => 'appointments_updated_at',
            'donor_medical_history' => 'donor_medical_history_updated_at',
            'hospitals' => 'hospitals_updated_at',
            'blood_requests' => 'blood_requests_updated_at',
            'events' => 'events_updated_at',
            'urgent_needs' => 'urgent_needs_updated_at'  // NEW TRIGGER
        ];
        
        foreach ($triggers as $table => $trigger_name) {
            try {
                // Drop trigger if exists
                $pdo->exec("DROP TRIGGER IF EXISTS {$trigger_name} ON {$table}");
                
                // Create trigger
                $sql = "CREATE TRIGGER {$trigger_name}
                        BEFORE UPDATE ON {$table}
                        FOR EACH ROW
                        EXECUTE FUNCTION update_updated_at_column()";
                $pdo->exec($sql);
                echo "<p class='text-blue-600 mb-1'>✅ Created trigger for {$table}</p>";
            } catch (PDOException $e) {
                echo "<p class='text-yellow-600 mb-1'>⚠️ Could not create trigger for {$table}: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "...</p>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<p class='text-yellow-600 mb-1'>⚠️ Could not create triggers: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "...</p>";
    }
    
    // Check if admin exists
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@bloodsync.com'")->fetchColumn();
    
    if ($checkAdmin == 0) {
        $adminPassword = 'admin123';
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password_hash, role, is_verified, verification_status) 
                VALUES (:email, :password, 'admin', TRUE, 'approved') RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => 'admin@bloodsync.com',
            'password' => $hashedPassword
        ]);
        
        $adminId = $stmt->fetchColumn();
        
        // Also create a test donor user
        $donorPassword = 'donor123';
        $hashedDonorPassword = password_hash($donorPassword, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password_hash, role, is_verified, verification_status) 
                VALUES (:email, :password, 'donor', TRUE, 'approved') RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => 'donor@bloodsync.com',
            'password' => $hashedDonorPassword
        ]);
        
        $donorUserId = $stmt->fetchColumn();
        
        // Create donor profile
        $sql = "INSERT INTO donors (user_id, first_name, last_name, nic, date_of_birth, blood_type, contact_number, address, city, weight)
                VALUES (:user_id, 'John', 'Doe', '987654321V', '1990-01-15', 'O+', '0771234567', '123 Main Street', 'Colombo', 70.5)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $donorUserId]);
        
        // Create a test hospital user
        $hospitalPassword = 'hospital123';
        $hashedHospitalPassword = password_hash($hospitalPassword, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password_hash, role, is_verified, verification_status) 
                VALUES (:email, :password, 'hospital', TRUE, 'approved') RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => 'hospital@bloodsync.com',
            'password' => $hashedHospitalPassword
        ]);
        
        $hospitalUserId = $stmt->fetchColumn();
        
        // Create hospital profile
        $sql = "INSERT INTO hospitals (user_id, hospital_name, registration_number, location, contact_person, contact_email, contact_phone, is_verified, verification_status)
                VALUES (:user_id, 'General Hospital Colombo', 'HOS-001-GHC', '123 Hospital Road, Colombo', 'Dr. James Wilson', 'contact@ghc.lk', '0112345678', TRUE, 'approved')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $hospitalUserId]);
        
        // Create sample events
        echo "<h3 class='text-lg font-bold mt-6 mb-3'>Creating sample events...</h3>";
        
        // Sample event 1
        $sql = "INSERT INTO events (title, description, full_description, organizer_name, organizer_nic, organizer_email, organizer_contact, organizer_description, 
                event_date, event_start_time, event_end_time, location, address, city, target_donations, status, approval_status, created_by)
                VALUES (
                    'Annual Blood Donation Camp',
                    'Join us for our annual community blood donation drive. Free health checkups and refreshments provided.',
                    'Our Annual Blood Donation Camp is one of the largest community-driven blood donation events in the region. This year, we aim to collect over 500 units of blood to support local hospitals, cancer treatment centers, and emergency services. The event will feature: • Free health checkups for all participants • Professional medical staff on-site • Refreshments and snacks for donors • Certificate of appreciation • Parking facilities available • Wheelchair accessible venue',
                    'Red Cross Society',
                    '987654321V',
                    'organizer@redcross.org',
                    '+1 234-567-8900',
                    'The Red Cross Society is a humanitarian organization dedicated to saving lives and supporting communities in need.',
                    '2024-12-15',
                    '09:00:00',
                    '17:00:00',
                    'Community Center',
                    '123 Main Street, Downtown',
                    'Colombo',
                    200,
                    'upcoming',
                    'approved',
                    :admin_id
                ) RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['admin_id' => $adminId]);
        $eventId1 = $stmt->fetchColumn();
        
        // Add requirements for event 1
        $requirements = ['Age 18-65', 'Weight > 50kg', 'No recent illnesses', 'Valid ID required'];
        foreach ($requirements as $requirement) {
            $sql = "INSERT INTO event_requirements (event_id, requirement) VALUES (:event_id, :requirement)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['event_id' => $eventId1, 'requirement' => $requirement]);
        }
        
        // Map categories for event 1
        $sql = "INSERT INTO event_category_mapping (event_id, category_id) 
                SELECT :event_id, id FROM event_categories WHERE category_name = 'Blood Donation Camp'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId1]);
        
        // Set registered donors for sample event 1
        $sql = "UPDATE events SET registered_donors = 156 WHERE id = :event_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId1]);
        
        echo "<p class='text-blue-600 mb-1'>✅ Created sample event: Annual Blood Donation Camp</p>";
        
        // Sample event 2
        $sql = "INSERT INTO events (title, description, full_description, organizer_name, organizer_nic, organizer_email, organizer_contact, organizer_description, 
                event_date, event_start_time, event_end_time, location, address, city, target_donations, status, approval_status, created_by)
                VALUES (
                    'Emergency Blood Drive',
                    'Urgent need for O- blood type. Hospital emergency department requires immediate donations.',
                    'Emergency blood collection event for critical patients. Due to recent accidents and emergency surgeries, we have an urgent need for O- blood type. All eligible donors are requested to participate. Immediate blood typing will be done on-site.',
                    'City General Hospital',
                    '876543210V',
                    'organizer@cityhospital.org',
                    '+1 234-567-8999',
                    'City General Hospital is the largest public hospital in the region, serving over 1 million patients annually.',
                    '2024-12-10',
                    '08:00:00',
                    '16:00:00',
                    'City General Hospital Main Lobby',
                    '456 Hospital Road',
                    'Colombo',
                    150,
                    'upcoming',
                    'approved',
                    :admin_id
                ) RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['admin_id' => $adminId]);
        $eventId2 = $stmt->fetchColumn();
        
        // Map categories for event 2
        $sql = "INSERT INTO event_category_mapping (event_id, category_id) 
                SELECT :event_id, id FROM event_categories WHERE category_name = 'Emergency Blood Drive'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId2]);
        
        // Set registered donors for sample event 2
        $sql = "UPDATE events SET registered_donors = 89 WHERE id = :event_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId2]);
        
        echo "<p class='text-blue-600 mb-1'>✅ Created sample event: Emergency Blood Drive</p>";
        
        // Sample event 3
        $sql = "INSERT INTO events (title, description, full_description, organizer_name, organizer_nic, organizer_email, organizer_contact, organizer_description, 
                event_date, event_start_time, event_end_time, location, address, city, target_donations, status, approval_status, created_by)
                VALUES (
                    'College Blood Drive 2024',
                    'University campus blood donation event. All students, faculty, and staff welcome.',
                    'Annual university blood donation drive organized by the Student Union. This event encourages young donors to participate in life-saving activities. Community service hours will be awarded to participating students.',
                    'Student Union',
                    '765432109V',
                    'president@studentunion.edu',
                    '+1 234-567-8998',
                    'University Student Union organizes various campus events and community service activities.',
                    '2024-12-20',
                    '10:00:00',
                    '18:00:00',
                    'University Student Center',
                    'University Avenue',
                    'Colombo',
                    300,
                    'upcoming',
                    'approved',
                    :admin_id
                ) RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['admin_id' => $adminId]);
        $eventId3 = $stmt->fetchColumn();
        
        // Map categories for event 3
        $sql = "INSERT INTO event_category_mapping (event_id, category_id) 
                SELECT :event_id, id FROM event_categories WHERE category_name = 'University Drive'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId3]);
        
        // Set registered donors for sample event 3
        $sql = "UPDATE events SET registered_donors = 203 WHERE id = :event_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId3]);
        
        echo "<p class='text-blue-600 mb-1'>✅ Created sample event: College Blood Drive 2024</p>";
        
        // Add sample event registrations
        $sql = "INSERT INTO event_registrations (event_id, donor_id, full_name, email, phone, blood_type, status)
                SELECT :event_id, id, CONCAT(first_name, ' ', last_name), 'donor@bloodsync.com', contact_number, blood_type, 'confirmed'
                FROM donors WHERE nic = '987654321V'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['event_id' => $eventId1]);
        
        echo "<p class='text-blue-600 mb-1'>✅ Added sample registration to events</p>";
        
        // Create sample appointment for testing
        echo "<h3 class='text-lg font-bold mt-6 mb-3'>Creating sample appointment...</h3>";
        
        // First get the actual donor ID from the donors table
        $donorId = $pdo->query("SELECT id FROM donors WHERE user_id = {$donorUserId}")->fetchColumn();
        
        if ($donorId) {
            $sql = "INSERT INTO appointments (
                        donor_id, user_id, hospital_name, appointment_date, appointment_time, 
                        appointment_type, purpose, notes, doctor_name, doctor_contact, created_by
                    ) VALUES (
                        :donor_id, :user_id, 'General Hospital Colombo', 
                        DATE(CURRENT_DATE + INTERVAL '7 days'), '10:00:00',
                        'medical_report', 'Medical Report Collection',
                        'Please bring your NIC and previous medical reports',
                        'Dr. James Wilson', '0112345678', :admin_id
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'donor_id' => $donorId,      // Use the actual donor ID
                'user_id' => $donorUserId,   // User ID stays as is
                'admin_id' => $adminId
            ]);
            
            echo "<p class='text-blue-600 mb-1'>✅ Created sample appointment for test donor</p>";
        } else {
            echo "<p class='text-yellow-600 mb-1'>⚠️ Could not create sample appointment: Donor profile not found</p>";
        }
        
        echo "<div class='mt-6 bg-blue-50 border border-blue-200 p-4 rounded-lg'>
                <p class='text-blue-700'>
                    <strong>✅ Admin user created:</strong><br>
                    Email: admin@bloodsync.com<br>
                    Password: admin123<br>
                    <span class='text-red-600 text-sm'>⚠️ Change this password immediately!</span>
                </p>
                <hr class='my-2'>
                <p class='text-blue-700'>
                    <strong>✅ Test donor user created:</strong><br>
                    Email: donor@bloodsync.com<br>
                    Password: donor123<br>
                    NIC: 987654321V (Test donor)<br>
                    Blood Type: O+
                </p>
                <hr class='my-2'>
                <p class='text-blue-700'>
                    <strong>✅ Test hospital user created:</strong><br>
                    Email: hospital@bloodsync.com<br>
                    Password: hospital123<br>
                    Hospital: General Hospital Colombo
                </p>
                <hr class='my-2'>
                <p class='text-blue-700'>
                    <strong>✅ Sample appointment created:</strong><br>
                    For: John Doe (987654321V)<br>
                    Hospital: General Hospital Colombo<br>
                    Date: " . date('Y-m-d', strtotime('+7 days')) . " at 10:00 AM<br>
                    Purpose: Medical Report Collection
                </p>
              </div>";
    } else {
        echo "<div class='mt-6 bg-yellow-50 border border-yellow-200 p-4 rounded-lg'>
                <p class='text-yellow-700'>
                    <strong>⚠️ Admin user already exists</strong><br>
                    Email: admin@bloodsync.com
                </p>
              </div>";
    }
    
    // Create sample medical report for testing (UPDATED with all columns)
    try {
        $checkReport = $pdo->query("SELECT COUNT(*) FROM medical_reports")->fetchColumn();
        if ($checkReport == 0) {
            // Get donor ID
            $donorId = $pdo->query("SELECT id FROM donors WHERE nic = '987654321V'")->fetchColumn();
            $userId = $pdo->query("SELECT id FROM users WHERE email = 'donor@bloodsync.com'")->fetchColumn();
            
            if ($donorId && $userId) {
                $sql = "INSERT INTO medical_reports (donor_id, user_id, title, file_name, file_path, file_type, file_size, report_date, report_type, notes, status)
                        VALUES (:donor_id, :user_id, 'Complete Blood Count Test', 'sample_report.pdf', 'uploads/reports/sample_report.pdf', 'application/pdf', 102400, :date, 'blood_test', 'Normal results, eligible for donation.', 'approved')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'donor_id' => $donorId,
                    'user_id' => $userId,
                    'date' => date('Y-m-d', strtotime('-1 month'))
                ]);
                
                echo "<p class='text-green-600 mb-2'>✅ Sample medical report created for testing</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='text-yellow-600 mb-1'>⚠️ Could not create sample report: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "...</p>";
    }
    
    // Create sample blood request for testing
    try {
        $checkRequest = $pdo->query("SELECT COUNT(*) FROM blood_requests")->fetchColumn();
        if ($checkRequest == 0) {
            // Get hospital ID
            $hospitalId = $pdo->query("SELECT id FROM hospitals WHERE registration_number = 'HOS-001-GHC'")->fetchColumn();
            
            if ($hospitalId) {
                $sql = "INSERT INTO blood_requests (hospital_id, request_number, patient_name, patient_age, patient_sex, patient_ward, blood_type, units_required, urgency_level, reason, required_date, status)
                        VALUES (:hospital_id, 'REQ-20240115-001', 'Sarah Johnson', 35, 'female', 'ICU Ward 3', 'O+', 3, 'urgent', 'Emergency surgery', :date, 'pending')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'hospital_id' => $hospitalId,
                    'date' => date('Y-m-d', strtotime('+2 days'))
                ]);
                
                echo "<p class='text-green-600 mb-2'>✅ Sample blood request created for testing</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='text-yellow-600 mb-1'>⚠️ Could not create sample blood request: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "...</p>";
    }
    
    echo "<div class='mt-8 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg'>
            <p class='font-bold'>✅ Database setup completed successfully!</p>
            <p class='mt-2'>All tables including events, appointments, and urgent_needs have been created.</p>
            <p class='mt-1'>✓ Appointment system ready for medical report collection scheduling</p>
            <p class='mt-1'>✓ Email notifications configured for appointment reminders</p>
            <p class='mt-1'>✓ Donor dashboard shows upcoming appointments</p>
            <p class='mt-1'>✓ Admin can manage all appointments</p>
            <p class='mt-1'>✓ Urgent blood needs system configured</p>
            <p class='mt-1'>✓ Sample urgent needs data added</p>
          </div>";
    
    echo "<div class='mt-8 bg-gray-50 border border-gray-200 p-6 rounded-lg'>
            <h3 class='text-lg font-bold mb-4'>New Features Added:</h3>
            <ul class='list-disc pl-5 space-y-2'>
                <li><strong>Medical Report Appointment System</strong> - Schedule appointments for donors</li>
                <li><strong>Urgent Blood Needs System</strong> - Track and display urgent blood requirements</li>
                <li><strong>Email Notifications</strong> - Automatic email to donors when appointments are created</li>
                <li><strong>Admin Appointment Management</strong> - Create, view, update, cancel appointments</li>
                <li><strong>Donor Appointment Dashboard</strong> - View upcoming and past appointments</li>
                <li><strong>Appointment History</strong> - Track all past appointments</li>
                <li><strong>Status Tracking</strong> - Scheduled, Completed, Cancelled statuses</li>
                <li><strong>Urgent Needs Dashboard</strong> - Critical blood requirements display</li>
                <li><strong>Priority-based Urgent Needs</strong> - Critical, High, Medium priority levels</li>
                <li><strong>Cancellation with Reason</strong> - Track why appointments were cancelled</li>
                <li><strong>Doctor Information</strong> - Store doctor name and contact details</li>
                <li><strong>Appointment Notes</strong> - Add special instructions for donors</li>
                <li><strong>Today's Appointments Highlight</strong> - Easy identification of today's appointments</li>
                <li><strong>Search & Filter</strong> - Search by donor name/NIC, filter by status/date</li>
                <li><strong>Sample Appointment Created</strong> - Test appointment for sample donor</li>
                <li><strong>Sample Urgent Needs Created</strong> - Test urgent blood requirements</li>
            </ul>
          </div>";
    
    echo "<div class='mt-8'>
            <h3 class='text-lg font-bold mb-2'>Next Steps for Full System:</h3>
            <ol class='list-decimal pl-5 space-y-2'>
                <li><a href='pages/admin/dashboard.php' class='text-blue-600 hover:underline'>Go to Admin Dashboard</a> - Access all features</li>
                <li><a href='pages/admin/create_appointment.php' class='text-blue-600 hover:underline'>Create New Appointment</a> - Schedule medical report appointment</li>
                <li><a href='pages/admin/manage_appointments.php' class='text-blue-600 hover:underline'>Manage Appointments</a> - View all appointments</li>
                <li><a href='pages/donor/dashboard.php' class='text-blue-600 hover:underline'>Donor Dashboard</a> - See urgent needs and appointments</li>
                <li><a href='pages/urgent_needs.php' class='text-blue-600 hover:underline'>Urgent Blood Needs Page</a> - View all urgent requirements</li>
                <li><a href='pages/donor/appointments.php' class='text-blue-600 hover:underline'>Donor Appointment History</a> - View all donor appointments</li>
                <li><a href='pages/login.php' class='text-blue-600 hover:underline'>Login as admin</a> (admin@bloodsync.com / admin123)</li>
                <li><a href='pages/login.php' class='text-blue-600 hover:underline'>Login as test donor</a> (donor@bloodsync.com / donor123)</li>
                <li><strong>Check email</strong> - Test donor should receive appointment notification email</li>
            </ol>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='mt-8 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg'>
            <p class='font-bold'>❌ Setup Error</p>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p class='mt-2 text-sm'>Check your database configuration in config/database.php</p>
          </div>";
}

echo "</div></body></html>";
?>