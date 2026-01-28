<?php
// api/verify_donor.php
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

// Simple API key authentication (replace with proper JWT/OAuth)
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$validApiKey = 'your-secret-admin-api-key'; // Store in config

if ($apiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? null;
$status = $data['status'] ?? null; // 'approved' or 'rejected'
$note = $data['note'] ?? '';
$adminId = $data['admin_id'] ?? 1; // From admin authentication

if (!$userId || !in_array($status, ['approved', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();
    
    // Update user verification status
    $sql = "UPDATE users 
            SET is_verified = :is_verified, 
                verification_status = :status, 
                verification_note = :note,
                verified_at = NOW(),
                verified_by = :admin_id
            WHERE id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'is_verified' => $status === 'approved' ? true : false,
        'status' => $status,
        'note' => $note,
        'admin_id' => $adminId,
        'user_id' => $userId
    ]);
    
    // Update medical report status
    $sql = "UPDATE medical_reports 
            SET status = :status, 
                reviewed_by = :admin_id,
                reviewed_at = NOW(),
                notes = :note
            WHERE user_id = :user_id 
            ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'status' => $status,
        'admin_id' => $adminId,
        'note' => $note,
        'user_id' => $userId
    ]);
    
    // Create notification for user
    $notificationTitle = $status === 'approved' ? 'Account Verified' : 'Verification Required';
    $notificationMessage = $status === 'approved' 
        ? 'Your account has been verified. You can now access all features.'
        : ($note ?: 'Please upload a valid medical report.');
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, metadata) 
            VALUES (:user_id, 'verification', :title, :message, :metadata)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $userId,
        'title' => $notificationTitle,
        'message' => $notificationMessage,
        'metadata' => json_encode(['status' => $status])
    ]);
    
    // Update user's session if they're logged in (optional - you'd need session management)
    // This could be handled by having the frontend check verification status periodically
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification updated successfully',
        'data' => [
            'user_id' => $userId,
            'status' => $status,
            'verified_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>