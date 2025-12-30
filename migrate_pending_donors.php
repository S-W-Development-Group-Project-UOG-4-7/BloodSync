<?php
require_once __DIR__ . '/core/database.php';

try {
    $pdo = Database::getInstance();

    // Add status column to donors table if it doesn't exist
    $sql = "ALTER TABLE donors ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending'";
    $pdo->exec($sql);
    echo "Donors status column added successfully\n";

    // Add donor_number column to donors table if it doesn't exist
    $sql = "ALTER TABLE donors ADD COLUMN IF NOT EXISTS donor_number VARCHAR(20) UNIQUE";
    $pdo->exec($sql);
    echo "Donor number column added successfully\n";

    // Create notifications table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        recipient_type VARCHAR(20) NOT NULL, -- 'admin' or 'donor'
        recipient_id INTEGER, -- NULL for admin notifications, donor_id for donor-specific
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Notifications table created successfully\n";

    // Update any existing donors to have 'active' status and generate donor numbers
    $stmt = $pdo->prepare("SELECT id, created_at FROM donors WHERE status = 'pending'");
    $stmt->execute();
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($donors as $donor) {
        // Generate donor number in format: BLD-YYYY-XXXX
        $year = date('Y', strtotime($donor['created_at']));
        $donor_number = "BLD-" . $year . "-" . str_pad($donor['id'], 4, '0', STR_PAD_LEFT);
        
        $updateStmt = $pdo->prepare("UPDATE donors SET status = 'pending', donor_number = ? WHERE id = ?");
        $updateStmt->execute([$donor_number, $donor['id']]);
    }

    echo "Database migration for pending donors completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}