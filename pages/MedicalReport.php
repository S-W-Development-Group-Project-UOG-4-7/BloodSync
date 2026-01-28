<?php
class MedicalReport {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function create($data) {
        // Ensure all required fields are present
        $query = "INSERT INTO medical_reports (
                    donor_id, 
                    user_id,
                    title, 
                    file_name,
                    file_path, 
                    file_type,
                    file_size,
                    report_date, 
                    report_type, 
                    notes,
                    status
                  ) VALUES (
                    :donor_id, 
                    :user_id,
                    :title, 
                    :file_name,
                    :file_path, 
                    :file_type,
                    :file_size,
                    :report_date, 
                    :report_type, 
                    :notes,
                    'pending'
                  )";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($data);
    }
    
    public function getByDonor($donor_id, $limit = null) {
        $query = "SELECT * FROM medical_reports WHERE donor_id = :donor_id ORDER BY report_date DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':donor_id', $donor_id, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRecentByDonor($donor_id) {
        $query = "SELECT * FROM medical_reports WHERE donor_id = :donor_id 
                  ORDER BY report_date DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':donor_id' => $donor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getById($id, $donor_id = null) {
        $query = "SELECT * FROM medical_reports WHERE id = :id";
        
        if ($donor_id) {
            $query .= " AND donor_id = :donor_id";
        }
        
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        
        if ($donor_id) {
            $params[':donor_id'] = $donor_id;
        }
        
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update($id, $donor_id, $data) {
        $query = "UPDATE medical_reports SET title = :title, notes = :notes 
                  WHERE id = :id AND donor_id = :donor_id";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':title' => $data['title'],
            ':notes' => $data['notes'],
            ':id' => $id,
            ':donor_id' => $donor_id
        ]);
    }
    
    public function delete($id, $donor_id) {
        // First get file path to delete the file
        $report = $this->getById($id, $donor_id);
        
        if ($report && $report['file_path']) {
            $file_path = __DIR__ . '/../uploads/medical_reports/' . $report['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $query = "DELETE FROM medical_reports WHERE id = :id AND donor_id = :donor_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id, ':donor_id' => $donor_id]);
    }
    
    public function countByDonor($donor_id) {
        $query = "SELECT COUNT(*) as count FROM medical_reports WHERE donor_id = :donor_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':donor_id' => $donor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>