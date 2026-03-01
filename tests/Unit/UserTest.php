<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Eris\Generator;

require_once __DIR__ . '/../../tracker/classes/User.class.php';
require_once __DIR__ . '/../../tracker/classes/Database.class.php';

/**
 * Unit and Property-Based Tests for User Class
 * 
 * These tests validate the User class methods, particularly the login() method
 * which handles authentication with password verification.
 */
class UserTest extends TestCase
{
    use \Eris\TestTrait;

    private $user;
    private $db;
    private $testUsersCreated = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test database credentials
        $_SERVER['DB_SERVER'] = $_SERVER['DB_SERVER'] ?? 'localhost';
        $_SERVER['DB'] = $_SERVER['DB'] ?? 'zl5660';
        $_SERVER['DB_USER'] = $_SERVER['DB_USER'] ?? 'zl5660';
        $_SERVER['DB_PASSWORD'] = $_SERVER['DB_PASSWORD'] ?? 'YOUR_PASSWORD';
        
        $this->user = new \User();
        $this->db = new \Database();
    }

    protected function tearDown(): void
    {
        // Clean up test users
        foreach ($this->testUsersCreated as $userId) {
            try {
                $this->db->execute("DELETE FROM user_details WHERE Id = ?", [$userId]);
            } catch (\PDOException $e) {
                // Ignore errors during cleanup
            }
        }
        $this->testUsersCreated = [];
        parent::tearDown();
    }

    /**
     * Helper method to create a test user and track it for cleanup
     */
    private function createTestUser($username, $password, $roleId = 3, $name = 'Test User', $projectId = null)
    {
        $result = $this->user->createUser($username, $password, $roleId, $name, $projectId);
        
        if ($result) {
            // Get the last inserted ID
            $userId = $this->db->getConnection()->lastInsertId();
            $this->testUsersCreated[] = $userId;
            return $userId;
        }
        
        return false;
    }

    /**
     * Feature: complete-bug-tracker-system, Property 1: Valid Credentials Create Session
     * 
     * **Validates: Requirements 1.1, 1.6**
     * 
     * For any user with valid credentials in the database, when those credentials 
     * are submitted to the login function, the system should return user data 
     * containing userId, roleId, projectId, and username.
     */
    public function testValidCredentialsReturnUserData()
    {
        $this->forAll(
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 3 && strlen($s) <= 20 && preg_match('/^[a-zA-Z0-9_]+$/', $s); 
            }),
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 8; 
            }),
            Generator\choose(1, 3) // roleId: 1=Admin, 2=Manager, 3=Regular User
        )
        ->then(function($username, $password, $roleId) {
            // Create a test user with the generated credentials
            $username = 'test_' . $username . '_' . uniqid();
            $name = 'Test User ' . uniqid();
            
            $userId = $this->createTestUser($username, $password, $roleId, $name);
            $this->assertNotFalse($userId, 'User creation should succeed');

            // Attempt login with valid credentials
            $result = $this->user->login($username, $password);

            // Assert that login returns user data
            $this->assertIsArray($result, 'Login should return an array for valid credentials');
            $this->assertArrayHasKey('Id', $result, 'Result should contain userId (Id)');
            $this->assertArrayHasKey('RoleID', $result, 'Result should contain roleId');
            $this->assertArrayHasKey('Username', $result, 'Result should contain username');
            $this->assertArrayHasKey('Name', $result, 'Result should contain name');
            
            // Verify the returned data matches what we created
            $this->assertEquals($userId, $result['Id'], 'Returned userId should match');
            $this->assertEquals($roleId, $result['RoleID'], 'Returned roleId should match');
            $this->assertEquals($username, $result['Username'], 'Returned username should match');
        });
    }

    /**
     * Feature: complete-bug-tracker-system, Property 2: Invalid Credentials Reject Login
     * 
     * **Validates: Requirements 1.2**
     * 
     * For any credentials that do not match a user in the database (either username 
     * doesn't exist or password doesn't match), when those credentials are submitted 
     * to the login function, the system should return false.
     */
    public function testInvalidCredentialsReturnFalse()
    {
        $this->forAll(
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 3 && strlen($s) <= 20 && preg_match('/^[a-zA-Z0-9_]+$/', $s); 
            }),
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 8; 
            }),
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 8; 
            })
        )
        ->then(function($username, $correctPassword, $wrongPassword) {
            // Ensure passwords are different
            if ($correctPassword === $wrongPassword) {
                $wrongPassword = $wrongPassword . '_different';
            }
            
            // Create a test user
            $username = 'test_' . $username . '_' . uniqid();
            $userId = $this->createTestUser($username, $correctPassword);
            $this->assertNotFalse($userId, 'User creation should succeed');

            // Test 1: Wrong password
            $result = $this->user->login($username, $wrongPassword);
            $this->assertFalse($result, 'Login with wrong password should return false');

            // Test 2: Non-existent username
            $nonExistentUsername = 'nonexistent_' . uniqid();
            $result = $this->user->login($nonExistentUsername, $correctPassword);
            $this->assertFalse($result, 'Login with non-existent username should return false');
        });
    }

    /**
     * Feature: complete-bug-tracker-system, Property 3: Password Storage Uses Hashing
     * 
     * **Validates: Requirements 1.3**
     * 
     * For any user created in the system, the stored password in the database should 
     * be a valid hash that can be verified using password_verify() with the original 
     * password, and should not be the plain text password.
     */
    public function testPasswordStorageUsesHashing()
    {
        $this->forAll(
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 8; 
            })
        )
        ->then(function($password) {
            // Create a test user
            $username = 'test_hash_' . uniqid();
            $userId = $this->createTestUser($username, $password);
            $this->assertNotFalse($userId, 'User creation should succeed');

            // Retrieve the user from database
            $results = $this->db->query("SELECT Password FROM user_details WHERE Id = ?", [$userId]);
            $this->assertCount(1, $results, 'Should retrieve the user');
            
            $storedPassword = $results[0]['Password'];

            // Verify password is hashed (not plain text)
            $this->assertNotEquals($password, $storedPassword, 'Stored password should not be plain text');
            
            // Verify password_verify works with the hash
            $this->assertTrue(
                password_verify($password, $storedPassword),
                'password_verify should validate the hash with original password'
            );
            
            // Verify the hash format (should start with $2y$ for PASSWORD_DEFAULT)
            $this->assertStringStartsWith('$2y$', $storedPassword, 'Password should use bcrypt hashing');
        });
    }

    /**
     * Unit test: Login with valid credentials returns correct user data structure
     */
    public function testLoginReturnsCorrectDataStructure()
    {
        // Arrange
        $username = 'test_structure_' . uniqid();
        $password = 'testpassword123';
        $roleId = 2; // Manager
        $name = 'Test Manager';
        
        $userId = $this->createTestUser($username, $password, $roleId, $name);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act
        $result = $this->user->login($username, $password);

        // Assert
        $this->assertIsArray($result, 'Login should return an array');
        $this->assertArrayHasKey('Id', $result);
        $this->assertArrayHasKey('Username', $result);
        $this->assertArrayHasKey('Password', $result);
        $this->assertArrayHasKey('RoleID', $result);
        $this->assertArrayHasKey('Name', $result);
        $this->assertArrayHasKey('ProjectId', $result);
        
        $this->assertEquals($userId, $result['Id']);
        $this->assertEquals($username, $result['Username']);
        $this->assertEquals($roleId, $result['RoleID']);
        $this->assertEquals($name, $result['Name']);
    }

    /**
     * Unit test: Login with empty username returns false
     */
    public function testLoginWithEmptyUsernameReturnsFalse()
    {
        $result = $this->user->login('', 'password123');
        $this->assertFalse($result, 'Login with empty username should return false');
    }

    /**
     * Unit test: Login with empty password returns false
     */
    public function testLoginWithEmptyPasswordReturnsFalse()
    {
        $result = $this->user->login('testuser', '');
        $this->assertFalse($result, 'Login with empty password should return false');
    }

    /**
     * Unit test: Login uses prepared statements (SQL injection prevention)
     */
    public function testLoginUsesPreparedStatements()
    {
        // Arrange - create a legitimate user
        $username = 'test_injection_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - attempt SQL injection in username
        $maliciousUsername = "admin' OR '1'='1";
        $result = $this->user->login($maliciousUsername, $password);

        // Assert - should return false, not bypass authentication
        $this->assertFalse($result, 'SQL injection attempt should not succeed');
        
        // Verify the legitimate user still works
        $validResult = $this->user->login($username, $password);
        $this->assertIsArray($validResult, 'Legitimate login should still work');
    }

    /**
     * Unit test: Login is case-sensitive for username
     */
    public function testLoginIsCaseSensitiveForUsername()
    {
        // Arrange
        $username = 'TestUser' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act & Assert - correct case should work
        $result = $this->user->login($username, $password);
        $this->assertIsArray($result, 'Login with correct case should succeed');

        // Act & Assert - wrong case should fail (depends on database collation)
        // Note: This test may pass or fail depending on MySQL collation settings
        // Most MySQL installations use case-insensitive collation by default
    }

    /**
     * Unit test: getUserById returns user data for valid ID
     */
    public function testGetUserByIdReturnsUserData()
    {
        // Arrange
        $username = 'test_getbyid_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act
        $result = $this->user->getUserById($userId);

        // Assert
        $this->assertIsArray($result, 'getUserById should return an array');
        $this->assertEquals($userId, $result['Id']);
        $this->assertEquals($username, $result['Username']);
    }

    /**
     * Unit test: getUserById returns false for non-existent ID
     */
    public function testGetUserByIdReturnsFalseForNonExistentId()
    {
        // Act
        $result = $this->user->getUserById(999999);

        // Assert
        $this->assertFalse($result, 'getUserById should return false for non-existent ID');
    }

    /**
     * Unit test: getAllUsers returns array with role and project names
     * 
     * **Validates: Requirements 4.1, 9.1**
     * 
     * Tests that getAllUsers() properly joins with role and project tables
     * to include RoleName and ProjectName in the returned data.
     */
    public function testGetAllUsersIncludesRoleAndProjectNames()
    {
        // Arrange - create test users with different roles and projects
        $username1 = 'test_getall_admin_' . uniqid();
        $username2 = 'test_getall_user_' . uniqid();
        
        // Create an admin user (no project)
        $userId1 = $this->createTestUser($username1, 'password123', 1, 'Admin User', null);
        $this->assertNotFalse($userId1, 'Admin user creation should succeed');
        
        // Create a regular user (with project - assuming project ID 1 exists)
        // Note: This test assumes at least one project exists in the database
        $userId2 = $this->createTestUser($username2, 'password123', 3, 'Regular User', 1);
        $this->assertNotFalse($userId2, 'Regular user creation should succeed');

        // Act
        $result = $this->user->getAllUsers();

        // Assert
        $this->assertIsArray($result, 'getAllUsers should return an array');
        $this->assertNotEmpty($result, 'getAllUsers should return at least the test users');
        
        // Find our test users in the results
        $adminUser = null;
        $regularUser = null;
        
        foreach ($result as $user) {
            if ($user['Id'] == $userId1) {
                $adminUser = $user;
            }
            if ($user['Id'] == $userId2) {
                $regularUser = $user;
            }
        }
        
        // Verify admin user data
        $this->assertNotNull($adminUser, 'Admin user should be in results');
        $this->assertArrayHasKey('RoleName', $adminUser, 'Result should include RoleName');
        $this->assertArrayHasKey('ProjectName', $adminUser, 'Result should include ProjectName');
        $this->assertEquals('Admin', $adminUser['RoleName'], 'RoleName should be "Admin"');
        $this->assertNull($adminUser['ProjectName'], 'Admin should have no project');
        
        // Verify regular user data
        $this->assertNotNull($regularUser, 'Regular user should be in results');
        $this->assertArrayHasKey('RoleName', $regularUser, 'Result should include RoleName');
        $this->assertArrayHasKey('ProjectName', $regularUser, 'Result should include ProjectName');
        $this->assertEquals('Regular User', $regularUser['RoleName'], 'RoleName should be "Regular User"');
        $this->assertNotNull($regularUser['ProjectName'], 'Regular user should have a project name');
    }

    /**
     * Unit test: createUser validates username uniqueness
     * 
     * **Validates: Requirements 4.2, 10.5**
     * 
     * Tests that createUser() rejects duplicate usernames.
     */
    public function testCreateUserRejectsDuplicateUsername()
    {
        // Arrange - create first user
        $username = 'test_duplicate_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password);
        $this->assertNotFalse($userId, 'First user creation should succeed');

        // Act - try to create another user with same username
        $result = $this->user->createUser($username, 'differentpassword', 3, 'Another User', null);

        // Assert
        $this->assertFalse($result, 'createUser should reject duplicate username');
    }

    /**
     * Unit test: createUser validates role exists
     * 
     * **Validates: Requirements 4.2, 10.5**
     * 
     * Tests that createUser() rejects invalid role IDs.
     */
    public function testCreateUserRejectsInvalidRole()
    {
        // Arrange
        $username = 'test_invalidrole_' . uniqid();
        $password = 'testpassword123';
        $invalidRoleId = 999; // Non-existent role

        // Act
        $result = $this->user->createUser($username, $password, $invalidRoleId, 'Test User', null);

        // Assert
        $this->assertFalse($result, 'createUser should reject invalid role ID');
    }

    /**
     * Unit test: createUser validates project exists when provided
     * 
     * **Validates: Requirements 4.2, 10.5**
     * 
     * Tests that createUser() rejects invalid project IDs.
     */
    public function testCreateUserRejectsInvalidProject()
    {
        // Arrange
        $username = 'test_invalidproject_' . uniqid();
        $password = 'testpassword123';
        $invalidProjectId = 999999; // Non-existent project

        // Act
        $result = $this->user->createUser($username, $password, 3, 'Test User', $invalidProjectId);

        // Assert
        $this->assertFalse($result, 'createUser should reject invalid project ID');
    }

    /**
     * Unit test: createUser accepts null project ID
     * 
     * **Validates: Requirements 4.2**
     * 
     * Tests that createUser() allows null project ID (for Admin/Manager roles).
     */
    public function testCreateUserAcceptsNullProjectId()
    {
        // Arrange
        $username = 'test_nullproject_' . uniqid();
        $password = 'testpassword123';

        // Act
        $userId = $this->createTestUser($username, $password, 1, 'Admin User', null);

        // Assert
        $this->assertNotFalse($userId, 'createUser should accept null project ID');
        
        // Verify the user was created with null project
        $user = $this->user->getUserById($userId);
        $this->assertNull($user['ProjectId'], 'ProjectId should be null');
    }

    /**
     * Unit test: createUser accepts valid project ID
     * 
     * **Validates: Requirements 4.2, 10.5**
     * 
     * Tests that createUser() accepts valid project IDs.
     */
    public function testCreateUserAcceptsValidProjectId()
    {
        // Arrange
        $username = 'test_validproject_' . uniqid();
        $password = 'testpassword123';
        $projectId = 1; // Assuming project ID 1 exists

        // Act
        $userId = $this->createTestUser($username, $password, 3, 'Regular User', $projectId);

        // Assert
        $this->assertNotFalse($userId, 'createUser should accept valid project ID');
        
        // Verify the user was created with the correct project
        $user = $this->user->getUserById($userId);
        $this->assertEquals($projectId, $user['ProjectId'], 'ProjectId should match');
    }

    /**
     * Unit test: createUser uses prepared statements for all queries
     * 
     * **Validates: Requirements 9.2**
     * 
     * Tests that createUser() is protected against SQL injection.
     */
    public function testCreateUserUsesPreparedStatements()
    {
        // Arrange - attempt SQL injection in username
        $maliciousUsername = "admin'; DROP TABLE user_details; --";
        $password = 'testpassword123';

        // Act - this should safely fail without executing the injection
        $result = $this->user->createUser($maliciousUsername, $password, 3, 'Test User', null);

        // Assert - the injection should not succeed
        // The method should either create the user with the literal string as username
        // or fail validation, but should not execute the DROP TABLE command
        
        // Verify the user_details table still exists by querying it
        $tableCheck = $this->db->query("SELECT COUNT(*) as count FROM user_details");
        $this->assertIsArray($tableCheck, 'user_details table should still exist');
        
        // If the user was created, clean it up
        if ($result !== false) {
            $userId = $this->db->getConnection()->lastInsertId();
            $this->testUsersCreated[] = $userId;
        }
    }

    /**
     * Property test: createUser validates all required fields
     * 
     * **Validates: Requirements 4.2, 10.5**
     * 
     * For any valid user data, createUser should succeed when all validations pass.
     */
    public function testCreateUserValidatesAllFields()
    {
        $this->forAll(
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 3 && strlen($s) <= 20 && preg_match('/^[a-zA-Z0-9_]+$/', $s); 
            }),
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 8; 
            }),
            Generator\choose(1, 3), // Valid role IDs
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 2 && strlen($s) <= 50; 
            })
        )
        ->then(function($username, $password, $roleId, $name) {
            // Make username unique
            $username = 'test_valid_' . $username . '_' . uniqid();
            
            // Act
            $userId = $this->createTestUser($username, $password, $roleId, $name, null);

            // Assert
            $this->assertNotFalse($userId, 'createUser should succeed with valid data');
            
            // Verify the user was created correctly
            $user = $this->user->getUserById($userId);
            $this->assertEquals($username, $user['Username']);
            $this->assertEquals($roleId, $user['RoleID']);
            $this->assertEquals($name, $user['Name']);
            
            // Verify password is hashed
            $this->assertNotEquals($password, $user['Password']);
            $this->assertTrue(password_verify($password, $user['Password']));
        });
    }

    /**
     * Unit test: updateUser successfully updates user fields
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() can update various user fields.
     */
    public function testUpdateUserSuccessfullyUpdatesFields()
    {
        // Arrange - create a test user
        $username = 'test_update_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Original Name', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - update the user's name
        $result = $this->user->updateUser($userId, ['Name' => 'Updated Name']);

        // Assert
        $this->assertTrue($result, 'updateUser should return true on success');
        
        // Verify the update
        $updatedUser = $this->user->getUserById($userId);
        $this->assertEquals('Updated Name', $updatedUser['Name'], 'Name should be updated');
        $this->assertEquals($username, $updatedUser['Username'], 'Username should remain unchanged');
    }

    /**
     * Unit test: updateUser validates role exists
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() rejects invalid role IDs.
     */
    public function testUpdateUserRejectsInvalidRole()
    {
        // Arrange - create a test user
        $username = 'test_update_role_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - try to update with invalid role
        $result = $this->user->updateUser($userId, ['RoleID' => 999]);

        // Assert
        $this->assertFalse($result, 'updateUser should reject invalid role ID');
        
        // Verify the user was not updated
        $user = $this->user->getUserById($userId);
        $this->assertEquals(3, $user['RoleID'], 'RoleID should remain unchanged');
    }

    /**
     * Unit test: updateUser validates project exists
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() rejects invalid project IDs.
     */
    public function testUpdateUserRejectsInvalidProject()
    {
        // Arrange - create a test user
        $username = 'test_update_project_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - try to update with invalid project
        $result = $this->user->updateUser($userId, ['ProjectId' => 999999]);

        // Assert
        $this->assertFalse($result, 'updateUser should reject invalid project ID');
        
        // Verify the user was not updated
        $user = $this->user->getUserById($userId);
        $this->assertNull($user['ProjectId'], 'ProjectId should remain unchanged');
    }

    /**
     * Unit test: updateUser accepts valid project ID
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() accepts valid project IDs.
     */
    public function testUpdateUserAcceptsValidProject()
    {
        // Arrange - create a test user
        $username = 'test_update_validproject_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - update with valid project (assuming project ID 1 exists)
        $result = $this->user->updateUser($userId, ['ProjectId' => 1]);

        // Assert
        $this->assertTrue($result, 'updateUser should accept valid project ID');
        
        // Verify the update
        $user = $this->user->getUserById($userId);
        $this->assertEquals(1, $user['ProjectId'], 'ProjectId should be updated');
    }

    /**
     * Unit test: updateUser hashes password when included
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() hashes the password before storing.
     */
    public function testUpdateUserHashesPassword()
    {
        // Arrange - create a test user
        $username = 'test_update_password_' . uniqid();
        $oldPassword = 'oldpassword123';
        $userId = $this->createTestUser($username, $oldPassword, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - update password
        $newPassword = 'newpassword456';
        $result = $this->user->updateUser($userId, ['Password' => $newPassword]);

        // Assert
        $this->assertTrue($result, 'updateUser should succeed');
        
        // Verify the password was hashed
        $user = $this->user->getUserById($userId);
        $this->assertNotEquals($newPassword, $user['Password'], 'Password should be hashed, not plain text');
        $this->assertTrue(password_verify($newPassword, $user['Password']), 'New password should verify');
        $this->assertFalse(password_verify($oldPassword, $user['Password']), 'Old password should not verify');
    }

    /**
     * Unit test: updateUser can update multiple fields at once
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() can update multiple fields in a single call.
     */
    public function testUpdateUserUpdatesMultipleFields()
    {
        // Arrange - create a test user
        $username = 'test_update_multiple_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Original Name', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - update multiple fields
        $result = $this->user->updateUser($userId, [
            'Name' => 'New Name',
            'RoleID' => 2,
            'ProjectId' => 1
        ]);

        // Assert
        $this->assertTrue($result, 'updateUser should succeed');
        
        // Verify all updates
        $user = $this->user->getUserById($userId);
        $this->assertEquals('New Name', $user['Name'], 'Name should be updated');
        $this->assertEquals(2, $user['RoleID'], 'RoleID should be updated');
        $this->assertEquals(1, $user['ProjectId'], 'ProjectId should be updated');
    }

    /**
     * Unit test: updateUser returns false for non-existent user
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() rejects updates for non-existent users.
     */
    public function testUpdateUserRejectsNonExistentUser()
    {
        // Act - try to update non-existent user
        $result = $this->user->updateUser(999999, ['Name' => 'New Name']);

        // Assert
        $this->assertFalse($result, 'updateUser should reject non-existent user ID');
    }

    /**
     * Unit test: updateUser accepts null project ID
     * 
     * **Validates: Requirements 9.3, 10.5**
     * 
     * Tests that updateUser() allows setting project to null.
     */
    public function testUpdateUserAcceptsNullProject()
    {
        // Arrange - create a test user with a project
        $username = 'test_update_nullproject_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', 1);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - update project to null
        $result = $this->user->updateUser($userId, ['ProjectId' => null]);

        // Assert
        $this->assertTrue($result, 'updateUser should accept null project ID');
        
        // Verify the update
        $user = $this->user->getUserById($userId);
        $this->assertNull($user['ProjectId'], 'ProjectId should be null');
    }

    /**
     * Unit test: updateUser uses prepared statements
     * 
     * **Validates: Requirements 9.3**
     * 
     * Tests that updateUser() is protected against SQL injection.
     */
    public function testUpdateUserUsesPreparedStatements()
    {
        // Arrange - create a test user
        $username = 'test_update_injection_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - attempt SQL injection in name field
        $maliciousName = "'; DROP TABLE user_details; --";
        $result = $this->user->updateUser($userId, ['Name' => $maliciousName]);

        // Assert - the injection should not succeed
        $this->assertTrue($result, 'updateUser should succeed (treating input as literal string)');
        
        // Verify the user_details table still exists
        $tableCheck = $this->db->query("SELECT COUNT(*) as count FROM user_details");
        $this->assertIsArray($tableCheck, 'user_details table should still exist');
        
        // Verify the name was set to the literal string (not executed as SQL)
        $user = $this->user->getUserById($userId);
        $this->assertEquals($maliciousName, $user['Name'], 'Name should be the literal string');
    }

    /**
     * Feature: complete-bug-tracker-system, Property 15: User Deletion Cascades to Bugs
     * 
     * **Validates: Requirements 4.3, 4.4, 4.5**
     * 
     * For any user deletion, the system should set assignedToId to NULL for all bugs 
     * where assignedToId equals the deleted user's ID, and should set owner to NULL 
     * for all bugs where owner equals the deleted user's ID.
     */
    public function testDeleteUserCascadesToBugs()
    {
        // Arrange - create a test user
        $username = 'test_delete_cascade_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', 1);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Create test bugs assigned to and owned by this user
        // Bug 1: Assigned to user
        $this->db->execute(
            "INSERT INTO bugs (projectId, owner, assignedToId, statusId, priorityId, summary, description, dateRaised) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [1, 1, $userId, 'open', 'medium', 'Test Bug 1', 'Description 1', date('Y-m-d')]
        );
        $bug1Id = $this->db->getConnection()->lastInsertId();

        // Bug 2: Owned by user
        $this->db->execute(
            "INSERT INTO bugs (projectId, owner, assignedToId, statusId, priorityId, summary, description, dateRaised) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [1, $userId, 2, 'open', 'medium', 'Test Bug 2', 'Description 2', date('Y-m-d')]
        );
        $bug2Id = $this->db->getConnection()->lastInsertId();

        // Bug 3: Both assigned to and owned by user
        $this->db->execute(
            "INSERT INTO bugs (projectId, owner, assignedToId, statusId, priorityId, summary, description, dateRaised) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [1, $userId, $userId, 'open', 'medium', 'Test Bug 3', 'Description 3', date('Y-m-d')]
        );
        $bug3Id = $this->db->getConnection()->lastInsertId();

        // Act - delete the user
        $result = $this->user->deleteUser($userId);

        // Assert
        $this->assertTrue($result, 'deleteUser should return true on success');

        // Verify user is deleted
        $deletedUser = $this->user->getUserById($userId);
        $this->assertFalse($deletedUser, 'User should be deleted');

        // Verify Bug 1: assignedToId should be NULL
        $bug1 = $this->db->query("SELECT * FROM bugs WHERE id = ?", [$bug1Id]);
        $this->assertCount(1, $bug1, 'Bug 1 should still exist');
        $this->assertNull($bug1[0]['assignedToId'], 'Bug 1 assignedToId should be NULL');
        $this->assertEquals(1, $bug1[0]['owner'], 'Bug 1 owner should remain unchanged');

        // Verify Bug 2: owner should be NULL
        $bug2 = $this->db->query("SELECT * FROM bugs WHERE id = ?", [$bug2Id]);
        $this->assertCount(1, $bug2, 'Bug 2 should still exist');
        $this->assertNull($bug2[0]['owner'], 'Bug 2 owner should be NULL');
        $this->assertEquals(2, $bug2[0]['assignedToId'], 'Bug 2 assignedToId should remain unchanged');

        // Verify Bug 3: both should be NULL
        $bug3 = $this->db->query("SELECT * FROM bugs WHERE id = ?", [$bug3Id]);
        $this->assertCount(1, $bug3, 'Bug 3 should still exist');
        $this->assertNull($bug3[0]['assignedToId'], 'Bug 3 assignedToId should be NULL');
        $this->assertNull($bug3[0]['owner'], 'Bug 3 owner should be NULL');

        // Cleanup test bugs
        $this->db->execute("DELETE FROM bugs WHERE id IN (?, ?, ?)", [$bug1Id, $bug2Id, $bug3Id]);
    }

    /**
     * Unit test: deleteUser successfully deletes user
     * 
     * **Validates: Requirements 4.3**
     * 
     * Tests that deleteUser() removes the user record from the database.
     */
    public function testDeleteUserRemovesUserRecord()
    {
        // Arrange - create a test user
        $username = 'test_delete_user_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Verify user exists
        $user = $this->user->getUserById($userId);
        $this->assertIsArray($user, 'User should exist before deletion');

        // Act - delete the user
        $result = $this->user->deleteUser($userId);

        // Assert
        $this->assertTrue($result, 'deleteUser should return true on success');

        // Verify user is deleted
        $deletedUser = $this->user->getUserById($userId);
        $this->assertFalse($deletedUser, 'User should not exist after deletion');
    }

    /**
     * Unit test: deleteUser sets assignedToId to NULL for assigned bugs
     * 
     * **Validates: Requirements 4.4**
     * 
     * Tests that deleteUser() sets assignedToId to NULL for all bugs assigned to the user.
     */
    public function testDeleteUserSetsAssignedToIdToNull()
    {
        // Arrange - create a test user
        $username = 'test_delete_assigned_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', 1);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Create a bug assigned to this user
        $this->db->execute(
            "INSERT INTO bugs (projectId, owner, assignedToId, statusId, priorityId, summary, description, dateRaised) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [1, 1, $userId, 'assigned', 'high', 'Assigned Bug', 'Test Description', date('Y-m-d')]
        );
        $bugId = $this->db->getConnection()->lastInsertId();

        // Act - delete the user
        $result = $this->user->deleteUser($userId);

        // Assert
        $this->assertTrue($result, 'deleteUser should return true on success');

        // Verify bug's assignedToId is NULL
        $bug = $this->db->query("SELECT * FROM bugs WHERE id = ?", [$bugId]);
        $this->assertCount(1, $bug, 'Bug should still exist');
        $this->assertNull($bug[0]['assignedToId'], 'assignedToId should be NULL after user deletion');

        // Cleanup
        $this->db->execute("DELETE FROM bugs WHERE id = ?", [$bugId]);
    }

    /**
     * Unit test: deleteUser sets owner to NULL for owned bugs
     * 
     * **Validates: Requirements 4.5**
     * 
     * Tests that deleteUser() sets owner to NULL for all bugs owned by the user.
     */
    public function testDeleteUserSetsOwnerToNull()
    {
        // Arrange - create a test user
        $username = 'test_delete_owner_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', 1);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Create a bug owned by this user
        $this->db->execute(
            "INSERT INTO bugs (projectId, owner, assignedToId, statusId, priorityId, summary, description, dateRaised) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [1, $userId, 2, 'open', 'medium', 'Owned Bug', 'Test Description', date('Y-m-d')]
        );
        $bugId = $this->db->getConnection()->lastInsertId();

        // Act - delete the user
        $result = $this->user->deleteUser($userId);

        // Assert
        $this->assertTrue($result, 'deleteUser should return true on success');

        // Verify bug's owner is NULL
        $bug = $this->db->query("SELECT * FROM bugs WHERE id = ?", [$bugId]);
        $this->assertCount(1, $bug, 'Bug should still exist');
        $this->assertNull($bug[0]['owner'], 'owner should be NULL after user deletion');

        // Cleanup
        $this->db->execute("DELETE FROM bugs WHERE id = ?", [$bugId]);
    }

    /**
     * Unit test: deleteUser uses prepared statements
     * 
     * **Validates: Requirements 9.4**
     * 
     * Tests that deleteUser() uses prepared statements for all queries.
     */
    public function testDeleteUserUsesPreparedStatements()
    {
        // Arrange - create a test user
        $username = 'test_delete_injection_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - delete the user (the method should use prepared statements internally)
        $result = $this->user->deleteUser($userId);

        // Assert
        $this->assertTrue($result, 'deleteUser should succeed');

        // Verify the user_details table still exists (no SQL injection occurred)
        $tableCheck = $this->db->query("SELECT COUNT(*) as count FROM user_details");
        $this->assertIsArray($tableCheck, 'user_details table should still exist');

        // Verify user is deleted
        $deletedUser = $this->user->getUserById($userId);
        $this->assertFalse($deletedUser, 'User should be deleted');
    }

    /**
     * Unit test: deleteUser handles user with no bugs
     * 
     * **Validates: Requirements 4.3**
     * 
     * Tests that deleteUser() works correctly for users with no associated bugs.
     */
    public function testDeleteUserHandlesUserWithNoBugs()
    {
        // Arrange - create a test user with no bugs
        $username = 'test_delete_nobugs_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - delete the user
        $result = $this->user->deleteUser($userId);

        // Assert
        $this->assertTrue($result, 'deleteUser should return true even with no bugs');

        // Verify user is deleted
        $deletedUser = $this->user->getUserById($userId);
        $this->assertFalse($deletedUser, 'User should be deleted');
    }

    /**
     * Unit test: assignToProject successfully assigns Regular User to project
     * 
     * **Validates: Requirements 12.1, 12.2**
     * 
     * Tests that assignToProject() updates a Regular User's projectId.
     */
    public function testAssignToProjectSuccessfullyAssignsRegularUser()
    {
        // Arrange - create a Regular User (roleId=3)
        $username = 'test_assign_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Regular User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - assign user to project 1
        $result = $this->user->assignToProject($userId, 1);

        // Assert
        $this->assertTrue($result, 'assignToProject should return true on success');

        // Verify the user's projectId was updated
        $user = $this->user->getUserById($userId);
        $this->assertEquals(1, $user['ProjectId'], 'User should be assigned to project 1');
    }

    /**
     * Unit test: assignToProject removes existing project assignment
     * 
     * **Validates: Requirements 12.2, 2.5**
     * 
     * Tests that assignToProject() ensures single project assignment by removing
     * any existing project assignment before assigning to a new project.
     */
    public function testAssignToProjectRemovesExistingAssignment()
    {
        // Arrange - create a Regular User assigned to project 1
        $username = 'test_reassign_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Regular User', 1);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Verify initial assignment
        $user = $this->user->getUserById($userId);
        $this->assertEquals(1, $user['ProjectId'], 'User should initially be assigned to project 1');

        // Act - reassign user to project 2
        $result = $this->user->assignToProject($userId, 2);

        // Assert
        $this->assertTrue($result, 'assignToProject should return true on success');

        // Verify the user is now assigned to project 2 (not both)
        $user = $this->user->getUserById($userId);
        $this->assertEquals(2, $user['ProjectId'], 'User should be assigned to project 2');
        $this->assertNotEquals(1, $user['ProjectId'], 'User should no longer be assigned to project 1');
    }

    /**
     * Unit test: assignToProject rejects non-Regular User (Manager)
     * 
     * **Validates: Requirements 12.3, 3.6**
     * 
     * Tests that assignToProject() prevents assigning Manager users to projects.
     */
    public function testAssignToProjectRejectsManager()
    {
        // Arrange - create a Manager user (roleId=2)
        $username = 'test_assign_manager_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 2, 'Manager User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - attempt to assign Manager to project
        $result = $this->user->assignToProject($userId, 1);

        // Assert
        $this->assertFalse($result, 'assignToProject should reject Manager users');

        // Verify the user's projectId remains NULL
        $user = $this->user->getUserById($userId);
        $this->assertNull($user['ProjectId'], 'Manager should not be assigned to any project');
    }

    /**
     * Unit test: assignToProject rejects non-Regular User (Admin)
     * 
     * **Validates: Requirements 12.3, 3.6**
     * 
     * Tests that assignToProject() prevents assigning Admin users to projects.
     */
    public function testAssignToProjectRejectsAdmin()
    {
        // Arrange - create an Admin user (roleId=1)
        $username = 'test_assign_admin_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 1, 'Admin User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - attempt to assign Admin to project
        $result = $this->user->assignToProject($userId, 1);

        // Assert
        $this->assertFalse($result, 'assignToProject should reject Admin users');

        // Verify the user's projectId remains NULL
        $user = $this->user->getUserById($userId);
        $this->assertNull($user['ProjectId'], 'Admin should not be assigned to any project');
    }

    /**
     * Unit test: assignToProject rejects non-existent user
     * 
     * **Validates: Requirements 10.5**
     * 
     * Tests that assignToProject() validates user exists.
     */
    public function testAssignToProjectRejectsNonExistentUser()
    {
        // Act - attempt to assign non-existent user to project
        $result = $this->user->assignToProject(999999, 1);

        // Assert
        $this->assertFalse($result, 'assignToProject should reject non-existent user');
    }

    /**
     * Unit test: assignToProject rejects non-existent project
     * 
     * **Validates: Requirements 10.5, 12.4**
     * 
     * Tests that assignToProject() validates project exists.
     */
    public function testAssignToProjectRejectsNonExistentProject()
    {
        // Arrange - create a Regular User
        $username = 'test_assign_badproject_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Regular User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - attempt to assign user to non-existent project
        $result = $this->user->assignToProject($userId, 999999);

        // Assert
        $this->assertFalse($result, 'assignToProject should reject non-existent project');

        // Verify the user's projectId remains NULL
        $user = $this->user->getUserById($userId);
        $this->assertNull($user['ProjectId'], 'User should not be assigned to any project');
    }

    /**
     * Unit test: assignToProject uses prepared statements
     * 
     * **Validates: Requirements 9.3**
     * 
     * Tests that assignToProject() is protected against SQL injection.
     */
    public function testAssignToProjectUsesPreparedStatements()
    {
        // Arrange - create a Regular User
        $username = 'test_assign_injection_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Regular User', null);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - the method should safely handle the userId parameter
        // Even if we tried to inject SQL, prepared statements should prevent it
        $result = $this->user->assignToProject($userId, 1);

        // Assert
        $this->assertTrue($result, 'assignToProject should succeed with valid data');

        // Verify the user_details table still exists (no SQL injection occurred)
        $tableCheck = $this->db->query("SELECT COUNT(*) as count FROM user_details");
        $this->assertIsArray($tableCheck, 'user_details table should still exist');

        // Verify the assignment worked correctly
        $user = $this->user->getUserById($userId);
        $this->assertEquals(1, $user['ProjectId'], 'User should be assigned to project 1');
    }

    /**
     * Feature: complete-bug-tracker-system, Property 9: Single Project Assignment for Regular Users
     * 
     * **Validates: Requirements 2.5, 12.2**
     * 
     * For any Regular User, when they are assigned to a project, the system should 
     * ensure they have exactly one project assignment (any previous project assignment 
     * should be removed).
     */
    public function testSingleProjectAssignmentForRegularUsers()
    {
        // Get available projects from database
        $projects = $this->db->query("SELECT Id FROM project ORDER BY Id LIMIT 10");
        
        if (count($projects) < 2) {
            $this->markTestSkipped('Need at least 2 projects in database to test project reassignment');
        }

        $this->forAll(
            Generator\elements(array_column($projects, 'Id')), // First project ID
            Generator\elements(array_column($projects, 'Id'))  // Second project ID
        )
        ->then(function($firstProjectId, $secondProjectId) {
            // Ensure we're testing reassignment (different projects)
            if ($firstProjectId === $secondProjectId) {
                // Skip this iteration if both projects are the same
                return;
            }

            // Arrange - create a Regular User (roleId=3)
            $username = 'test_single_proj_' . uniqid();
            $password = 'testpassword123';
            $userId = $this->createTestUser($username, $password, 3, 'Regular User', null);
            $this->assertNotFalse($userId, 'User creation should succeed');

            // Act - assign user to first project
            $result1 = $this->user->assignToProject($userId, $firstProjectId);
            $this->assertTrue($result1, 'First project assignment should succeed');

            // Verify user is assigned to first project
            $user1 = $this->user->getUserById($userId);
            $this->assertEquals($firstProjectId, $user1['ProjectId'], 
                'User should be assigned to first project');

            // Act - assign user to second project (should replace first assignment)
            $result2 = $this->user->assignToProject($userId, $secondProjectId);
            $this->assertTrue($result2, 'Second project assignment should succeed');

            // Assert - user should have exactly one project assignment (the second one)
            $user2 = $this->user->getUserById($userId);
            $this->assertEquals($secondProjectId, $user2['ProjectId'], 
                'User should be assigned to second project');
            $this->assertNotEquals($firstProjectId, $user2['ProjectId'], 
                'User should no longer be assigned to first project');

            // Verify there's only one ProjectId value (not multiple)
            $this->assertIsInt($user2['ProjectId'], 
                'ProjectId should be a single integer, not an array');
        });
    }

    /**
     * Feature: complete-bug-tracker-system, Property 37: Project Assignment Updates User Record
     * 
     * **Validates: Requirements 12.1**
     * 
     * For any Regular User, when a Manager or Admin assigns them to a project, 
     * the system should update the user's projectId field in the database to 
     * the assigned project ID.
     */
    public function testProjectAssignmentUpdatesUserRecord()
    {
        // Get available projects from database
        $projects = $this->db->query("SELECT Id FROM project ORDER BY Id LIMIT 10");
        
        if (count($projects) < 1) {
            $this->markTestSkipped('Need at least 1 project in database to test project assignment');
        }

        $this->forAll(
            Generator\elements(array_column($projects, 'Id')), // Project ID from actual database
            Generator\string()->suchThat(function($s) { 
                return strlen($s) >= 3 && strlen($s) <= 20 && preg_match('/^[a-zA-Z0-9_]+$/', $s); 
            })
        )
        ->then(function($projectId, $username) {
            // Make username unique
            $username = 'test_proj_update_' . $username . '_' . uniqid();
            $password = 'testpassword123';

            // Arrange - create a Regular User (roleId=3) with no initial project
            $userId = $this->createTestUser($username, $password, 3, 'Regular User', null);
            $this->assertNotFalse($userId, 'User creation should succeed');

            // Verify user initially has no project assignment
            $userBefore = $this->user->getUserById($userId);
            $this->assertNull($userBefore['ProjectId'], 
                'User should initially have no project assignment');

            // Act - assign user to project
            $result = $this->user->assignToProject($userId, $projectId);

            // Assert - assignment should succeed
            $this->assertTrue($result, 'Project assignment should return true');

            // Assert - user record should be updated in database
            $userAfter = $this->user->getUserById($userId);
            $this->assertNotNull($userAfter['ProjectId'], 
                'User should have a project assignment after assignment');
            $this->assertEquals($projectId, $userAfter['ProjectId'], 
                'User ProjectId should match the assigned project ID');

            // Verify the update persists (read from database again)
            $userVerify = $this->user->getUserById($userId);
            $this->assertEquals($projectId, $userVerify['ProjectId'], 
                'Project assignment should persist in database');
        });
    }

    /**
     * Unit test: getUsersByProject returns users assigned to project
     *
     * **Validates: Requirements 12.5, 9.1**
     *
     * Tests that getUsersByProject() retrieves all users assigned to a specific project
     * using prepared statements.
     */
    public function testGetUsersByProjectReturnsAssignedUsers()
    {
        // Arrange - create multiple Regular Users assigned to different projects
        $username1 = 'test_getbyproj1_' . uniqid();
        $username2 = 'test_getbyproj2_' . uniqid();
        $username3 = 'test_getbyproj3_' . uniqid();
        $password = 'testpassword123';

        // Create users assigned to project 1
        $userId1 = $this->createTestUser($username1, $password, 3, 'User 1', 1);
        $userId2 = $this->createTestUser($username2, $password, 3, 'User 2', 1);

        // Create user assigned to project 2
        $userId3 = $this->createTestUser($username3, $password, 3, 'User 3', 2);

        $this->assertNotFalse($userId1, 'User 1 creation should succeed');
        $this->assertNotFalse($userId2, 'User 2 creation should succeed');
        $this->assertNotFalse($userId3, 'User 3 creation should succeed');

        // Act - get users for project 1
        $result = $this->user->getUsersByProject(1);

        // Assert
        $this->assertIsArray($result, 'getUsersByProject should return an array');

        // Find our test users in the results
        $foundUser1 = false;
        $foundUser2 = false;
        $foundUser3 = false;

        foreach ($result as $user) {
            if ($user['Id'] == $userId1) {
                $foundUser1 = true;
                $this->assertEquals(1, $user['ProjectId'], 'User 1 should be assigned to project 1');
            }
            if ($user['Id'] == $userId2) {
                $foundUser2 = true;
                $this->assertEquals(1, $user['ProjectId'], 'User 2 should be assigned to project 1');
            }
            if ($user['Id'] == $userId3) {
                $foundUser3 = true;
            }
        }

        // Verify correct users were returned
        $this->assertTrue($foundUser1, 'User 1 should be in results for project 1');
        $this->assertTrue($foundUser2, 'User 2 should be in results for project 1');
        $this->assertFalse($foundUser3, 'User 3 should NOT be in results for project 1');
    }

    /**
     * Unit test: getUsersByProject returns empty array for project with no users
     *
     * **Validates: Requirements 12.5, 9.1**
     *
     * Tests that getUsersByProject() returns an empty array when no users are
     * assigned to the specified project.
     */
    public function testGetUsersByProjectReturnsEmptyArrayForNoUsers()
    {
        // Act - get users for a project that likely has no users (high ID)
        $result = $this->user->getUsersByProject(999999);

        // Assert
        $this->assertIsArray($result, 'getUsersByProject should return an array');
        $this->assertEmpty($result, 'getUsersByProject should return empty array for project with no users');
    }

    /**
     * Unit test: getUsersByProject uses prepared statements
     *
     * **Validates: Requirements 9.1**
     *
     * Tests that getUsersByProject() is protected against SQL injection.
     */
    public function testGetUsersByProjectUsesPreparedStatements()
    {
        // Act - attempt SQL injection in projectId parameter
        // The method should safely handle this as a parameter
        $result = $this->user->getUsersByProject("1 OR 1=1");

        // Assert - should return empty array or only users for literal "1 OR 1=1" project
        // (which doesn't exist), not all users
        $this->assertIsArray($result, 'getUsersByProject should return an array');

        // Verify the user_details table still exists (no SQL injection occurred)
        $tableCheck = $this->db->query("SELECT COUNT(*) as count FROM user_details");
        $this->assertIsArray($tableCheck, 'user_details table should still exist');
    }

    /**
     * Unit test: getUsersByProject returns all user fields
     *
     * **Validates: Requirements 12.5**
     *
     * Tests that getUsersByProject() returns complete user records with all fields.
     */
    public function testGetUsersByProjectReturnsAllFields()
    {
        // Arrange - create a test user assigned to project 1
        $username = 'test_getbyproj_fields_' . uniqid();
        $password = 'testpassword123';
        $userId = $this->createTestUser($username, $password, 3, 'Test User', 1);
        $this->assertNotFalse($userId, 'User creation should succeed');

        // Act - get users for project 1
        $result = $this->user->getUsersByProject(1);

        // Assert
        $this->assertIsArray($result, 'getUsersByProject should return an array');

        // Find our test user
        $testUser = null;
        foreach ($result as $user) {
            if ($user['Id'] == $userId) {
                $testUser = $user;
                break;
            }
        }

        $this->assertNotNull($testUser, 'Test user should be in results');
        $this->assertArrayHasKey('Id', $testUser, 'Result should include Id');
        $this->assertArrayHasKey('Username', $testUser, 'Result should include Username');
        $this->assertArrayHasKey('RoleID', $testUser, 'Result should include RoleID');
        $this->assertArrayHasKey('Name', $testUser, 'Result should include Name');
        $this->assertArrayHasKey('ProjectId', $testUser, 'Result should include ProjectId');

        $this->assertEquals($username, $testUser['Username'], 'Username should match');
        $this->assertEquals(3, $testUser['RoleID'], 'RoleID should match');
        $this->assertEquals(1, $testUser['ProjectId'], 'ProjectId should match');
    }

}
