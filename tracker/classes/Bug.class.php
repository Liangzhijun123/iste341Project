<?php
require_once "Database.class.php";

class Bug {
    private $db;
    public function __construct() {
        $this->db = new Database();
    }

    // Get bugs by project for users
   public function getBugsByProject($projectId) {
    $sql = "SELECT * FROM bugs WHERE projectId = ?";
    return $this->db->query($sql, [$projectId]); 
}

    // Get all bugs (Manager/Admin)
   public function getAllBugs() {
    $sql = "SELECT * FROM bugs";
    return $this->db->query($sql);
}

   // Create a new bug
    public function createBug($summary, $description, $projectId, $priorityId, $ownerId) {
        $sql = "INSERT INTO bugs (summary, description, projectId, priorityId, statusId, ownerId, dateRaised) 
                VALUES (?, ?, ?, ?, 1, ?, NOW())";
                
        // Since we are inside the class, we are allowed to use $this->db!
        return $this->db->execute($sql, [$summary, $description, $projectId, $priorityId, $ownerId]);
    }

    // Update bug
    public function updateBug($id, $fields) {
        $set = [];
        $params = [];
        foreach($fields as $key => $value) {
            $set[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE bugs SET ".implode(", ", $set)." WHERE id = ?";
        return $this->db->execute($sql, $params);
    }

    // Get details for a single bug
    public function getBugById($id) {
        $sql = "SELECT * FROM bugs WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        
        // Return just the single row (the first item in the array), or null if not found
        return (count($result) > 0) ? $result[0] : null;
    }

    
}