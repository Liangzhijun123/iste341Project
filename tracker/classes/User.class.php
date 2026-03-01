<?php
require_once "Database.class.php";

class User {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Login function
    public function login($username, $password) {
        $sql = "SELECT * FROM user_details WHERE Username = ?";
        $results = $this->db->query($sql, [$username]);
        
        // query() returns an array, get first result if exists
        if (empty($results)) {
            return false;
        }
        
        $user = $results[0];
        
        if (password_verify($password, $user["Password"])) {
            return $user;
        }
        return false;
    }

    // Get user by ID
    public function getUserById($id) {
        $sql = "SELECT * FROM user_details WHERE Id = ?";
        $results = $this->db->query($sql, [$id]);
        
        // query() returns an array, get first result if exists
        if (empty($results)) {
            return false;
        }
        
        return $results[0];
    }

    // Create new user (Admin only)
    public function createUser($username, $password, $roleId, $name, $projectId = null) {
        // Validate username uniqueness
        $sql = "SELECT Id FROM user_details WHERE Username = ?";
        $existing = $this->db->query($sql, [$username]);
        if (!empty($existing)) {
            return false; // Username already exists
        }
        
        // Validate role exists
        $sql = "SELECT Id FROM role WHERE Id = ?";
        $roleExists = $this->db->query($sql, [$roleId]);
        if (empty($roleExists)) {
            return false; // Role does not exist
        }
        
        // Validate project exists (if provided)
        if ($projectId !== null) {
            $sql = "SELECT Id FROM project WHERE Id = ?";
            $projectExists = $this->db->query($sql, [$projectId]);
            if (empty($projectExists)) {
                return false; // Project does not exist
            }
        }
        
        // Hash password and insert user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO user_details (Username, Password, RoleID, Name, ProjectId)
                VALUES (?, ?, ?, ?, ?)";
        return $this->db->execute($sql, [$username, $hash, $roleId, $name, $projectId]);
    }

    // Update user (Admin only)
    public function updateUser($id, $fields) {
        // Validate user exists
        $sql = "SELECT Id FROM user_details WHERE Id = ?";
        $userExists = $this->db->query($sql, [$id]);
        if (empty($userExists)) {
            return false; // User does not exist
        }

        // Validate role exists (if provided)
        if (isset($fields['RoleID'])) {
            $sql = "SELECT Id FROM role WHERE Id = ?";
            $roleExists = $this->db->query($sql, [$fields['RoleID']]);
            if (empty($roleExists)) {
                return false; // Role does not exist
            }
        }

        // Validate project exists (if provided and not null)
        if (isset($fields['ProjectId']) && $fields['ProjectId'] !== null) {
            $sql = "SELECT Id FROM project WHERE Id = ?";
            $projectExists = $this->db->query($sql, [$fields['ProjectId']]);
            if (empty($projectExists)) {
                return false; // Project does not exist
            }
        }

        // Hash password if included in update
        if (isset($fields['Password'])) {
            $fields['Password'] = password_hash($fields['Password'], PASSWORD_DEFAULT);
        }

        // Build dynamic UPDATE query
        $setClauses = [];
        $params = [];

        foreach ($fields as $field => $value) {
            $setClauses[] = "$field = ?";
            $params[] = $value;
        }

        // Add user ID as final parameter
        $params[] = $id;

        // Execute update
        $sql = "UPDATE user_details SET " . implode(", ", $setClauses) . " WHERE Id = ?";
        return $this->db->execute($sql, $params);
    }


    // Delete user (Admin only)
    public function deleteUser($id) {
        // Set assignedToId to NULL for all bugs assigned to this user
        $sql1 = "UPDATE bugs SET assignedToId = NULL WHERE assignedToId = ?";
        $this->db->execute($sql1, [$id]);

        // Set owner to NULL for all bugs owned by this user
        $sql2 = "UPDATE bugs SET owner = NULL WHERE owner = ?";
        $this->db->execute($sql2, [$id]);

        // Delete user record
        $sql3 = "DELETE FROM user_details WHERE Id = ?";
        return $this->db->execute($sql3, [$id]);
    }

    // Get all users (Admin only)
    public function getAllUsers() {
        $sql = "SELECT 
                    u.Id,
                    u.Username,
                    u.RoleID,
                    u.Name,
                    u.ProjectId,
                    r.Role as RoleName,
                    p.Project as ProjectName
                FROM user_details u
                LEFT JOIN role r ON u.RoleID = r.Id
                LEFT JOIN project p ON u.ProjectId = p.Id";
        return $this->db->query($sql);
    }

    // Assign Regular User to project (Manager/Admin only)
    public function assignToProject($userId, $projectId) {
        // Validate user exists and is Regular User (roleId=3)
        $sql = "SELECT Id, RoleID FROM user_details WHERE Id = ?";
        $userResults = $this->db->query($sql, [$userId]);

        if (empty($userResults)) {
            return false; // User does not exist
        }

        $user = $userResults[0];

        if ($user['RoleID'] != 3) {
            return false; // User is not a Regular User
        }

        // Validate project exists
        $sql = "SELECT Id FROM project WHERE Id = ?";
        $projectResults = $this->db->query($sql, [$projectId]);

        if (empty($projectResults)) {
            return false; // Project does not exist
        }

        // Remove any existing project assignment (ensure single project assignment)
        // Then update user's projectId to the new project
        $sql = "UPDATE user_details SET ProjectId = ? WHERE Id = ?";
        return $this->db->execute($sql, [$projectId, $userId]);
    }

    // Get users by project (Manager/Admin only)
    public function getUsersByProject($projectId) {
        $sql = "SELECT * FROM user_details WHERE ProjectId = ?";
        return $this->db->query($sql, [$projectId]);
    }


}