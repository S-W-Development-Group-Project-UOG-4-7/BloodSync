<?php
// pages/admin/export_blood_requests.php
require_once __DIR__ . '/../../autoload.php';

// Check if user is admin
Auth::requireAdmin('../login.php');

// Get filter parameters from GET request
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$blood_type = $_GET['blood_type'] ?? '';
$urgency = $_GET['urgency'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with correct column names from setup.php
$query = "SELECT br.*, h.hospital_name, h.registration_number, h.location as hospital_location,
           u1.email as approved_by_email, u2.email as fulfilled_by_email,
           h.contact_person, h.contact_phone as hospital_phone, h.contact_email as hospital_email
    FROM blood_requests br
    JOIN hospitals h ON br.hospital_id = h.id
    LEFT JOIN users u1 ON br.approved_by = u1.id
    LEFT JOIN users u2 ON br.fulfilled_by = u2.id";
    
$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(br.patient_name ILIKE :search OR 
                     br.request_number ILIKE :search OR 
                     h.hospital_name ILIKE :search)";
    $params['search'] = "%{$search}%";
}

if (!empty($status) && $status !== 'all') {
    $conditions[] = "br.status = :status";
    $params['status'] = $status;
}

if (!empty($blood_type) && $blood_type !== 'all') {
    $conditions[] = "br.blood_type = :blood_type";
    $params['blood_type'] = $blood_type;
}

if (!empty($urgency) && $urgency !== 'all') {
    $conditions[] = "br.urgency_level = :urgency";
    $params['urgency'] = $urgency;
}

if (!empty($date_from)) {
    $conditions[] = "br.required_date >= :date_from";
    $params['date_from'] = $date_from;
}

if (!empty($date_to)) {
    $conditions[] = "br.required_date <= :date_to";
    $params['date_to'] = $date_to;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY 
    CASE br.urgency_level 
        WHEN 'critical' THEN 1
        WHEN 'urgent' THEN 2
        ELSE 3
    END, 
    br.required_date ASC, 
    br.created_at DESC";

// Fetch blood requests
try {
    $bloodRequests = Database::fetchAll($query, $params);
    
    if (empty($bloodRequests)) {
        $_SESSION['error'] = 'No data to export';
        header('Location: blood_requests.php');
        exit;
    }
    
    // Check what columns actually exist by examining the first row
    $firstRow = reset($bloodRequests);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=blood_requests_' . date('Y-m-d_H-i') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");
    
    // CSV Headers based on what we expect from the query
    $headers = [
        'Request Number',
        'Patient Name',
        'Patient Age',
        'Patient Sex',
        'Patient Ward',
        'Blood Type',
        'Units Required',
        'Urgency Level',
        'Required Date',
        'Status',
        'Hospital Name',
        'Hospital Registration',
        'Hospital Location',
        'Contact Person',
        'Hospital Phone',
        'Hospital Email',
        'Reason',
        'Admin Notes',
        'Approved By',
        'Approved At',
        'Fulfilled By',
        'Fulfilled At',
        'Created At',
        'Updated At'
    ];
    
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($bloodRequests as $request) {
        $row = [
            $request['request_number'] ?? '',
            $request['patient_name'] ?? '',
            $request['patient_age'] ?? '',
            $request['patient_sex'] ?? '',
            $request['patient_ward'] ?? '',
            $request['blood_type'] ?? '',
            $request['units_required'] ?? '',
            ucfirst($request['urgency_level'] ?? ''),
            $request['required_date'] ? date('Y-m-d', strtotime($request['required_date'])) : '',
            ucfirst($request['status'] ?? ''),
            $request['hospital_name'] ?? '',
            $request['registration_number'] ?? '',
            $request['hospital_location'] ?? '',
            $request['contact_person'] ?? '',
            $request['hospital_phone'] ?? '',
            $request['hospital_email'] ?? '',
            $request['reason'] ?? '',
            $request['admin_notes'] ?? '',
            $request['approved_by_email'] ?? '',
            $request['approved_at'] ? date('Y-m-d H:i:s', strtotime($request['approved_at'])) : '',
            $request['fulfilled_by_email'] ?? '',
            $request['fulfilled_at'] ? date('Y-m-d H:i:s', strtotime($request['fulfilled_at'])) : '',
            $request['created_at'] ? date('Y-m-d H:i:s', strtotime($request['created_at'])) : '',
            $request['updated_at'] ? date('Y-m-d H:i:s', strtotime($request['updated_at'])) : ''
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    error_log('Export error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error exporting data: ' . $e->getMessage();
    header('Location: blood_requests.php');
    exit;
}
?>