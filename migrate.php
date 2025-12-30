<?php
require_once __DIR__ . '/core/database.php';

try {
    $pdo = Database::getInstance();

    // Create donors table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS donors (
        id SERIAL PRIMARY KEY,
        fullname VARCHAR(255) NOT NULL,
        nic VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20) NOT NULL,
        blood_type VARCHAR(5) NOT NULL,
        dob DATE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Donors table created successfully\n";

    // Create hospitals table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS hospitals (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        contact_number VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Hospitals table created successfully\n";

    // Create appointments table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        hospital_id INTEGER REFERENCES hospitals(id) ON DELETE CASCADE,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        status VARCHAR(20) DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Appointments table created successfully\n";

    // Create donations table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS donations (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        hospital_id INTEGER REFERENCES hospitals(id) ON DELETE CASCADE,
        donation_date DATE NOT NULL,
        units_donated INTEGER DEFAULT 1,
        status VARCHAR(20) DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Donations table created successfully\n";

    // Create screenings table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS screenings (
        id SERIAL PRIMARY KEY,
        donor_id INTEGER REFERENCES donors(id) ON DELETE CASCADE,
        screening_date DATE NOT NULL,
        hemoglobin_level DECIMAL(4,2),
        blood_pressure_sys INTEGER,
        blood_pressure_dia INTEGER,
        temperature DECIMAL(3,1),
        eligibility_status BOOLEAN DEFAULT TRUE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Screenings table created successfully\n";

    // Insert sample hospitals if the table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM hospitals");
    if ($stmt->fetchColumn() == 0) {
        $hospitals = [
            ['Teaching Hospital, Peradeniya', 'Peradeniya, Kandy', '0812222500'],
            ['District Hospital, Matale', 'Matale', '0662233100'],
            ['National Hospital, Colombo', 'Colombo 7', '0112691111']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO hospitals (name, address, contact_number) VALUES (?, ?, ?)");
        foreach ($hospitals as $hospital) {
            $stmt->execute($hospital);
        }
        echo "Sample hospitals inserted\n";
    }

    echo "Database migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}