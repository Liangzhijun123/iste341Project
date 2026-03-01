# Implementation Plan: Complete Bug Tracker System

## Overview

This plan implements a PHP-based bug tracking application with role-based access control. The system has existing class files, controllers, and views that need full implementation. The implementation follows an MVC architecture with session-based authentication, prepared statements for SQL injection prevention, and template-based rendering.

## Tasks

- [x] 1. Set up database configuration and connection
  - Create config/database.php with database credentials
  - Implement Database class constructor with PDO connection
  - Implement Database::query() method for SELECT queries with prepared statements
  - Implement Database::execute() method for INSERT/UPDATE/DELETE with prepared statements
  - Implement Database::getConnection() method to return PDO instance
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 16.1, 16.2_

- [x] 1.1 Write property test for Database class
  - **Property 42: NULL Value Handling**
  - **Validates: Requirements 16.4**

- [ ] 2. Implement User class for user management
  - [x] 2.1 Implement User::login() method with password verification
    - Query user by username using prepared statement
    - Verify password using password_verify()
    - Return user data array on success, false on failure
    - _Requirements: 1.1, 1.2, 9.1_
  
  - [x] 2.2 Write property tests for User authentication
    - **Property 1: Valid Credentials Create Session**
    - **Property 2: Invalid Credentials Reject Login**
    - **Property 3: Password Storage Uses Hashing**
    - **Validates: Requirements 1.1, 1.2, 1.3**
  
  - [x] 2.3 Implement User::getUserById() method
    - Query user by ID using prepared statement
    - Return user data or false if not found
    - _Requirements: 9.1_
  
  - [x] 2.4 Implement User::getAllUsers() method
    - Query all users with JOIN to role and project tables
    - Return array of user records with role and project names
    - _Requirements: 4.1, 9.1_
  
  - [x] 2.5 Implement User::createUser() method with password hashing
    - Validate username uniqueness, role exists, project exists (if provided)
    - Hash password using password_hash() with PASSWORD_DEFAULT
    - Insert user with prepared statement
    - _Requirements: 1.3, 4.2, 10.5, 9.2_
  
  - [x] 2.6 Write property test for user creation
    - **Property 3: Password Storage Uses Hashing**
    - **Validates: Requirements 1.3**
  
  - [x] 2.7 Implement User::updateUser() method
    - Validate role exists, project exists (if provided)
    - Hash password if included in update
    - Update user with prepared statement
    - _Requirements: 9.3, 10.5_
  
  - [x] 2.8 Implement User::deleteUser() method with cascade
    - Set assignedToId to NULL in bugs table for assigned bugs
    - Set owner to NULL in bugs table for owned bugs
    - Delete user record
    - Use prepared statements for all queries
    - _Requirements: 4.3, 4.4, 4.5, 9.4_
  
  - [x] 2.9 Write property test for user deletion cascade
    - **Property 15: User Deletion Cascades to Bugs**
    - **Validates: Requirements 4.3, 4.4, 4.5**
  
  - [x] 2.10 Implement User::assignToProject() method
    - Validate user is Regular User (roleId=3) and project exists
    - Remove any existing project assignment
    - Update user's projectId
    - _Requirements: 12.1, 12.2, 10.5_
  
  - [x] 2.11 Write property test for project assignment
    - **Property 9: Single Project Assignment for Regular Users**
    - **Property 37: Project Assignment Updates User Record**
    - **Validates: Requirements 2.5, 12.1, 12.2**
  
  - [-] 2.12 Implement User::getUsersByProject() method
    - Query users by projectId using prepared statement
    - Return array of user records
    - _Requirements: 12.5, 9.1_

- [ ] 3. Implement Auth class for authentication and authorization
  - [~] 3.1 Implement Auth::checkLogin() method
    - Check if $_SESSION['userId'] is set
    - Return boolean
    - _Requirements: 1.4_
  
  - [~] 3.2 Implement Auth::requireLogin() method
    - Call checkLogin(), redirect to login if false
    - Preserve original URL for post-login redirect
    - _Requirements: 1.4_
  
  - [~] 3.3 Write property test for authentication requirement
    - **Property 4: Unauthenticated Access Redirects to Login**
    - **Validates: Requirements 1.4**
  
  - [~] 3.4 Implement Auth::requireRole() method
    - Check if $_SESSION['roleId'] is in allowed roles array
    - Redirect to dashboard with error if unauthorized
    - _Requirements: 4.7_
  
  - [~] 3.5 Implement Auth::requireAdmin() and Auth::requireManager() methods
    - Shorthand methods calling requireRole() with appropriate role IDs
    - _Requirements: 4.7_
  
  - [~] 3.6 Write property test for role-based authorization
    - **Property 16: Non-Admin Authorization Denial**
    - **Validates: Requirements 4.7**
  
  - [~] 3.7 Implement Auth::hasRole() method
    - Check if $_SESSION['roleId'] matches specified role
    - Return boolean for conditional UI rendering
    - _Requirements: 11.5_
  
  - [~] 3.8 Implement Auth::canAccessBug() method
    - Query bug to get projectId
    - Regular Users: check if bug's projectId matches $_SESSION['projectId']
    - Managers/Admins: return true
    - _Requirements: 2.4_
  
  - [~] 3.9 Write property test for bug access control
    - **Property 6: Regular User Bug Access Filtered by Project**
    - **Validates: Requirements 2.1, 2.4**
  
  - [~] 3.10 Implement Auth::canUpdateBug() method
    - Query bug to get assignedToId
    - Regular Users: check if bug's assignedToId matches $_SESSION['userId']
    - Managers/Admins: return true
    - _Requirements: 2.3, 6.1_
  
  - [~] 3.11 Write property test for bug update authorization
    - **Property 8: Regular User Update Authorization**
    - **Property 11: Manager Update Authorization**
    - **Validates: Requirements 2.3, 3.5, 6.1, 6.2**

- [ ] 4. Implement Project class for project management
  - [~] 4.1 Implement Project::getAllProjects() method
    - Query all projects using prepared statement
    - Return array of project records
    - _Requirements: 3.1, 9.1_
  
  - [~] 4.2 Implement Project::getProjectById() method
    - Query project by ID using prepared statement
    - Return project data or false if not found
    - _Requirements: 9.1_
  
  - [~] 4.3 Implement Project::createProject() method with validation
    - Validate name is required, 3-100 chars, unique
    - Insert project with prepared statement
    - _Requirements: 3.2, 10.1, 10.2, 9.2_
  
  - [~] 4.4 Write property test for project creation validation
    - **Property 13: Project Creation Validation**
    - **Validates: Requirements 3.2, 12.4**
  
  - [~] 4.5 Implement Project::updateProject() method
    - Validate name is required, project exists
    - Update project with prepared statement
    - _Requirements: 12.4, 9.3_
  
  - [~] 4.6 Implement Project::deleteProject() method
    - Validate no bugs assigned to project
    - Remove user assignments to project
    - Delete project record
    - _Requirements: 9.4_
  
  - [~] 4.7 Implement Project::getProjectBugCount() method
    - Query count of bugs for project using prepared statement
    - Return integer count
    - _Requirements: 9.1_

- [ ] 5. Implement Bug class for bug tracking
  - [~] 5.1 Implement Bug::getAllBugs() method
    - Query all bugs with JOINs to project, status, priority, user tables
    - Return array of bug records with related data
    - _Requirements: 3.3, 9.1_
  
  - [~] 5.2 Write property test for manager bug access
    - **Property 10: Manager Views All Bugs**
    - **Validates: Requirements 3.3**
  
  - [~] 5.3 Implement Bug::getBugsByProject() method
    - Query bugs filtered by projectId with JOINs
    - Return array of bug records
    - _Requirements: 2.1, 9.1_
  
  - [~] 5.4 Implement Bug::getBugById() method
    - Query single bug with JOINs to all related tables
    - Return bug data with project name, user names, status/priority labels
    - _Requirements: 14.2, 14.3, 14.4, 14.5, 9.1_
  
  - [~] 5.5 Write property tests for bug detail display
    - **Property 38: Bug Detail Displays Project Name**
    - **Property 39: Bug Detail Displays Assigned User Name**
    - **Property 40: Bug Detail Displays Lookup Labels**
    - **Property 41: Closed Bug Detail Displays Closure Fields**
    - **Validates: Requirements 14.2, 14.3, 14.4, 14.5**
  
  - [~] 5.6 Implement Bug::getFilteredBugs() method
    - Build query based on role, project, and filter criteria
    - Filters: 'all', 'open', 'overdue', 'unassigned'
    - Regular Users: filter by their assigned project
    - Managers/Admins: optional project filter or all projects
    - _Requirements: 7.1, 7.2, 7.3, 8.1, 8.2, 8.3, 8.4, 8.5, 9.1_
  
  - [~] 5.7 Write property tests for bug filtering
    - **Property 27: Regular User All Bugs Filter**
    - **Property 28: Regular User Open Bugs Filter**
    - **Property 29: Regular User Overdue Bugs Filter**
    - **Property 30: Manager All Bugs Filter**
    - **Property 31: Manager Open Bugs Filter**
    - **Property 32: Manager Overdue Bugs Filter**
    - **Property 33: Manager Unassigned Bugs Filter**
    - **Property 14: Manager Bug Filtering**
    - **Validates: Requirements 7.1, 7.2, 7.3, 8.1, 8.2, 8.3, 8.4, 8.5**
  
  - [~] 5.8 Implement Bug::validateBugData() method
    - Validate required fields: description, summary, owner, dateRaised, projectId
    - Validate foreign keys: owner exists, project exists, assignedToId exists (if provided)
    - Validate dates: dateRaised is valid and not future, targetDate is future (if provided)
    - Validate closed bug: dateClosed and fixDescription required if statusId='closed'
    - Return array of error messages (empty if valid)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.8, 6.4, 6.6, 10.1, 10.2, 10.4, 10.5_
  
  - [~] 5.9 Write property tests for bug validation
    - **Property 17: Bug Creation Required Fields**
    - **Property 18: Bug Creation Foreign Key Validation**
    - **Property 19: Bug Creation Date Validation**
    - **Property 23: Target Date Future Validation**
    - **Property 24: Bug Update Validation Consistency**
    - **Property 25: Closed Bug Required Fields**
    - **Property 26: Bug Update Foreign Key Validation**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.8, 6.3, 6.4, 6.6**
  
  - [~] 5.10 Implement Bug::createBug() method
    - Call validateBugData() and return false if errors
    - Regular Users: override assignedToId=NULL, statusId='unassigned', priorityId='medium', projectId=$_SESSION['projectId']
    - Managers/Admins: allow all fields from request
    - Set dateClosed and fixDescription to NULL
    - Insert bug with prepared statement
    - Return new bug ID
    - _Requirements: 2.2, 5.5, 5.6, 5.7, 9.2_
  
  - [~] 5.11 Write property tests for bug creation
    - **Property 7: Regular User Bug Creation Sets Project**
    - **Property 20: Regular User Bug Creation Auto-Fields**
    - **Property 21: Manager Bug Creation Field Permissions**
    - **Property 22: Bug Creation Initial Null Fields**
    - **Validates: Requirements 2.2, 5.5, 5.6, 5.7**
  
  - [~] 5.12 Implement Bug::updateBug() method
    - Call validateBugData() with isUpdate=true
    - Update bug with prepared statement
    - _Requirements: 6.3, 9.3_
  
  - [~] 5.13 Implement Bug::deleteBug() method
    - Delete bug with prepared statement
    - _Requirements: 9.4_

- [ ] 6. Implement Template class for reusable rendering
  - [~] 6.1 Implement Template::renderPage() method
    - Generate complete HTML page structure
    - Call renderHeader(), renderNavigation(), renderFooter()
    - Include content parameter in main section
    - _Requirements: 11.1, 11.2, 11.3, 11.4_
  
  - [~] 6.2 Implement Template::renderHeader() method
    - Return HTML for page header with title
    - Include DOCTYPE, meta tags, CSS link
    - _Requirements: 11.2_
  
  - [~] 6.3 Implement Template::renderNavigation() method
    - Generate navigation menu based on roleId parameter
    - Regular User: Dashboard, Bugs, Logout
    - Manager: Dashboard, Bugs, Projects, Logout
    - Admin: Dashboard, Bugs, Projects, Users, Logout
    - _Requirements: 11.1, 11.5_
  
  - [~] 6.4 Write property test for dynamic navigation
    - **Property 36: Dynamic Navigation Based on Role**
    - **Validates: Requirements 11.5**
  
  - [~] 6.5 Implement Template::renderFooter() method
    - Return HTML for page footer with copyright
    - _Requirements: 11.3_
  
  - [~] 6.6 Implement Template::renderBugTable() method
    - Generate HTML table from bugs array
    - Include columns for summary, status, priority, project, assigned user
    - _Requirements: 11.4_
  
  - [~] 6.7 Implement Template::renderBugForm() method
    - Generate HTML form for creating/editing bugs
    - Pre-fill fields if bug data provided
    - Include dropdowns for projects, users, statuses, priorities
    - Include CSRF token hidden field
    - _Requirements: 11.4_
  
  - [~] 6.8 Implement Template::renderProjectForm() method
    - Generate HTML form for creating/editing projects
    - Pre-fill fields if project data provided
    - Include CSRF token hidden field
    - _Requirements: 11.4_
  
  - [~] 6.9 Implement Template::renderUserForm() method
    - Generate HTML form for creating/editing users
    - Pre-fill fields if user data provided
    - Include dropdowns for roles and projects
    - Include CSRF token hidden field
    - _Requirements: 11.4_
  
  - [~] 6.10 Implement Template::renderError() and Template::renderSuccess() methods
    - Return HTML for displaying error/success messages
    - Read from session flash variables and clear them
    - _Requirements: 10.6_
  
  - [~] 6.11 Write property test for validation error display
    - **Property 35: Validation Error Display**
    - **Validates: Requirements 10.6**

- [ ] 7. Implement login controller and view
  - [~] 7.1 Create controllers/login.php
    - Start session
    - If GET: redirect to login view
    - If POST: sanitize inputs, call User::login()
    - On success: store userId, roleId, projectId, username in session, redirect to dashboard
    - On failure: set error message, redirect to login view
    - _Requirements: 1.1, 1.2, 1.6, 10.1, 10.3_
  
  - [~] 7.2 Write property test for login session creation
    - **Property 1: Valid Credentials Create Session**
    - **Property 2: Invalid Credentials Reject Login**
    - **Validates: Requirements 1.1, 1.2, 1.6**
  
  - [~] 7.3 Create views/login.php
    - Display login form with username and password fields
    - Display error messages using Template::renderError()
    - Use Template::renderPage() for page structure
    - _Requirements: 11.4_

- [ ] 8. Implement logout controller
  - [~] 8.1 Create controllers/logout.php
    - Start session
    - Call session_unset() and session_destroy()
    - Redirect to login page
    - _Requirements: 1.5_
  
  - [~] 8.2 Write property test for logout session destruction
    - **Property 5: Logout Destroys Session**
    - **Validates: Requirements 1.5**

- [ ] 9. Implement bugs controller and view
  - [~] 9.1 Create controllers/bugs.php for list view
    - Call Auth::requireLogin()
    - Get filter and filterProject parameters
    - Call Bug::getFilteredBugs() with session role and project
    - Render bugs view with bug list
    - _Requirements: 1.4, 2.1, 3.3, 7.1, 7.2, 7.3, 8.1, 8.2, 8.3, 8.4_
  
  - [~] 9.2 Add bug detail view to controllers/bugs.php
    - Get bug ID from query string
    - Call Bug::getBugById()
    - Call Auth::canAccessBug()
    - If authorized: render bug detail view
    - If unauthorized: redirect with error
    - _Requirements: 2.4, 14.1, 14.2, 14.3, 14.4, 14.5_
  
  - [~] 9.3 Add bug creation to controllers/bugs.php
    - Call Auth::requireLogin()
    - Validate CSRF token
    - Sanitize all input fields using htmlspecialchars()
    - Build bug data array
    - If Regular User: override assignedToId, statusId, priorityId, projectId
    - Call Bug::createBug()
    - Redirect with success/error message
    - _Requirements: 2.2, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_
  
  - [~] 9.4 Write property test for input sanitization
    - **Property 34: XSS Prevention Through Sanitization**
    - **Validates: Requirements 10.3**
  
  - [~] 9.5 Add bug update to controllers/bugs.php
    - Call Auth::canUpdateBug()
    - Validate CSRF token
    - Sanitize all input fields
    - Build update data array
    - Call Bug::updateBug()
    - Redirect with success/error message
    - _Requirements: 2.3, 3.5, 6.1, 6.2, 6.3, 6.4, 6.6, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_
  
  - [~] 9.6 Add bug deletion to controllers/bugs.php
    - Call Auth::requireAdmin()
    - Validate CSRF token
    - Call Bug::deleteBug()
    - Redirect with success/error message
    - _Requirements: 4.7_
  
  - [~] 9.7 Create views/bugs.php
    - Display bug list using Template::renderBugTable()
    - Display filter controls (all, open, overdue, by project for Managers/Admins)
    - Display create bug button (links to bug form)
    - Use Template::renderPage() for page structure
    - _Requirements: 11.4_
  
  - [~] 9.8 Add bug detail view to views/bugs.php
    - Display all bug fields with labels
    - Display project name, assigned user name, status/priority labels
    - Display dateClosed and fixDescription if bug is closed
    - Display edit button if user can update bug
    - Use Template::renderPage() for page structure
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 11.4_
  
  - [~] 9.9 Add bug form to views/bugs.php
    - Use Template::renderBugForm() to generate form
    - Pre-fill fields if editing existing bug
    - Display validation errors using Template::renderError()
    - _Requirements: 10.6, 11.4_

- [ ] 10. Implement projects controller and view
  - [~] 10.1 Create controllers/projects.php for list view
    - Call Auth::requireManager()
    - Call Project::getAllProjects()
    - For each project: call Project::getProjectBugCount()
    - Render projects view with project list
    - _Requirements: 3.1, 4.7_
  
  - [~] 10.2 Add project creation to controllers/projects.php
    - Call Auth::requireManager()
    - Validate CSRF token
    - Sanitize name and description inputs
    - Validate name required, 3-100 chars
    - Call Project::createProject()
    - Redirect with success/error message
    - _Requirements: 3.2, 10.1, 10.2, 10.3, 10.6_
  
  - [~] 10.3 Add project update to controllers/projects.php
    - Call Auth::requireManager()
    - Validate CSRF token
    - Sanitize inputs
    - Validate name required, 3-100 chars
    - Call Project::updateProject()
    - Redirect with success/error message
    - _Requirements: 12.4, 10.1, 10.2, 10.3, 10.6_
  
  - [~] 10.4 Add project deletion to controllers/projects.php
    - Call Auth::requireAdmin()
    - Validate CSRF token
    - Call Project::getProjectBugCount()
    - If count > 0: redirect with error
    - Call Project::deleteProject()
    - Redirect with success/error message
    - _Requirements: 4.7_
  
  - [~] 10.5 Create views/projects.php
    - Display project list with name, description, bug count
    - Display create project button
    - Display edit/delete buttons for each project
    - Use Template::renderProjectForm() for forms
    - Use Template::renderPage() for page structure
    - _Requirements: 11.4_

- [ ] 11. Implement users controller and view
  - [~] 11.1 Create controllers/users.php for list view
    - Call Auth::requireAdmin()
    - Call User::getAllUsers()
    - Call Project::getAllProjects()
    - Render users view with user list and projects
    - _Requirements: 4.1, 4.7_
  
  - [~] 11.2 Add user creation to controllers/users.php
    - Call Auth::requireAdmin()
    - Validate CSRF token
    - Sanitize all input fields
    - Validate username (3-50 chars, alphanumeric + underscore, unique)
    - Validate password (min 8 chars)
    - Validate roleId exists, name (2-100 chars)
    - Validate projectId exists if provided, only if roleId=3
    - Call User::createUser()
    - Redirect with success/error message
    - _Requirements: 4.2, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_
  
  - [~] 11.3 Add user update to controllers/users.php
    - Call Auth::requireAdmin()
    - Validate CSRF token
    - Sanitize inputs
    - Build update data array (only changed fields)
    - Validate fields as in create
    - Call User::updateUser()
    - Redirect with success/error message
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_
  
  - [~] 11.4 Add user deletion to controllers/users.php
    - Call Auth::requireAdmin()
    - Validate CSRF token
    - If user ID equals $_SESSION['userId']: redirect with error
    - Call User::deleteUser()
    - Redirect with success/error message
    - _Requirements: 4.3, 4.4, 4.5, 4.6_
  
  - [~] 11.5 Add user project assignment to controllers/users.php
    - Call Auth::requireManager()
    - Validate CSRF token
    - Get user ID and project ID from request
    - Validate user has roleId=3, project exists
    - Call User::assignToProject()
    - Redirect with success/error message
    - _Requirements: 12.1, 12.2, 12.3_
  
  - [~] 11.6 Write property test for manager project assignment prevention
    - **Property 12: Manager Project Assignment Prevention**
    - **Validates: Requirements 3.6, 12.3**
  
  - [~] 11.7 Create views/admin_users.php
    - Display user list with username, name, role, assigned project
    - Display create user button
    - Display edit/delete buttons for each user
    - Display assign to project dropdown for Regular Users
    - Use Template::renderUserForm() for forms
    - Use Template::renderPage() for page structure
    - _Requirements: 11.4, 12.5_

- [ ] 12. Implement dashboard view
  - [~] 12.1 Create views/dashboard.php
    - Call Auth::requireLogin()
    - Display welcome message with username
    - Display role-specific quick links
    - Display recent bugs summary
    - Use Template::renderPage() for page structure
    - _Requirements: 1.4, 11.4_

- [ ] 13. Implement front controller routing
  - [~] 13.1 Create index.php router
    - Start session
    - Parse request URI to determine controller
    - Route to appropriate controller file
    - Default route to dashboard for authenticated users, login for unauthenticated
    - _Requirements: 15.4_

- [ ] 14. Add CSRF protection
  - [~] 14.1 Generate CSRF token in session
    - In index.php or auth initialization, generate token if not exists
    - Store in $_SESSION['csrf_token']
    - _Requirements: Security best practice_
  
  - [~] 14.2 Add CSRF token validation to all controllers
    - Check token in all POST requests
    - Die with error if token missing or invalid
    - _Requirements: Security best practice_
  
  - [~] 14.3 Include CSRF token in all forms
    - Update all Template::render*Form() methods to include hidden token field
    - _Requirements: Security best practice_

- [ ] 15. Create database population script
  - [~] 15.1 Create scripts/populate_database.php
    - Create at least one Admin user (username: admin, password: admin123)
    - Create at least one Manager user (username: manager, password: manager123)
    - Create at least one Regular User (username: user, password: user123)
    - Create at least two projects (Project A, Project B)
    - Assign Regular User to Project A
    - Create at least two bugs with different statuses and priorities
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_

- [ ] 16. Add error handling and logging
  - [~] 16.1 Configure PDO error mode in Database class
    - Set PDO::ERRMODE_EXCEPTION
    - _Requirements: Security best practice_
  
  - [~] 16.2 Add try-catch blocks in model methods
    - Catch PDOException in all database operations
    - Log errors to file
    - Return false or appropriate error response
    - _Requirements: Security best practice_
  
  - [~] 16.3 Create error logging utility
    - Create utility function for logging errors to file
    - Log format: [timestamp] [level] [userId] [message]
    - _Requirements: Security best practice_

- [ ] 17. Create basic stylesheet
  - [~] 17.1 Create assets/style.css
    - Basic layout styles for navigation, forms, tables
    - Responsive design for mobile devices
    - Error/success message styling
    - _Requirements: User experience_

- [~] 18. Final checkpoint - Integration testing
  - Ensure all tests pass
  - Test complete user workflows (login → create bug → update bug → logout)
  - Test role-based access control across all pages
  - Test validation errors display correctly
  - Test CSRF protection on all forms
  - Ask the user if questions arise

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- All database operations use prepared statements for SQL injection prevention
- All user input is sanitized using htmlspecialchars() for XSS prevention
- CSRF tokens protect all state-changing operations
- Template class provides DRY rendering without include/require statements
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples and edge cases
