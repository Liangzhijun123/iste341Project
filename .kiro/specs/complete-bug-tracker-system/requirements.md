# Requirements Document

## Introduction

This document specifies the requirements for completing a bug tracker PHP application for the ISTE341 course project. The application enables teams to track software bugs across multiple projects with role-based access control. The system has existing class files, controllers, and views that need to be fully implemented and integrated to meet all functional and technical requirements.

## Glossary

- **Bug_Tracker_System**: The complete PHP web application for tracking software bugs
- **Authentication_Module**: The subsystem responsible for user login, logout, and session management
- **User**: A person with an account in the system (can be Regular User, Manager, or Admin)
- **Regular_User**: A user role that can only access bugs for their assigned project
- **Manager**: A user role that can manage projects and view all bugs across projects
- **Admin**: A user role with full system access including user management
- **Bug**: A software defect record with description, status, priority, and assignment information
- **Project**: A software project that contains multiple bugs
- **Session**: Server-side storage of authenticated user state
- **Prepared_Statement**: A parameterized SQL query that prevents SQL injection
- **Role_Based_Access_Control**: Authorization system that grants permissions based on user role

## Requirements

### Requirement 1: User Authentication System

**User Story:** As a system user, I want to log in with my credentials, so that I can access the bug tracker securely.

#### Acceptance Criteria

1. WHEN a user submits valid credentials, THE Authentication_Module SHALL create a session and redirect to the dashboard
2. WHEN a user submits invalid credentials, THE Authentication_Module SHALL display an error message and remain on the login page
3. THE Authentication_Module SHALL store all passwords using password_hash() with PASSWORD_DEFAULT algorithm
4. WHEN a user attempts to access any page without a valid session, THE Bug_Tracker_System SHALL redirect to the login page
5. WHEN a user clicks logout, THE Authentication_Module SHALL destroy the session and redirect to the login page
6. THE Authentication_Module SHALL store the user's role in the session for authorization checks

### Requirement 2: Regular User Bug Access Control

**User Story:** As a regular user, I want to view and update only bugs for my assigned project, so that I can focus on my team's work.

#### Acceptance Criteria

1. WHEN a Regular_User views the bugs page, THE Bug_Tracker_System SHALL display only bugs for the project they are assigned to
2. WHEN a Regular_User creates a bug, THE Bug_Tracker_System SHALL set the projectId to their assigned project
3. WHEN a Regular_User updates a bug, THE Bug_Tracker_System SHALL only allow updates to bugs assigned to them
4. THE Bug_Tracker_System SHALL prevent Regular_User from accessing bugs for projects they are not assigned to
5. WHEN a Regular_User is assigned to a project, THE Bug_Tracker_System SHALL ensure they are assigned to only one project at a time

### Requirement 3: Manager Project and Bug Management

**User Story:** As a manager, I want to create projects and view bugs across all projects, so that I can oversee multiple teams.

#### Acceptance Criteria

1. WHEN a Manager accesses the admin page, THE Bug_Tracker_System SHALL display project creation and update forms
2. WHEN a Manager creates a project, THE Bug_Tracker_System SHALL validate all required fields and save the project
3. WHEN a Manager views the bugs page, THE Bug_Tracker_System SHALL display bugs from all projects
4. WHEN a Manager filters bugs, THE Bug_Tracker_System SHALL allow filtering by specific project or all projects
5. WHEN a Manager updates a bug, THE Bug_Tracker_System SHALL allow updates to any bug regardless of project
6. THE Bug_Tracker_System SHALL prevent Manager from being assigned to any specific project

### Requirement 4: Admin User Management

**User Story:** As an admin, I want to add and delete users, so that I can control system access.

#### Acceptance Criteria

1. WHEN an Admin accesses the admin page, THE Bug_Tracker_System SHALL display user management interface
2. WHEN an Admin creates a user, THE Bug_Tracker_System SHALL validate all required fields and hash the password
3. WHEN an Admin deletes a user, THE Bug_Tracker_System SHALL remove the user from all project assignments
4. WHEN an Admin deletes a user, THE Bug_Tracker_System SHALL update all bugs assigned to that user to set assignedToId to NULL
5. WHEN an Admin deletes a user, THE Bug_Tracker_System SHALL update all bugs owned by that user to set owner to NULL
6. THE Bug_Tracker_System SHALL prevent deletion of the currently logged-in Admin user
7. WHEN a non-Admin user attempts to access user management, THE Bug_Tracker_System SHALL deny access

### Requirement 5: Bug Creation with Validation

**User Story:** As a user, I want to create bug reports with all necessary information, so that bugs can be tracked effectively.

#### Acceptance Criteria

1. WHEN a user creates a bug, THE Bug_Tracker_System SHALL require description, summary, owner, dateRaised, and projectId fields
2. WHEN a user creates a bug, THE Bug_Tracker_System SHALL validate that owner exists in the User table
3. WHEN a user creates a bug, THE Bug_Tracker_System SHALL validate that projectId exists in the Project table
4. WHEN a user creates a bug, THE Bug_Tracker_System SHALL validate that dateRaised is a valid date
5. WHEN a Regular_User creates a bug without assignment permission, THE Bug_Tracker_System SHALL set assignedToId to NULL, statusId to "unassigned", and priorityId to "medium"
6. WHEN a Manager or Admin creates a bug, THE Bug_Tracker_System SHALL allow setting assignedToId, statusId, priorityId, and targetDate
7. WHEN a user creates a bug, THE Bug_Tracker_System SHALL set dateClosed and fixDescription to NULL
8. WHEN targetDate is provided, THE Bug_Tracker_System SHALL validate it is a future date

### Requirement 6: Bug Update with Role-Based Permissions

**User Story:** As a user, I want to update bug information based on my role permissions, so that bug tracking stays current.

#### Acceptance Criteria

1. WHEN a Regular_User updates a bug, THE Bug_Tracker_System SHALL only allow updates to bugs where assignedToId matches their user ID
2. WHEN a Manager or Admin updates a bug, THE Bug_Tracker_System SHALL allow updates to any bug
3. WHEN a bug is updated, THE Bug_Tracker_System SHALL validate all field constraints as in bug creation
4. WHEN a bug status is changed to "closed", THE Bug_Tracker_System SHALL require dateClosed and fixDescription fields
5. WHEN a bug is updated, THE Bug_Tracker_System SHALL use prepared statements for all database queries
6. WHEN assignedToId is provided, THE Bug_Tracker_System SHALL validate the user exists in the User table

### Requirement 7: Bug Filtering for Regular Users

**User Story:** As a regular user, I want to filter bugs by status and due date, so that I can prioritize my work.

#### Acceptance Criteria

1. WHEN a Regular_User selects "all bugs" filter, THE Bug_Tracker_System SHALL display all bugs for their assigned project
2. WHEN a Regular_User selects "open bugs" filter, THE Bug_Tracker_System SHALL display bugs with statusId not equal to "closed" for their assigned project
3. WHEN a Regular_User selects "overdue bugs" filter, THE Bug_Tracker_System SHALL display bugs where targetDate is less than current date and statusId is not "closed" for their assigned project
4. THE Bug_Tracker_System SHALL apply project restriction to all filters for Regular_User

### Requirement 8: Bug Filtering for Managers and Admins

**User Story:** As a manager or admin, I want to filter bugs across all projects or by specific project, so that I can monitor system-wide bug status.

#### Acceptance Criteria

1. WHEN a Manager or Admin selects "all bugs" filter, THE Bug_Tracker_System SHALL display all bugs across all projects
2. WHEN a Manager or Admin selects "open bugs" filter, THE Bug_Tracker_System SHALL display bugs with statusId not equal to "closed" across all projects
3. WHEN a Manager or Admin selects "overdue bugs" filter, THE Bug_Tracker_System SHALL display bugs where targetDate is less than current date and statusId is not "closed" across all projects
4. WHEN a Manager or Admin selects a specific project filter, THE Bug_Tracker_System SHALL apply the status filter to only that project
5. WHEN a Manager or Admin views unassigned bugs, THE Bug_Tracker_System SHALL display bugs where assignedToId is NULL

### Requirement 9: SQL Injection Prevention

**User Story:** As a system administrator, I want all database queries to use prepared statements, so that the application is protected from SQL injection attacks.

#### Acceptance Criteria

1. THE Database_Module SHALL use parameterized prepared statements for all SELECT queries
2. THE Database_Module SHALL use parameterized prepared statements for all INSERT queries
3. THE Database_Module SHALL use parameterized prepared statements for all UPDATE queries
4. THE Database_Module SHALL use parameterized prepared statements for all DELETE queries
5. THE Bug_Tracker_System SHALL never concatenate user input directly into SQL query strings

### Requirement 10: Input Validation and Sanitization

**User Story:** As a system administrator, I want all user input validated and sanitized, so that the application is secure from malicious input.

#### Acceptance Criteria

1. WHEN a user submits a form, THE Bug_Tracker_System SHALL validate all required fields are present on the server side
2. WHEN a user submits a form, THE Bug_Tracker_System SHALL validate data types match expected types on the server side
3. WHEN a user submits alphanumeric fields, THE Bug_Tracker_System SHALL sanitize input to prevent XSS attacks
4. WHEN a user submits date fields, THE Bug_Tracker_System SHALL validate dates are in correct format and logically valid
5. WHEN a user submits foreign key fields, THE Bug_Tracker_System SHALL validate referenced records exist in the database
6. THE Bug_Tracker_System SHALL display validation error messages to the user when validation fails

### Requirement 11: Template-Based Page Rendering

**User Story:** As a developer, I want common page elements rendered through templates or functions, so that the codebase is maintainable and DRY.

#### Acceptance Criteria

1. THE Template_Module SHALL render navigation menus without using include or require statements
2. THE Template_Module SHALL render page headers without using include or require statements
3. THE Template_Module SHALL render page footers without using include or require statements
4. THE Bug_Tracker_System SHALL use the Template_Module for all common page elements across views
5. WHEN a user's role changes, THE Template_Module SHALL dynamically adjust navigation menu items based on permissions

### Requirement 12: Project Assignment Management

**User Story:** As a manager or admin, I want to assign users to projects, so that team members can access their project bugs.

#### Acceptance Criteria

1. WHEN a Manager or Admin assigns a Regular_User to a project, THE Bug_Tracker_System SHALL update the user's project assignment
2. WHEN a Manager or Admin assigns a Regular_User to a new project, THE Bug_Tracker_System SHALL remove any existing project assignment
3. THE Bug_Tracker_System SHALL prevent assigning Manager or Admin users to projects
4. WHEN a project is updated, THE Bug_Tracker_System SHALL validate all required project fields
5. WHEN a Manager or Admin views projects, THE Bug_Tracker_System SHALL display all users assigned to each project

### Requirement 13: System Data Population

**User Story:** As a system administrator, I want the system populated with initial data, so that the application can be demonstrated and tested.

#### Acceptance Criteria

1. THE Bug_Tracker_System SHALL contain at least one user with Admin role
2. THE Bug_Tracker_System SHALL contain at least one user with Manager role
3. THE Bug_Tracker_System SHALL contain at least one user with Regular_User role
4. THE Bug_Tracker_System SHALL contain at least two projects
5. THE Bug_Tracker_System SHALL contain at least two bugs with different statuses and priorities
6. THE Bug_Tracker_System SHALL assign the Regular_User to one of the projects

### Requirement 14: Bug Detail View

**User Story:** As a user, I want to view complete bug details, so that I can understand the full context of a bug.

#### Acceptance Criteria

1. WHEN a user clicks on a bug, THE Bug_Tracker_System SHALL display all bug fields including description, summary, owner, dates, status, priority, and assignment
2. WHEN a user views bug details, THE Bug_Tracker_System SHALL display the project name associated with the bug
3. WHEN a user views bug details, THE Bug_Tracker_System SHALL display the assigned user's name if assignedToId is not NULL
4. WHEN a user views bug details, THE Bug_Tracker_System SHALL display human-readable status and priority labels
5. WHEN a bug is closed, THE Bug_Tracker_System SHALL display dateClosed and fixDescription in the detail view

### Requirement 15: Code Structure and Reusability

**User Story:** As a developer, I want code organized into classes and functions, so that the application is maintainable and extensible.

#### Acceptance Criteria

1. THE Bug_Tracker_System SHALL implement all database operations in class methods
2. THE Bug_Tracker_System SHALL implement all business logic in class methods or functions
3. THE Bug_Tracker_System SHALL avoid duplicating code across controllers and views
4. THE Bug_Tracker_System SHALL use the existing class structure (Auth, Bug, Database, Project, Template, User)
5. WHEN new functionality is added, THE Bug_Tracker_System SHALL extend existing classes or create new classes rather than using procedural code

### Requirement 16: Database Schema Compliance

**User Story:** As a developer, I want the application to work with the existing database schema, so that I don't need to modify the database structure.

#### Acceptance Criteria

1. THE Bug_Tracker_System SHALL use the existing database tables without modification
2. THE Bug_Tracker_System SHALL use the existing table column names and data types
3. THE Bug_Tracker_System SHALL respect all foreign key relationships defined in the database
4. THE Bug_Tracker_System SHALL handle NULL values appropriately for nullable columns
5. THE Bug_Tracker_System SHALL use the existing status, priority, and role lookup tables
