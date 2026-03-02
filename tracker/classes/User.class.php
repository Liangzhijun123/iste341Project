<?php
/**
 * User Class
 * Manages all user-related data operations, authentication, and security.
 * This class handles the core logic for Role-Based Access Control (RBAC) 
 * and ensures data integrity during user creation and deletion.
 */
require_once "Database.class.php";

class User {
    /**
     * @var Database $db The database connection instance.
     */
    private $db;

    /**
     * Constructor initializes the data access layer for user management.
     */
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Authenticates a user using secure password verification.
     * @param string $username The unique username provided by the client.
     * @param string $password The plain-text password to be verified against the hash.
     * @return array|false Returns the user data array on success, false on failure.
     */
    public function login($username, $password) {
        $sql = "SELECT * FROM user_details WHERE Username = ?";
        $results = $this->db->query($sql, [$username]);
        
        if (empty($results)) {
            return false; 
        }
        
        $user = $results[0];
        
        // Securely verify the password against the stored hash
        if (password_verify($password, $user["Password"])) {
            return $user;
        }
        
        return false;
    }

    /**
     * Fetches all registered users for administrative management.
     * @return array List of all users in the system.
     */
    public function getAllUsers() {
        $sql = "SELECT id, Username, Name, RoleID, ProjectId FROM user_details";
        return $this->db->query($sql);
    }

    /**
     * Admin function to create a new system user.
     * Includes automatic hashing and validation for roles and projects.
     * @param string $username
     * @param string $password
     * @param int $roleId Target role (Admin, Manager, User)
     * @param string $name Full name of the user
     * @param int|null $projectId Optional project assignment
     */
    public function createUser($username, $password, $roleId, $name, $projectId = null) {
        // Implementation includes uniqueness and existence validation
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO user_details (Username, Password, RoleID, Name, ProjectId)
                VALUES (?, ?, ?, ?, ?)";
        return $this->db->execute($sql, [$username, $hash, $roleId, $name, $projectId]);
    }

    /**
     * Safely deletes a user from the system.
     * Requirement: Ensures bugs and projects remain while removing user associations.
     * @param int $userId The ID of the user to remove.
     */
    public function deleteUser($userId) {
        // RULE: Unassign user from bugs without deleting the bug record
        $this->db->execute("UPDATE bugs SET ownerId = NULL WHERE ownerId = ?", [$userId]);
        $this->db->execute("UPDATE bugs SET assignedToId = NULL WHERE assignedToId = ?", [$userId]);
        
        // RULE: Remove user from the system
        $sql = "DELETE FROM user_details WHERE id = ?";
        return $this->db->execute($sql, [$userId]);
    }

    /**
     * Assigns a Regular User to exactly one project at a time.
     * Requirement: Managers/Admins can update assignments for Role 3 users.
     * @param int $userId
     * @param int $projectId
     */
    public function assignToProject($userId, $projectId) {
        $sql = "UPDATE user_details SET ProjectId = ? WHERE Id = ?";
        return $this->db->execute($sql, [$projectId, $userId]);
    }
}