#!/bin/bash

# Bug Tracker System - Test Runner Script
# This script helps set up and run the test suite

set -e

echo "==================================="
echo "Bug Tracker System - Test Runner"
echo "==================================="
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP is not installed or not in PATH"
    echo "Please install PHP 7.4 or higher"
    exit 1
fi

echo "✓ PHP found: $(php -v | head -n 1)"

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Error: Composer is not installed or not in PATH"
    echo "Please install Composer: https://getcomposer.org/download/"
    exit 1
fi

echo "✓ Composer found: $(composer --version)"

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo ""
    echo "Installing dependencies..."
    composer install
    echo "✓ Dependencies installed"
else
    echo "✓ Dependencies already installed"
fi

# Check database configuration
echo ""
echo "Database Configuration:"
echo "  Server: ${DB_SERVER:-localhost}"
echo "  Database: ${DB:-zl5660}"
echo "  User: ${DB_USER:-zl5660}"
echo ""
echo "⚠️  Make sure to set DB_PASSWORD environment variable or update phpunit.xml"
echo ""

# Run tests
echo "Running tests..."
echo "==================================="
echo ""

if [ "$1" == "--coverage" ]; then
    echo "Running tests with coverage report..."
    vendor/bin/phpunit --coverage-html coverage
    echo ""
    echo "✓ Coverage report generated in coverage/index.html"
elif [ "$1" == "--unit" ]; then
    echo "Running unit tests only..."
    vendor/bin/phpunit --testsuite "Unit Tests"
elif [ -n "$1" ]; then
    echo "Running specific test: $1"
    vendor/bin/phpunit "$1"
else
    vendor/bin/phpunit
fi

echo ""
echo "==================================="
echo "✓ Tests completed"
echo "==================================="
