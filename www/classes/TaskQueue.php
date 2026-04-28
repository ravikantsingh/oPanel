<?php
// /opt/panel/www/classes/TaskQueue.php

require_once __DIR__ . '/Database.php';

class TaskQueue {
    private $db;

    public function __construct() {
        // Grab the PDO connection from our Singleton
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Dispatch a new task to the background worker.
     * * @param string $action The command to run (e.g., 'create_vhost')
     * @param array $payload The data required (e.g., domain name, php version)
     * @return int The ID of the newly created task
     */
    public function dispatch($action, $payload = []) {
        $sql = "INSERT INTO tasks_queue (action, payload, status) VALUES (:action, :payload, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':action'  => $action,
            ':payload' => json_encode($payload) // Convert array to JSON string for the database
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Check the status of a specific task (Used for frontend loading spinners)
     */
    public function checkStatus($taskId) {
        $sql = "SELECT status, output_log FROM tasks_queue WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $taskId]);
        
        return $stmt->fetch();
    }
}