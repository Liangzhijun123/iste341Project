<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Eris\Generator;

require_once __DIR__ . '/../../tracker/classes/Database.class.php';

/**
 * Property-Based Tests for Database Class
 * 
 * These tests validate that the Database class correctly handles NULL values
 * in database operations, as specified in Property 42 of the design document.
 */
class DatabaseTest extends TestCase
{
    use \Eris\TestTrait;

    private $db;
    private $testTableCreated = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test database credentials
        $_SERVER['DB_SERVER'] = $_SERVER['DB_SERVER'] ?? 'localhost';
        $_SERVER['DB'] = $_SERVER['DB'] ?? 'zl5660';
        $_SERVER['DB_USER'] = $_SERVER['DB_USER'] ?? 'zl5660';
        $_SERVER['DB_PASSWORD'] = $_SERVER['DB_PASSWORD'] ?? 'YOUR_PASSWORD';
        
        $this->db = new \Database();
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        if ($this->testTableCreated) {
            $this->dropTestTable();
        }
        parent::tearDown();
    }

    /**
     * Create a test table for property testing
     */
    private function createTestTable(): void
    {
        try {
            $this->db->execute("DROP TABLE IF EXISTS test_null_handling");
            $this->db->execute("
                CREATE TABLE test_null_handling (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nullable_string VARCHAR(255) NULL,
                    nullable_int INT NULL,
                    nullable_date DATE NULL,
                    required_field VARCHAR(100) NOT NULL
                )
            ");
            $this->testTableCreated = true;
        } catch (\PDOException $e) {
            $this->markTestSkipped('Could not create test table: ' . $e->getMessage());
        }
    }

    /**
     * Drop the test table
     */
    private function dropTestTable(): void
    {
        try {
            $this->db->execute("DROP TABLE IF EXISTS test_null_handling");
        } catch (\PDOException $e) {
            // Ignore errors during cleanup
        }
    }

    /**
     * Feature: complete-bug-tracker-system, Property 42: NULL Value Handling
     * 
     * **Validates: Requirements 16.4**
     * 
     * For any database operation involving nullable columns (projectId, assignedToId, 
     * owner, targetDate, dateClosed, fixDescription), the system should correctly 
     * handle NULL values in both storage and retrieval without errors.
     * 
     * This property test verifies that:
     * 1. NULL values can be inserted into nullable columns
     * 2. NULL values can be retrieved from nullable columns
     * 3. NULL values can be updated in nullable columns
     * 4. The Database class correctly handles NULL in prepared statement parameters
     */
    public function testNullValueHandlingInInsertAndRetrieve()
    {
        $this->forAll(
            Generator\choose(0, 1), // 0 = NULL, 1 = value
            Generator\choose(0, 1),
            Generator\choose(0, 1),
            Generator\string()
        )
        ->then(function ($useString, $useInt, $useDate, $requiredValue) {
            // Generate values or NULL based on random choice
            $stringValue = $useString ? 'test_string_' . uniqid() : null;
            $intValue = $useInt ? rand(1, 1000) : null;
            $dateValue = $useDate ? date('Y-m-d', strtotime('+' . rand(1, 365) . ' days')) : null;
            $requiredValue = 'required_' . substr(md5($requiredValue), 0, 10);

            // Test INSERT with NULL values using execute()
            $insertResult = $this->db->execute(
                "INSERT INTO test_null_handling (nullable_string, nullable_int, nullable_date, required_field) 
                 VALUES (?, ?, ?, ?)",
                [$stringValue, $intValue, $dateValue, $requiredValue]
            );

            $this->assertTrue($insertResult, 'Insert with NULL values should succeed');

            // Get the last inserted ID
            $lastId = $this->db->getConnection()->lastInsertId();

            // Test SELECT with NULL values using query()
            $results = $this->db->query(
                "SELECT * FROM test_null_handling WHERE id = ?",
                [$lastId]
            );

            $this->assertCount(1, $results, 'Should retrieve exactly one record');
            
            $record = $results[0];
            
            // Verify NULL values are correctly retrieved
            if ($stringValue === null) {
                $this->assertNull($record['nullable_string'], 'NULL string should be retrieved as NULL');
            } else {
                $this->assertEquals($stringValue, $record['nullable_string'], 'String value should match');
            }

            if ($intValue === null) {
                $this->assertNull($record['nullable_int'], 'NULL int should be retrieved as NULL');
            } else {
                $this->assertEquals($intValue, $record['nullable_int'], 'Int value should match');
            }

            if ($dateValue === null) {
                $this->assertNull($record['nullable_date'], 'NULL date should be retrieved as NULL');
            } else {
                $this->assertEquals($dateValue, $record['nullable_date'], 'Date value should match');
            }

            $this->assertEquals($requiredValue, $record['required_field'], 'Required field should match');
        });
    }

    /**
     * Feature: complete-bug-tracker-system, Property 42: NULL Value Handling
     * 
     * **Validates: Requirements 16.4**
     * 
     * This test verifies that UPDATE operations correctly handle NULL values
     * in nullable columns using prepared statements.
     */
    public function testNullValueHandlingInUpdate()
    {
        $this->forAll(
            Generator\choose(0, 1), // Initial value: 0 = NULL, 1 = value
            Generator\choose(0, 1)  // Update value: 0 = NULL, 1 = value
        )
        ->then(function ($initialUseValue, $updateUseValue) {
            // Insert initial record
            $initialValue = $initialUseValue ? 'initial_' . uniqid() : null;
            $requiredValue = 'required_' . uniqid();

            $this->db->execute(
                "INSERT INTO test_null_handling (nullable_string, required_field) VALUES (?, ?)",
                [$initialValue, $requiredValue]
            );

            $lastId = $this->db->getConnection()->lastInsertId();

            // Update with NULL or value
            $updateValue = $updateUseValue ? 'updated_' . uniqid() : null;
            
            $updateResult = $this->db->execute(
                "UPDATE test_null_handling SET nullable_string = ? WHERE id = ?",
                [$updateValue, $lastId]
            );

            $this->assertTrue($updateResult, 'Update with NULL value should succeed');

            // Verify the update
            $results = $this->db->query(
                "SELECT nullable_string FROM test_null_handling WHERE id = ?",
                [$lastId]
            );

            $this->assertCount(1, $results, 'Should retrieve the updated record');
            
            if ($updateValue === null) {
                $this->assertNull($results[0]['nullable_string'], 'NULL value should be stored and retrieved');
            } else {
                $this->assertEquals($updateValue, $results[0]['nullable_string'], 'Updated value should match');
            }
        });
    }

    /**
     * Feature: complete-bug-tracker-system, Property 42: NULL Value Handling
     * 
     * **Validates: Requirements 16.4**
     * 
     * This test verifies that queries with NULL in WHERE clauses work correctly.
     * This is important for filtering bugs by assignedToId (NULL = unassigned).
     */
    public function testQueryWithNullInWhereClause()
    {
        // Insert records with NULL and non-NULL values
        $this->db->execute(
            "INSERT INTO test_null_handling (nullable_string, required_field) VALUES (?, ?)",
            [null, 'record_with_null']
        );
        
        $this->db->execute(
            "INSERT INTO test_null_handling (nullable_string, required_field) VALUES (?, ?)",
            ['not_null', 'record_with_value']
        );

        // Query for NULL values (must use IS NULL, not = NULL)
        $nullResults = $this->db->query(
            "SELECT * FROM test_null_handling WHERE nullable_string IS NULL"
        );

        $this->assertGreaterThanOrEqual(1, count($nullResults), 'Should find records with NULL values');
        
        foreach ($nullResults as $record) {
            $this->assertNull($record['nullable_string'], 'All results should have NULL nullable_string');
        }

        // Query for non-NULL values
        $nonNullResults = $this->db->query(
            "SELECT * FROM test_null_handling WHERE nullable_string IS NOT NULL"
        );

        $this->assertGreaterThanOrEqual(1, count($nonNullResults), 'Should find records with non-NULL values');
        
        foreach ($nonNullResults as $record) {
            $this->assertNotNull($record['nullable_string'], 'All results should have non-NULL nullable_string');
        }
    }

    /**
     * Unit test: Verify that the Database class methods exist and are callable
     */
    public function testDatabaseClassHasRequiredMethods()
    {
        $this->assertTrue(method_exists($this->db, 'query'), 'Database should have query() method');
        $this->assertTrue(method_exists($this->db, 'execute'), 'Database should have execute() method');
        $this->assertTrue(method_exists($this->db, 'getConnection'), 'Database should have getConnection() method');
        
        $pdo = $this->db->getConnection();
        $this->assertInstanceOf(\PDO::class, $pdo, 'getConnection() should return PDO instance');
    }

    /**
     * Unit test: Verify that prepared statements are used (not direct SQL concatenation)
     */
    public function testPreparedStatementsPreventSqlInjection()
    {
        // Attempt SQL injection in a parameter
        $maliciousInput = "'; DROP TABLE test_null_handling; --";
        $requiredValue = 'test_injection';

        // This should safely handle the malicious input as data, not SQL
        $result = $this->db->execute(
            "INSERT INTO test_null_handling (nullable_string, required_field) VALUES (?, ?)",
            [$maliciousInput, $requiredValue]
        );

        $this->assertTrue($result, 'Prepared statement should safely handle malicious input');

        // Verify the table still exists and the data was inserted as a string
        $results = $this->db->query(
            "SELECT * FROM test_null_handling WHERE required_field = ?",
            [$requiredValue]
        );

        $this->assertCount(1, $results, 'Record should be inserted safely');
        $this->assertEquals($maliciousInput, $results[0]['nullable_string'], 'Malicious input should be stored as data');
    }
}
