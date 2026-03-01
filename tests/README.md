# Bug Tracker System - Testing Guide

## Setup

### Prerequisites
- PHP 7.4 or higher
- Composer
- MySQL database

### Installation

1. Install dependencies:
```bash
composer install
```

2. Configure database credentials:
   - Edit `phpunit.xml` and update the database credentials in the `<php>` section
   - Or set environment variables: `DB_SERVER`, `DB`, `DB_USER`, `DB_PASSWORD`

3. Ensure your database is running and accessible

## Running Tests

### Run all tests:
```bash
vendor/bin/phpunit
```

### Run only unit tests:
```bash
vendor/bin/phpunit --testsuite "Unit Tests"
```

### Run with coverage report:
```bash
vendor/bin/phpunit --coverage-html coverage
```

### Run specific test file:
```bash
vendor/bin/phpunit tests/Unit/DatabaseTest.php
```

## Property-Based Testing

The test suite includes property-based tests using the Eris library. These tests:
- Run a minimum of 100 iterations per property
- Generate random test data to verify universal properties
- Are tagged with comments referencing design document properties

### Property Tests in DatabaseTest.php

- **Property 42: NULL Value Handling** - Validates that the Database class correctly handles NULL values in all operations (insert, update, select)

## Test Structure

```
tests/
├── Unit/              # Unit tests for individual classes
│   └── DatabaseTest.php
├── Integration/       # Integration tests for controllers and workflows
└── README.md         # This file
```

## Writing New Tests

### Property-Based Test Template

```php
/**
 * Feature: complete-bug-tracker-system, Property X: [Property Name]
 * 
 * **Validates: Requirements X.Y**
 * 
 * [Description of what this property validates]
 */
public function testPropertyName()
{
    $this->forAll(
        Generator\string(),
        Generator\int()
    )
    ->then(function($str, $int) {
        // Test logic here
        $this->assertTrue($condition);
    });
}
```

### Unit Test Template

```php
/**
 * Unit test: [Description]
 */
public function testFeatureName()
{
    // Arrange
    $input = 'test data';
    
    // Act
    $result = $this->classUnderTest->method($input);
    
    // Assert
    $this->assertEquals($expected, $result);
}
```

## Troubleshooting

### Database Connection Errors
- Verify database credentials in `phpunit.xml`
- Ensure MySQL server is running
- Check that the database exists and user has proper permissions

### Composer Not Found
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### PHP Not Found
- Ensure PHP is installed and in your PATH
- Check PHP version: `php -v` (must be 7.4+)

## Notes

- Property tests may take longer to run due to multiple iterations
- Test database tables are created and dropped automatically
- All tests use prepared statements to prevent SQL injection
- NULL value handling is critical for the bug tracker's nullable columns
