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
        
        // 1. Check if the database returned anything (does the username exist?)
        if (empty($results)) {
            return false; 
        }
        
        // 2. Since it's not empty, it is safe to grab the user data
        $user = $results[0];
        
        // 3. Verify the password
        if (password_verify($password, $user["Password"])) {
            return $user; // Login success! Return the user data array
        }
        
        return false; // Password was wrong
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


       // 3. Safely delete a user (Meeting the strict rubric requirements)
    public function deleteUser($userId) {
        // RULE: "must be removed from any bugs they are assigned to (the bug still remains)"
        // We set the ownerId and assignedToId to NULL instead of deleting the bug
        $this->db->execute("UPDATE bugs SET ownerId = NULL WHERE ownerId = ?", [$userId]);
        $this->db->execute("UPDATE bugs SET assignedToId = NULL WHERE assignedToId = ?", [$userId]);
        
        // RULE: "must be removed from any project"
        // Since their ProjectId is just a column on their user row, deleting the user automatically removes them from the project!
        $sql = "DELETE FROM user_details WHERE id = ?";
        return $this->db->execute($sql, [$userId]);
    }

    // 1. Get all users for the Admin table
    public function getAllUsers() {
        $sql = "SELECT id, Username, Name, RoleID, ProjectId FROM user_details";
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

  
    // 2. Add a new user (with automatic hashing!)
    public function addUser($username, $password, $name, $roleId) {
        // Hash the password exactly as the rubric requires
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO user_details (Username, Password, Name, RoleID) VALUES (?, ?, ?, ?)";
        return $this->db->execute($sql, [$username, $hashedPassword, $name, $roleId]);
    }

 


}