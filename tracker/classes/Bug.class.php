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
        $stmt = $this->db->query($sql, [$projectId]);
        return $stmt->fetchAll();
    }

    // Get all bugs (Manager/Admin)
    public function getAllBugs() {
        $sql = "SELECT * FROM bugs";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // Create new bug
    public function createBug($projectId, $ownerId, $statusId, $priorityId, $summary, $description, $targetDate = null) {
        $sql = "INSERT INTO bugs (projectId, ownerId, statusId, priorityId, summary, description, targetDate)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        return $this->db->execute($sql, [$projectId, $ownerId, $statusId, $priorityId, $summary, $description, $targetDate]);
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
}