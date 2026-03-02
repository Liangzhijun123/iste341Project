<?php
/**
 * Project Class
 * Manages all data operations related to software projects.
 * This class allows Admins and Managers to define the project scope 
 * that bugs are eventually assigned to.
 */
require_once "Database.class.php";

class Project {
    /**
     * @var Database $db The database connection instance.
     */
    private $db;

    /**
     * Constructor initializes the data access layer for projects.
     */
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Retrieves a list of all projects in the system.
     * Used for populating dropdown menus in bug reports and 
     * showing project lists on the dashboard.
     * * @return array An associative array of all project records.
     */
    public function getAllProjects() {
        $sql = "SELECT * FROM project";
        return $this->db->query($sql);
    }

    /**
     * Inserts a new project record into the database.
     * Restricted to Admin and Manager roles within the application logic.
     * * @param string $name The name of the new project to be created.
     * @return bool True if the project was successfully created.
     */
    public function createProject($name) {
        $sql = "INSERT INTO project (Project) VALUES (?)";
        return $this->db->execute($sql, [$name]);
    }
}