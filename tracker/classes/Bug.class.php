<?php
/**
 * Bug Class
 * Handles all data operations related to bug reports within the system.
 * Connects to the Database class to perform CRUD operations.
 */
require_once "Database.class.php";

class Bug {
    /**
     * @var Database $db The database connection instance
     */
    private $db;

    /**
     * Constructor initializes the database connection.
     */
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Retrieves all bugs assigned to a specific project.
     * Used to enforce Role-Based Access for Regular Users.
     * * @param int $projectId The ID of the project to filter by.
     * @return array List of bugs for that project.
     */
   public function getBugsByProject($projectId) {
        $sql = "SELECT * FROM bugs WHERE projectId = ?";
        return $this->db->query($sql, [$projectId]); 
    }

    /**
     * Retrieves every bug in the system.
     * Used by Admins and Managers to maintain a global view of all projects.
     * * @return array List of all bugs across all projects.
     */
   public function getAllBugs() {
        $sql = "SELECT * FROM bugs";
        return $this->db->query($sql);
    }

   /**
    * Inserts a new bug report into the database.
    * Sets default status to 1 (Open) and uses NOW() for the timestamp.
    * * @param string $summary Brief title of the bug.
    * @param string $description Detailed explanation.
    * @param int $projectId Associated project ID.
    * @param int $priorityId Urgency level (1-4).
    * @param int $ownerId The user ID of the reporter.
    */
    public function createBug($summary, $description, $projectId, $priorityId, $ownerId) {
        $sql = "INSERT INTO bugs (summary, description, projectId, priorityId, statusId, ownerId, dateRaised) 
                VALUES (?, ?, ?, ?, 1, ?, NOW())";
                
        return $this->db->execute($sql, [$summary, $description, $projectId, $priorityId, $ownerId]);
    }

    /**
     * Updates specific fields of an existing bug.
     * Dynamically builds the SET clause based on provided fields array.
     * * @param int $id The unique ID of the bug.
     * @param array $fields Associative array of column => value (e.g. ['statusId' => 3]).
     */
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

    /**
     * Fetches details for one specific bug.
     * Used for the individual bug details view and the edit form.
     * * @param int $id The unique ID of the bug.
     * @return array|null The bug data or null if not found.
     */
    public function getBugById($id) {
        $sql = "SELECT * FROM bugs WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        
        return (count($result) > 0) ? $result[0] : null;
    }
}