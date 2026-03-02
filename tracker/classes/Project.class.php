<?php
require_once "Database.class.php";

class Project {
    private $db;
    public function __construct() {
        $this->db = new Database();
    }

    // Get all projects
    public function getAllProjects() {
        $sql = "SELECT * FROM project";
        return $this->db->query($sql);
    }

    // Create new project
    public function createProject($name) {
        $sql = "INSERT INTO project (Project) VALUES (?)";
        return $this->db->execute($sql, [$name]);
    }
}