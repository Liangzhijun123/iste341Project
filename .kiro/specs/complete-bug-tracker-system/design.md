# Design Document: Complete Bug Tracker System

## Overview

The Complete Bug Tracker System is a PHP-based web application that enables teams to track software bugs across multiple projects with role-based access control. The system follows an MVC-like architecture with existing class files (Auth, Bug, Database, Project, Template, User) that need full implementation to meet functional and security requirements.

The system supports three user roles:
- **Regular User**: Can view and update bugs only for their assigned project
- **Manager**: Can create/update projects and view bugs across all projects
- **Admin**: Has full system access including user management

Key technical requirements include:
- Session-based authentication with role-based authorization
- All database queries using prepared statements (SQL injection prevention)
- Server-side validation and sanitization (XSS prevention)
- Template-based rendering without include/require statements
- DRY code organization using existing class structure

## Architecture

The system follows a Model-View-Controller (MVC) pattern with the following layers:

### Request Flow

```
User Request → index.php (Router) → Controller → Model Classes → Database
                                         ↓
                                      View ← Template
```

### Directory Structure

```
tracker/
├── index.php              # Front controller / router
├── classes/               # Model layer
│   ├── Auth.class.php     # Authentication & authorization
│   ├── Bug.class.php      # Bug operations
│   ├── Database.class.php # Database abstraction
│   ├── Project.class.php  # Project operations
│   ├── User.class.php     # User operations
│   └── Template.class.php # Template rendering
├── controllers/           # Controller layer
│   ├── login.php          # Login handler
│   ├── logout.php         # Logout handler
│   ├── bugs.php           # Bug CRUD operations
│   ├── projects.php       # Project management
│   └── users.php          # User management
├── views/                 # View layer
│   ├── login.php          # Login page
│   ├── dashboard.php      # Dashboard
│   ├── bugs.php           # Bug list/detail
│   ├── admin_users.php    # User management
│   └── projects.php       # Project management
├── assets/                # Static resources
│   └── style.css          # Stylesheet
└── config/                # Configuration
    └── database.php       # Database credentials
```

### Session Management

Sessions store authenticated user state:
- `$_SESSION['userId']`: User ID
- `$_SESSION['roleId']`: Role ID (1=Admin, 2=Manager, 3=Regular User)
- `$_SESSION['projectId']`: Assigned project ID (for Regular Users only)
- `$_SESSION['username']`: Username for display

All pages except login.php require an active session. The Auth class provides middleware methods to enforce authentication and authorization.

## Components and Interfaces

### Database Class

The Database class provides a secure abstraction layer using PDO with prepared statements.

**Constructor:**
```php
public function __construct()
```
- Establishes PDO connection with error mode and fetch mode configuration
- Reads credentials from environment variables or defaults
- Dies with error message if connection fails

**Methods:**
```php
public function query($sql, $params = []): array
```
- Executes SELECT queries with prepared statements
- Returns array of associative arrays (all rows)
- Parameters: SQL string with placeholders, array of values

```php
public function execute($sql, $params = []): bool
```
- Executes INSERT, UPDATE, DELETE queries with prepared statements
- Returns boolean success status
- Parameters: SQL string with placeholders, array of values

```php
public function getConnection(): PDO
```
- Returns PDO instance for advanced operations (e.g., lastInsertId)

**Security:**
- All queries use parameterized prepared statements
- Never concatenates user input into SQL strings
- PDO::ERRMODE_EXCEPTION enabled for error handling

### Auth Class

The Auth class handles authentication and authorization middleware.

**Methods:**
```php
public function checkLogin(): bool
```
- Checks if user has valid session
- Returns true if `$_SESSION['userId']` is set
- Used by all protected pages

```php
public function requireLogin(): void
```
- Redirects to login page if user not authenticated
- Called at top of all protected pages
- Preserves original URL for post-login redirect

```php
public function requireRole($allowedRoles): void
```
- Checks if user's role is in allowed roles array
- Redirects to dashboard with error if unauthorized
- Parameters: array of role IDs (e.g., [1, 2] for Admin and Manager)

```php
public function requireAdmin(): void
```
- Shorthand for requireRole([1])
- Used by user management pages

```php
public function requireManager(): void
```
- Shorthand for requireRole([1, 2])
- Used by project management pages

```php
public function hasRole($roleId): bool
```
- Returns true if current user has specified role
- Used for conditional UI rendering

```php
public function canAccessBug($bugId): bool
```
- Checks if current user can access specific bug
- Regular Users: only bugs for their assigned project
- Managers/Admins: all bugs
- Returns boolean

```php
public function canUpdateBug($bugId): bool
```
- Checks if current user can update specific bug
- Regular Users: only bugs assigned to them
- Managers/Admins: all bugs
- Returns boolean

### User Class

The User class handles user-related database operations.

**Constructor:**
```php
public function __construct()
```
- Initializes Database instance

**Methods:**
```php
public function login($username, $password): array|false
```
- Validates credentials against database
- Uses password_verify() for hash comparison
- Returns user data array on success, false on failure
- Parameters: username string, plain password string

```php
public function getUserById($id): array|false
```
- Retrieves user record by ID
- Returns associative array or false if not found
- Parameters: user ID integer

```php
public function getAllUsers(): array
```
- Retrieves all users (Admin only)
- Returns array of user records with role and project info
- Joins with role and project tables for display names

```php
public function createUser($username, $password, $roleId, $name, $projectId = null): bool
```
- Creates new user with hashed password
- Uses password_hash() with PASSWORD_DEFAULT
- Validates: username unique, role exists, project exists (if provided)
- Parameters: username, plain password, role ID, full name, optional project ID
- Returns boolean success status

```php
public function updateUser($id, $fields): bool
```
- Updates user fields dynamically
- Validates: role exists, project exists (if provided)
- If password in fields, hashes before storing
- Parameters: user ID, associative array of field=>value pairs
- Returns boolean success status

```php
public function deleteUser($id): bool
```
- Deletes user and cleans up references
- Sets assignedToId to NULL in bugs table
- Sets owner to NULL in bugs table where user is owner
- Removes user record
- Parameters: user ID integer
- Returns boolean success status

```php
public function assignToProject($userId, $projectId): bool
```
- Assigns Regular User to project
- Validates: user is Regular User role, project exists
- Removes any existing project assignment first
- Parameters: user ID, project ID
- Returns boolean success status

```php
public function getUsersByProject($projectId): array
```
- Retrieves all users assigned to specific project
- Parameters: project ID integer
- Returns array of user records

### Project Class

The Project class handles project-related database operations.

**Constructor:**
```php
public function __construct()
```
- Initializes Database instance

**Methods:**
```php
public function getAllProjects(): array
```
- Retrieves all projects
- Returns array of project records

```php
public function getProjectById($id): array|false
```
- Retrieves project by ID
- Returns associative array or false if not found
- Parameters: project ID integer

```php
public function createProject($name, $description = null): bool
```
- Creates new project
- Validates: name is required and unique
- Parameters: project name string, optional description string
- Returns boolean success status

```php
public function updateProject($id, $name, $description = null): bool
```
- Updates project fields
- Validates: name is required, project exists
- Parameters: project ID, name string, optional description string
- Returns boolean success status

```php
public function deleteProject($id): bool
```
- Deletes project (Admin only)
- Validates: no bugs assigned to project
- Removes user assignments to project
- Parameters: project ID integer
- Returns boolean success status

```php
public function getProjectBugCount($id): int
```
- Returns count of bugs for project
- Parameters: project ID integer
- Returns integer count

### Bug Class

The Bug class handles bug-related database operations.

**Constructor:**
```php
public function __construct()
```
- Initializes Database instance

**Methods:**
```php
public function getAllBugs(): array
```
- Retrieves all bugs (Manager/Admin only)
- Joins with project, status, priority, user tables
- Returns array of bug records with related data

```php
public function getBugsByProject($projectId): array
```
- Retrieves bugs for specific project (Regular User)
- Joins with status, priority, user tables
- Parameters: project ID integer
- Returns array of bug records

```php
public function getBugById($id): array|false
```
- Retrieves single bug with all related data
- Joins with project, status, priority, owner, assignee tables
- Parameters: bug ID integer
- Returns associative array or false if not found

```php
public function getFilteredBugs($roleId, $projectId, $filter, $filterProjectId = null): array
```
- Retrieves bugs based on role and filter criteria
- Filters: 'all', 'open', 'overdue', 'unassigned'
- Regular Users: filtered by their assigned project
- Managers/Admins: can filter by specific project or all projects
- Parameters: role ID, user's project ID, filter string, optional filter project ID
- Returns array of bug records

```php
public function createBug($data): int|false
```
- Creates new bug with validation
- Required fields: description, summary, owner, dateRaised, projectId
- Optional fields: assignedToId, statusId, priorityId, targetDate
- Validates: owner exists, project exists, dates are valid
- Regular Users: auto-set statusId='unassigned', priorityId='medium', assignedToId=NULL
- Parameters: associative array of bug data
- Returns new bug ID on success, false on failure

```php
public function updateBug($id, $data): bool
```
- Updates bug with validation
- Validates: bug exists, foreign keys exist, dates are valid
- If statusId='closed': requires dateClosed and fixDescription
- Parameters: bug ID integer, associative array of fields to update
- Returns boolean success status

```php
public function deleteBug($id): bool
```
- Deletes bug (Admin only)
- Parameters: bug ID integer
- Returns boolean success status

```php
public function validateBugData($data, $isUpdate = false): array
```
- Validates bug data and returns array of errors
- Checks: required fields, data types, foreign keys, date logic
- Parameters: bug data array, boolean indicating update vs create
- Returns array of error messages (empty if valid)

### Template Class

The Template class provides reusable rendering functions without include/require.

**Static Methods:**
```php
public static function renderPage($title, $content, $roleId = null): void
```
- Renders complete HTML page with header, navigation, content, footer
- Navigation items based on role
- Parameters: page title, HTML content string, optional role ID

```php
public static function renderHeader($title): string
```
- Returns HTML for page header with title
- Parameters: page title string

```php
public static function renderNavigation($roleId): string
```
- Returns HTML for navigation menu based on role
- Regular User: Dashboard, Bugs, Logout
- Manager: Dashboard, Bugs, Projects, Logout
- Admin: Dashboard, Bugs, Projects, Users, Logout
- Parameters: role ID integer

```php
public static function renderFooter(): string
```
- Returns HTML for page footer with copyright

```php
public static function renderBugTable($bugs): string
```
- Returns HTML table of bugs
- Parameters: array of bug records

```php
public static function renderBugForm($bug = null, $projects = [], $users = [], $statuses = [], $priorities = []): string
```
- Returns HTML form for creating/editing bug
- Pre-fills fields if bug data provided
- Parameters: optional bug data, arrays of projects, users, statuses, priorities

```php
public static function renderProjectForm($project = null): string
```
- Returns HTML form for creating/editing project
- Pre-fills fields if project data provided
- Parameters: optional project data array

```php
public static function renderUserForm($user = null, $roles = [], $projects = []): string
```
- Returns HTML form for creating/editing user
- Pre-fills fields if user data provided
- Parameters: optional user data, arrays of roles and projects

```php
public static function renderError($message): string
```
- Returns HTML for error message display
- Parameters: error message string

```php
public static function renderSuccess($message): string
```
- Returns HTML for success message display
- Parameters: success message string

## Data Models

### Database Schema

The application uses the existing database schema without modification:

**user_details table:**
- Id (INT, PRIMARY KEY, AUTO_INCREMENT)
- Username (VARCHAR, UNIQUE, NOT NULL)
- Password (VARCHAR, NOT NULL) - hashed with password_hash()
- RoleID (INT, FOREIGN KEY to role.Id)
- Name (VARCHAR, NOT NULL)
- ProjectId (INT, FOREIGN KEY to project.Id, NULLABLE)

**role table:**
- Id (INT, PRIMARY KEY)
- Role (VARCHAR, NOT NULL)
- Values: 1=Admin, 2=Manager, 3=Regular User

**project table:**
- Id (INT, PRIMARY KEY, AUTO_INCREMENT)
- Project (VARCHAR, NOT NULL)
- Description (TEXT, NULLABLE)

**bugs table:**
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- projectId (INT, FOREIGN KEY to project.Id, NOT NULL)
- owner (INT, FOREIGN KEY to user_details.Id, NULLABLE)
- assignedToId (INT, FOREIGN KEY to user_details.Id, NULLABLE)
- statusId (VARCHAR, FOREIGN KEY to status.Id, NOT NULL)
- priorityId (VARCHAR, FOREIGN KEY to priority.Id, NOT NULL)
- summary (VARCHAR, NOT NULL)
- description (TEXT, NOT NULL)
- dateRaised (DATE, NOT NULL)
- targetDate (DATE, NULLABLE)
- dateClosed (DATE, NULLABLE)
- fixDescription (TEXT, NULLABLE)

**status table:**
- Id (VARCHAR, PRIMARY KEY)
- Status (VARCHAR, NOT NULL)
- Values: 'open', 'assigned', 'in-progress', 'testing', 'closed', 'unassigned'

**priority table:**
- Id (VARCHAR, PRIMARY KEY)
- Priority (VARCHAR, NOT NULL)
- Values: 'low', 'medium', 'high', 'critical'

### Data Validation Rules

**User Creation/Update:**
- Username: required, 3-50 characters, alphanumeric + underscore, unique
- Password: required (create only), minimum 8 characters
- RoleID: required, must exist in role table
- Name: required, 2-100 characters
- ProjectId: optional, must exist in project table if provided, only for Regular Users

**Project Creation/Update:**
- Project (name): required, 3-100 characters, unique
- Description: optional, max 500 characters

**Bug Creation/Update:**
- projectId: required, must exist in project table
- owner: required, must exist in user_details table
- assignedToId: optional, must exist in user_details table if provided
- statusId: required, must exist in status table
- priorityId: required, must exist in priority table
- summary: required, 5-200 characters
- description: required, 10-5000 characters
- dateRaised: required, valid date, not future date
- targetDate: optional, valid date, must be future date if provided
- dateClosed: required if statusId='closed', must be >= dateRaised
- fixDescription: required if statusId='closed', 10-5000 characters

### Business Rules

**Role-Based Access:**
- Regular User: projectId must be set, can only access bugs for their project
- Manager: projectId must be NULL, can access all bugs and manage projects
- Admin: projectId must be NULL, can access everything including user management

**Bug Assignment:**
- Regular Users cannot set assignedToId when creating bugs
- Regular Users can only update bugs where assignedToId matches their userId
- Managers/Admins can update any bug

**Project Assignment:**
- Regular Users can be assigned to exactly one project
- Managers and Admins cannot be assigned to projects
- Changing a Regular User's project removes previous assignment

**User Deletion:**
- Cannot delete currently logged-in user
- Deleting user sets assignedToId to NULL for all assigned bugs
- Deleting user sets owner to NULL for all owned bugs
- Deleting user removes project assignment

## Controller Logic

### Login Controller (controllers/login.php)

**Request Handling:**
1. Start session
2. If GET request: redirect to login view
3. If POST request:
   - Sanitize username and password inputs
   - Call User->login($username, $password)
   - If successful:
     - Store userId, roleId, projectId, username in session
     - Redirect to dashboard
   - If failed:
     - Set error message
     - Redirect to login view with error

**Validation:**
- Username: trim whitespace, check not empty
- Password: check not empty (no trim to preserve spaces)

### Logout Controller (controllers/logout.php)

**Request Handling:**
1. Start session
2. Call session_unset()
3. Call session_destroy()
4. Redirect to login page

### Bugs Controller (controllers/bugs.php)

**Request Handling:**

**GET /bugs (list view):**
1. Auth->requireLogin()
2. Get filter parameter (default: 'all')
3. Get filterProject parameter (Manager/Admin only)
4. Call Bug->getFilteredBugs($_SESSION['roleId'], $_SESSION['projectId'], $filter, $filterProject)
5. Render bugs view with bug list

**GET /bugs?id=X (detail view):**
1. Auth->requireLogin()
2. Get bug ID from query string
3. Call Bug->getBugById($id)
4. Call Auth->canAccessBug($id)
5. If authorized: render bug detail view
6. If unauthorized: redirect to bugs list with error

**POST /bugs (create):**
1. Auth->requireLogin()
2. Sanitize all input fields
3. Build bug data array
4. If Regular User: override assignedToId=NULL, statusId='unassigned', priorityId='medium'
5. Call Bug->validateBugData($data)
6. If valid: call Bug->createBug($data)
7. Redirect to bugs list with success/error message

**POST /bugs?id=X (update):**
1. Auth->requireLogin()
2. Get bug ID from query string
3. Call Auth->canUpdateBug($id)
4. If unauthorized: redirect with error
5. Sanitize all input fields
6. Build update data array (only changed fields)
7. Call Bug->validateBugData($data, true)
8. If valid: call Bug->updateBug($id, $data)
9. Redirect to bug detail with success/error message

**POST /bugs?id=X&action=delete:**
1. Auth->requireAdmin()
2. Get bug ID from query string
3. Call Bug->deleteBug($id)
4. Redirect to bugs list with success/error message

### Projects Controller (controllers/projects.php)

**Request Handling:**

**GET /projects (list view):**
1. Auth->requireManager()
2. Call Project->getAllProjects()
3. For each project: call Project->getProjectBugCount($id)
4. Render projects view with project list

**POST /projects (create):**
1. Auth->requireManager()
2. Sanitize name and description inputs
3. Validate: name required, 3-100 chars
4. Call Project->createProject($name, $description)
5. Redirect to projects list with success/error message

**POST /projects?id=X (update):**
1. Auth->requireManager()
2. Get project ID from query string
3. Sanitize name and description inputs
4. Validate: name required, 3-100 chars
5. Call Project->updateProject($id, $name, $description)
6. Redirect to projects list with success/error message

**POST /projects?id=X&action=delete:**
1. Auth->requireAdmin()
2. Get project ID from query string
3. Call Project->getProjectBugCount($id)
4. If count > 0: redirect with error "Cannot delete project with bugs"
5. Call Project->deleteProject($id)
6. Redirect to projects list with success/error message

### Users Controller (controllers/users.php)

**Request Handling:**

**GET /users (list view):**
1. Auth->requireAdmin()
2. Call User->getAllUsers()
3. Call Project->getAllProjects()
4. Render users view with user list and projects

**POST /users (create):**
1. Auth->requireAdmin()
2. Sanitize all input fields
3. Validate:
   - Username: required, 3-50 chars, alphanumeric + underscore, unique
   - Password: required, min 8 chars
   - RoleID: required, exists in role table
   - Name: required, 2-100 chars
   - ProjectId: optional, exists if provided, only if roleId=3
4. Call User->createUser($username, $password, $roleId, $name, $projectId)
5. Redirect to users list with success/error message

**POST /users?id=X (update):**
1. Auth->requireAdmin()
2. Get user ID from query string
3. Sanitize all input fields
4. Build update data array (only changed fields)
5. Validate fields as in create
6. If password provided: validate min 8 chars
7. Call User->updateUser($id, $data)
8. Redirect to users list with success/error message

**POST /users?id=X&action=delete:**
1. Auth->requireAdmin()
2. Get user ID from query string
3. If $id == $_SESSION['userId']: redirect with error "Cannot delete yourself"
4. Call User->deleteUser($id)
5. Redirect to users list with success/error message

**POST /users?id=X&action=assign:**
1. Auth->requireManager()
2. Get user ID and project ID from request
3. Validate: user has roleId=3, project exists
4. Call User->assignToProject($userId, $projectId)
5. Redirect to users list with success/error message

## View Rendering Strategy

### Template-Based Rendering

All views use the Template class for common elements instead of include/require:

**Page Structure:**
```php
<?php
// Controller logic at top
$auth = new Auth();
$auth->requireLogin();

// Fetch data
$data = // ... get data from models

// Build content HTML
$content = Template::renderNavigation($_SESSION['roleId']);
$content .= '<div class="main-content">';
$content .= // ... page-specific content
$content .= '</div>';

// Render complete page
Template::renderPage($title, $content, $_SESSION['roleId']);
?>
```

**Navigation Menu:**
- Dynamically generated based on role
- Template::renderNavigation($roleId) returns appropriate menu items
- No hardcoded navigation in individual views

**Forms:**
- Template class provides form rendering methods
- Forms include CSRF token (stored in session)
- All forms POST to controllers, not to views

**Error/Success Messages:**
- Stored in session flash variables
- Template::renderError() and Template::renderSuccess() display and clear
- Prevents message persistence on page refresh

### Input Sanitization

All user input sanitized before use:

**Display in HTML:**
```php
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

**Use in SQL:**
- Always use prepared statements (handled by Database class)
- Never concatenate user input into SQL

**Validation:**
- Server-side validation for all inputs
- Type checking (is_numeric, is_string, etc.)
- Length checking (strlen, mb_strlen)
- Format checking (regex for username, dates, etc.)
- Foreign key validation (check record exists)

### CSRF Protection

**Token Generation:**
```php
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

**Token in Forms:**
```php
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
```

**Token Validation:**
```php
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

### Error Handling

**Database Errors:**
- PDO configured with PDO::ERRMODE_EXCEPTION
- Try-catch blocks in model methods
- Log errors to file, display generic message to user

**Validation Errors:**
- Collect all errors in array
- Display all errors to user at once
- Preserve form input for correction

**Authorization Errors:**
- Redirect to appropriate page with error message
- Log unauthorized access attempts


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, several redundancies were identified:
- Properties 6.1 and 2.3 both test Regular User update authorization (consolidated)
- Properties 6.2 and 3.5 both test Manager/Admin update permissions (consolidated)
- Properties 12.2 and 2.5 both test single project assignment constraint (consolidated)
- Properties 12.3 and 3.6 both test Manager/Admin project assignment prevention (consolidated)
- Properties 12.4 and 3.2 both test project validation (consolidated)
- General validation requirements (10.1, 10.2, 10.4, 10.5) are covered by specific validation properties
- SQL injection prevention (9.1-9.5, 6.5) is an implementation detail verified through code review
- Template implementation (11.1-11.4) is an implementation detail verified through code review
- Code organization (15.1-15.5) is verified through code review
- Database schema compliance (16.1-16.3, 16.5) is verified through code review
- Initial data population (13.1-13.6) is a one-time setup, not a system behavior property

The following properties provide unique validation value:

### Property 1: Valid Credentials Create Session

*For any* user with valid credentials in the database, when those credentials are submitted to the login function, the system should create a session containing userId, roleId, projectId, and username, and redirect to the dashboard.

**Validates: Requirements 1.1, 1.6**

### Property 2: Invalid Credentials Reject Login

*For any* credentials that do not match a user in the database (either username doesn't exist or password doesn't match), when those credentials are submitted to the login function, the system should not create a session, should display an error message, and should remain on the login page.

**Validates: Requirements 1.2**

### Property 3: Password Storage Uses Hashing

*For any* user created in the system, the stored password in the database should be a valid hash that can be verified using password_verify() with the original password, and should not be the plain text password.

**Validates: Requirements 1.3**

### Property 4: Unauthenticated Access Redirects to Login

*For any* protected page in the system, when accessed without a valid session, the system should redirect to the login page and should not display the protected content.

**Validates: Requirements 1.4**

### Property 5: Logout Destroys Session

*For any* authenticated session, when the logout action is performed, the system should destroy the session (all session variables should be unset) and redirect to the login page.

**Validates: Requirements 1.5**

### Property 6: Regular User Bug Access Filtered by Project

*For any* Regular User with an assigned project, when they query the bugs list, the system should return only bugs where projectId matches their assigned project, and should not return bugs from other projects.

**Validates: Requirements 2.1, 2.4**

### Property 7: Regular User Bug Creation Sets Project

*For any* Regular User with an assigned project, when they create a bug, the system should set the bug's projectId to their assigned project regardless of any projectId value submitted in the request.

**Validates: Requirements 2.2**

### Property 8: Regular User Update Authorization

*For any* Regular User, when they attempt to update a bug, the system should allow the update only if the bug's assignedToId matches their userId, and should deny the update for all other bugs.

**Validates: Requirements 2.3, 6.1**

### Property 9: Single Project Assignment for Regular Users

*For any* Regular User, when they are assigned to a project, the system should ensure they have exactly one project assignment (any previous project assignment should be removed).

**Validates: Requirements 2.5, 12.2**

### Property 10: Manager Views All Bugs

*For any* Manager or Admin user, when they query the bugs list without a project filter, the system should return bugs from all projects.

**Validates: Requirements 3.3**

### Property 11: Manager Update Authorization

*For any* Manager or Admin user, when they attempt to update any bug in the system, the system should allow the update regardless of the bug's projectId or assignedToId.

**Validates: Requirements 3.5, 6.2**

### Property 12: Manager Project Assignment Prevention

*For any* user with Manager or Admin role, when an attempt is made to assign them to a project, the system should prevent the assignment and their projectId should remain NULL.

**Validates: Requirements 3.6, 12.3**

### Property 13: Project Creation Validation

*For any* project creation attempt, the system should validate that the project name is present, is between 3-100 characters, and is unique, and should reject creation if any validation fails.

**Validates: Requirements 3.2, 12.4**

### Property 14: Manager Bug Filtering

*For any* Manager or Admin user, when they apply a filter (all, open, overdue) with an optional project filter, the system should return bugs matching the filter criteria from either the specified project or all projects.

**Validates: Requirements 3.4, 8.4**

### Property 15: User Deletion Cascades to Bugs

*For any* user deletion, the system should set assignedToId to NULL for all bugs where assignedToId equals the deleted user's ID, and should set owner to NULL for all bugs where owner equals the deleted user's ID.

**Validates: Requirements 4.3, 4.4, 4.5**

### Property 16: Non-Admin Authorization Denial

*For any* user with a role other than Admin, when they attempt to access user management functionality, the system should deny access and redirect with an error message.

**Validates: Requirements 4.7**

### Property 17: Bug Creation Required Fields

*For any* bug creation attempt, the system should validate that description, summary, owner, dateRaised, and projectId are present and non-empty, and should reject creation if any required field is missing.

**Validates: Requirements 5.1**

### Property 18: Bug Creation Foreign Key Validation

*For any* bug creation attempt, the system should validate that the owner exists in the user_details table and the projectId exists in the project table, and should reject creation if either foreign key is invalid.

**Validates: Requirements 5.2, 5.3**

### Property 19: Bug Creation Date Validation

*For any* bug creation attempt, the system should validate that dateRaised is a valid date format and is not a future date, and should reject creation if the date is invalid.

**Validates: Requirements 5.4**

### Property 20: Regular User Bug Creation Auto-Fields

*For any* Regular User bug creation, the system should automatically set assignedToId to NULL, statusId to "unassigned", and priorityId to "medium", regardless of any values submitted in the request.

**Validates: Requirements 5.5**

### Property 21: Manager Bug Creation Field Permissions

*For any* Manager or Admin bug creation, the system should allow setting assignedToId, statusId, priorityId, and targetDate to the values provided in the request.

**Validates: Requirements 5.6**

### Property 22: Bug Creation Initial Null Fields

*For any* bug creation, the system should set dateClosed and fixDescription to NULL regardless of any values submitted in the request.

**Validates: Requirements 5.7**

### Property 23: Target Date Future Validation

*For any* bug creation or update with a targetDate value, the system should validate that targetDate is a future date (greater than or equal to current date), and should reject the operation if targetDate is in the past.

**Validates: Requirements 5.8**

### Property 24: Bug Update Validation Consistency

*For any* bug update attempt, the system should apply the same validation rules as bug creation (required fields, foreign keys, date formats, date logic), and should reject the update if any validation fails.

**Validates: Requirements 6.3**

### Property 25: Closed Bug Required Fields

*For any* bug update that changes statusId to "closed", the system should validate that dateClosed and fixDescription are present and non-empty, and should reject the update if either field is missing.

**Validates: Requirements 6.4**

### Property 26: Bug Update Foreign Key Validation

*For any* bug update with an assignedToId value, the system should validate that the user exists in the user_details table, and should reject the update if the foreign key is invalid.

**Validates: Requirements 6.6**

### Property 27: Regular User All Bugs Filter

*For any* Regular User with an assigned project, when they apply the "all bugs" filter, the system should return all bugs where projectId matches their assigned project.

**Validates: Requirements 7.1**

### Property 28: Regular User Open Bugs Filter

*For any* Regular User with an assigned project, when they apply the "open bugs" filter, the system should return bugs where projectId matches their assigned project and statusId is not equal to "closed".

**Validates: Requirements 7.2**

### Property 29: Regular User Overdue Bugs Filter

*For any* Regular User with an assigned project, when they apply the "overdue bugs" filter, the system should return bugs where projectId matches their assigned project, targetDate is less than the current date, and statusId is not equal to "closed".

**Validates: Requirements 7.3**

### Property 30: Manager All Bugs Filter

*For any* Manager or Admin user, when they apply the "all bugs" filter without a project filter, the system should return all bugs across all projects.

**Validates: Requirements 8.1**

### Property 31: Manager Open Bugs Filter

*For any* Manager or Admin user, when they apply the "open bugs" filter without a project filter, the system should return bugs where statusId is not equal to "closed" across all projects.

**Validates: Requirements 8.2**

### Property 32: Manager Overdue Bugs Filter

*For any* Manager or Admin user, when they apply the "overdue bugs" filter without a project filter, the system should return bugs where targetDate is less than the current date and statusId is not equal to "closed" across all projects.

**Validates: Requirements 8.3**

### Property 33: Manager Unassigned Bugs Filter

*For any* Manager or Admin user, when they apply the "unassigned bugs" filter, the system should return bugs where assignedToId is NULL.

**Validates: Requirements 8.5**

### Property 34: XSS Prevention Through Sanitization

*For any* alphanumeric field submitted by a user, when the value is displayed in HTML output, the system should sanitize the value using htmlspecialchars() to prevent XSS attacks (special characters like <, >, &, " should be converted to HTML entities).

**Validates: Requirements 10.3**

### Property 35: Validation Error Display

*For any* form submission that fails validation, the system should display all validation error messages to the user and should preserve the submitted form values for correction.

**Validates: Requirements 10.6**

### Property 36: Dynamic Navigation Based on Role

*For any* user, when their role changes in the database and they log in again, the system should display navigation menu items appropriate to their new role (Regular User: Dashboard, Bugs, Logout; Manager: Dashboard, Bugs, Projects, Logout; Admin: Dashboard, Bugs, Projects, Users, Logout).

**Validates: Requirements 11.5**

### Property 37: Project Assignment Updates User Record

*For any* Regular User, when a Manager or Admin assigns them to a project, the system should update the user's projectId field in the database to the assigned project ID.

**Validates: Requirements 12.1**

### Property 38: Bug Detail Displays Project Name

*For any* bug detail view, the system should display the project name by joining with the project table, not just the projectId.

**Validates: Requirements 14.2**

### Property 39: Bug Detail Displays Assigned User Name

*For any* bug detail view where assignedToId is not NULL, the system should display the assigned user's name by joining with the user_details table; where assignedToId is NULL, the system should display "Unassigned" or similar indicator.

**Validates: Requirements 14.3**

### Property 40: Bug Detail Displays Lookup Labels

*For any* bug detail view, the system should display human-readable status and priority labels by joining with the status and priority tables, not just the statusId and priorityId codes.

**Validates: Requirements 14.4**

### Property 41: Closed Bug Detail Displays Closure Fields

*For any* bug detail view where statusId equals "closed", the system should display the dateClosed and fixDescription fields.

**Validates: Requirements 14.5**

### Property 42: NULL Value Handling

*For any* database operation involving nullable columns (projectId, assignedToId, owner, targetDate, dateClosed, fixDescription), the system should correctly handle NULL values in both storage and retrieval without errors.

**Validates: Requirements 16.4**

## Error Handling

### Authentication Errors

**Invalid Credentials:**
- Display: "Invalid username or password"
- Action: Remain on login page, clear password field
- Logging: Log failed login attempt with username and timestamp

**Session Timeout:**
- Display: "Your session has expired. Please log in again."
- Action: Redirect to login page
- Logging: Log session timeout with userId and timestamp

**Unauthorized Access:**
- Display: "You do not have permission to access this resource."
- Action: Redirect to dashboard
- Logging: Log unauthorized access attempt with userId, roleId, and requested resource

### Validation Errors

**Missing Required Fields:**
- Display: "The following fields are required: [field list]"
- Action: Remain on form page, preserve submitted values
- Logging: Log validation failure with userId and form data

**Invalid Data Format:**
- Display: "Invalid format for [field name]. Expected: [format description]"
- Action: Remain on form page, preserve submitted values
- Logging: Log validation failure with userId and invalid value

**Foreign Key Violation:**
- Display: "Invalid [entity name]. Please select a valid option."
- Action: Remain on form page, preserve submitted values
- Logging: Log validation failure with userId and invalid foreign key

**Business Rule Violation:**
- Display: Specific message for the rule (e.g., "Target date must be in the future")
- Action: Remain on form page, preserve submitted values
- Logging: Log validation failure with userId and rule violated

### Database Errors

**Connection Failure:**
- Display: "Database connection failed. Please try again later."
- Action: Display error page with retry option
- Logging: Log full PDO exception with stack trace

**Query Execution Failure:**
- Display: "An error occurred while processing your request. Please try again."
- Action: Remain on current page or redirect to safe page
- Logging: Log full PDO exception with SQL query (sanitized) and parameters

**Constraint Violation:**
- Display: Specific message based on constraint (e.g., "Username already exists")
- Action: Remain on form page, preserve submitted values
- Logging: Log constraint violation with userId and attempted operation

### Application Errors

**CSRF Token Validation Failure:**
- Display: "Security token validation failed. Please try again."
- Action: Redirect to form page with new token
- Logging: Log CSRF failure with userId and request details

**File Not Found:**
- Display: "The requested page could not be found."
- Action: Display 404 error page with navigation
- Logging: Log 404 with requested URL and userId

**Unexpected Exceptions:**
- Display: "An unexpected error occurred. Please contact support."
- Action: Display error page with error ID for support reference
- Logging: Log full exception with stack trace and error ID

### Error Logging Strategy

**Log Location:**
- Development: Display errors on screen + log to file
- Production: Log to file only, display generic messages

**Log Format:**
```
[YYYY-MM-DD HH:MM:SS] [ERROR_LEVEL] [USER_ID] [ERROR_TYPE] Message
Stack trace (if applicable)
```

**Log Levels:**
- ERROR: Authentication failures, authorization failures, validation errors
- CRITICAL: Database connection failures, unexpected exceptions
- WARNING: Deprecated function usage, performance issues
- INFO: Successful operations, user actions

**Log Rotation:**
- Daily log files
- Keep logs for 30 days
- Compress logs older than 7 days

## Testing Strategy

### Dual Testing Approach

The testing strategy employs both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests:**
- Verify specific examples and edge cases
- Test integration points between components
- Validate error conditions and error messages
- Test specific user scenarios (e.g., "Admin deletes user with 5 assigned bugs")

**Property-Based Tests:**
- Verify universal properties across all inputs
- Use randomized input generation to test many scenarios
- Validate correctness properties defined in this document
- Each property test runs minimum 100 iterations

Both approaches are complementary and necessary. Unit tests catch concrete bugs in specific scenarios, while property tests verify general correctness across the input space.

### Property-Based Testing Configuration

**Library Selection:**
- PHP: Use `eris/eris` library for property-based testing
- Installation: `composer require --dev giorgiosironi/eris`

**Test Configuration:**
- Minimum 100 iterations per property test
- Each test tagged with comment referencing design property
- Tag format: `// Feature: complete-bug-tracker-system, Property X: [property text]`

**Example Property Test Structure:**
```php
/**
 * Feature: complete-bug-tracker-system, Property 1: Valid Credentials Create Session
 */
public function testValidCredentialsCreateSession() {
    $this->forAll(
        Generator\associative([
            'username' => Generator\string(),
            'password' => Generator\string()
        ])
    )
    ->then(function($credentials) {
        // Create user with credentials
        $user = $this->createUser($credentials);
        
        // Attempt login
        $result = $this->auth->login($credentials['username'], $credentials['password']);
        
        // Assert session created
        $this->assertTrue(isset($_SESSION['userId']));
        $this->assertEquals($user['Id'], $_SESSION['userId']);
        $this->assertEquals($user['RoleID'], $_SESSION['roleId']);
    });
}
```

### Unit Testing Strategy

**Test Organization:**
- tests/Unit/Auth.test.php - Authentication tests
- tests/Unit/Bug.test.php - Bug operations tests
- tests/Unit/Project.test.php - Project operations tests
- tests/Unit/User.test.php - User operations tests
- tests/Integration/Controllers.test.php - Controller integration tests

**Test Coverage Goals:**
- Minimum 80% code coverage
- 100% coverage of critical security functions (authentication, authorization, validation)
- All error handling paths tested

**Example Unit Test:**
```php
public function testRegularUserCannotAccessOtherProjectBugs() {
    // Arrange
    $user = $this->createRegularUser(['projectId' => 1]);
    $bug = $this->createBug(['projectId' => 2]);
    $this->loginAs($user);
    
    // Act
    $result = $this->auth->canAccessBug($bug['id']);
    
    // Assert
    $this->assertFalse($result);
}
```

### Integration Testing

**Test Scenarios:**
- Complete user workflows (login → view bugs → create bug → update bug → logout)
- Role-based access control across all pages
- Form submission with validation errors
- Database transaction rollback on errors

**Test Environment:**
- Separate test database with known seed data
- Reset database state before each test
- Use transactions to isolate tests

### Security Testing

**SQL Injection Testing:**
- Submit malicious SQL in all input fields
- Verify prepared statements prevent injection
- Test with common SQL injection payloads

**XSS Testing:**
- Submit malicious JavaScript in all text fields
- Verify output is properly sanitized
- Test with common XSS payloads

**CSRF Testing:**
- Submit forms without CSRF token
- Submit forms with invalid CSRF token
- Verify all state-changing operations require valid token

**Authentication Testing:**
- Attempt to access protected pages without session
- Attempt to access pages with expired session
- Attempt to access pages with tampered session data

### Performance Testing

**Load Testing:**
- Simulate 100 concurrent users
- Measure response times for all pages
- Identify bottlenecks in database queries

**Database Query Optimization:**
- Use EXPLAIN on all queries
- Add indexes where needed
- Optimize N+1 query problems

### Manual Testing Checklist

**Authentication:**
- [ ] Login with valid credentials succeeds
- [ ] Login with invalid credentials fails
- [ ] Logout destroys session
- [ ] Accessing protected page without session redirects to login

**Regular User:**
- [ ] Can view only bugs for assigned project
- [ ] Can create bugs (auto-assigned to their project)
- [ ] Can update only bugs assigned to them
- [ ] Cannot access user management
- [ ] Cannot access project management

**Manager:**
- [ ] Can view bugs from all projects
- [ ] Can filter bugs by project
- [ ] Can create and update projects
- [ ] Can update any bug
- [ ] Cannot access user management

**Admin:**
- [ ] Can access all functionality
- [ ] Can create users
- [ ] Can delete users (cascades to bugs)
- [ ] Cannot delete self

**Validation:**
- [ ] Required fields enforced
- [ ] Date validation works
- [ ] Foreign key validation works
- [ ] Error messages displayed correctly

**Security:**
- [ ] XSS attempts are sanitized
- [ ] SQL injection attempts are blocked
- [ ] CSRF tokens are validated
- [ ] Unauthorized access is denied
