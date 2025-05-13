<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
    header("Location: ../pages/login.php");
    exit;
}

// Database credentials
$DB_HOST = 'localhost'; // Default for local development
$DB_USER = 'root';      // Default MySQL username for localhost
$DB_PASS = '1234';      // MySQL password for localhost
$DB_NAME = 'bunnishop'; // Your local database name

// Schedule configuration table
$schedule_table = 'backup_schedules';

// Create the schedule table if it doesn't exist
try {
    // Check if table exists first
    $tableExists = false;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE '{$schedule_table}'");
        $tableExists = $checkTable->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error checking if table exists: " . $e->getMessage());
        // Continue with creation attempt
    }
    
    if (!$tableExists) {
        // Table doesn't exist, create it
        error_log("Creating backup_schedules table");
        
        $createTableSQL = "
            CREATE TABLE {$schedule_table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
                day_of_week INT NULL, -- 0 (Sunday) to 6 (Saturday), NULL for daily
                day_of_month INT NULL, -- 1 to 31, NULL for daily/weekly
                hour INT NOT NULL DEFAULT 0,
                minute INT NOT NULL DEFAULT 0,
                retention_days INT NOT NULL DEFAULT 30,
                is_active BOOLEAN NOT NULL DEFAULT 1,
                created_by INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_run DATETIME NULL,
                FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
            )
        ";
        
        $pdo->exec($createTableSQL);
        error_log("Successfully created backup_schedules table");
    }
} catch (Exception $e) {
    error_log("Error creating schedule table: " . $e->getMessage());
    
    // Try without the foreign key if that was the issue
    try {
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS {$schedule_table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
                day_of_week INT NULL,
                day_of_month INT NULL,
                hour INT NOT NULL DEFAULT 0,
                minute INT NOT NULL DEFAULT 0,
                retention_days INT NOT NULL DEFAULT 30,
                is_active BOOLEAN NOT NULL DEFAULT 1,
                created_by INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_run DATETIME NULL
            )
        ";
        
        $pdo->exec($createTableSQL);
        error_log("Created backup_schedules table without foreign key");
    } catch (Exception $e2) {
        error_log("Critical error creating schedule table: " . $e2->getMessage());
    }
}

/**
 * Get all scheduled backup configurations
 * 
 * @return array List of schedules
 */
function get_schedules() {
    global $pdo, $schedule_table;
    
    try {
        $stmt = $pdo->query("
            SELECT s.*, u.name as creator_name
            FROM {$schedule_table} s
            JOIN users u ON s.created_by = u.user_id
            ORDER BY s.is_active DESC, s.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching schedules: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a new backup schedule
 * 
 * @param array $schedule Schedule details
 * @return array Operation result
 */
function add_schedule($schedule) {
    global $pdo, $schedule_table;
    
    // Debug logging
    error_log("Adding schedule: " . json_encode($schedule));
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log("No user_id in session");
        // For localhost testing, use a default admin user ID
        $user_id = 1; // Assuming ID 1 is admin
        error_log("Using default user_id: $user_id for localhost testing");
    } else {
        $user_id = $_SESSION['user_id'];
        error_log("Using user_id from session: $user_id");
    }
    
    try {
        // Prepare the insert statement
        $query = "
            INSERT INTO {$schedule_table} (
                frequency, day_of_week, day_of_month, hour, minute, 
                retention_days, is_active, created_by
            ) VALUES (
                :frequency, :day_of_week, :day_of_month, :hour, :minute,
                :retention_days, :is_active, :created_by
            )
        ";
        
        error_log("SQL Query: $query");
        
        $stmt = $pdo->prepare($query);
        
        // Bind all parameters
        $params = [
            'frequency' => $schedule['frequency'],
            'day_of_week' => $schedule['frequency'] == 'weekly' ? $schedule['day_of_week'] : null,
            'day_of_month' => $schedule['frequency'] == 'monthly' ? $schedule['day_of_month'] : null,
            'hour' => $schedule['hour'],
            'minute' => $schedule['minute'],
            'retention_days' => $schedule['retention_days'] ?? 30,
            'is_active' => $schedule['is_active'] ?? 1,
            'created_by' => $user_id
        ];
        
        error_log("Parameters: " . json_encode($params));
        
        // Execute the query
        $success = $stmt->execute($params);
        
        if (!$success) {
            error_log("SQL Error: " . json_encode($stmt->errorInfo()));
            return [
                'status' => 'error',
                'message' => 'SQL execution failed: ' . $stmt->errorInfo()[2]
            ];
        }
        
        $schedule_id = $pdo->lastInsertId();
        error_log("Schedule created with ID: $schedule_id");
        
        // Log schedule creation to audit_logs if table exists
        try {
            $checkAuditTable = $pdo->query("SHOW TABLES LIKE 'audit_logs'");
            if ($checkAuditTable->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                    VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                ");
                $stmt->execute([
                    'user_id'       => $user_id,
                    'action'        => 'Backup schedule created',
                    'table_name'    => $schedule_table,
                    'record_id'     => $schedule_id,
                    'action_type'   => 'CREATE',
                    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'affected_data' => json_encode($schedule)
                ]);
                error_log("Audit log entry created");
            }
        } catch (Exception $e) {
            error_log("Failed to log schedule creation: " . $e->getMessage());
            // Don't fail the whole operation for logging error
        }
        
        return [
            'status' => 'success',
            'message' => 'Schedule was added successfully!',
            'schedule_id' => $schedule_id
        ];
    } catch (Exception $e) {
        error_log("Exception in add_schedule: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to add schedule: ' . $e->getMessage()
        ];
    }
}

/**
 * Delete a backup schedule
 * 
 * @param int $schedule_id Schedule ID
 * @return array Operation result
 */
function delete_schedule($schedule_id) {
    global $pdo, $schedule_table;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM {$schedule_table} WHERE id = :id");
        $stmt->execute(['id' => $schedule_id]);
        
        if ($stmt->rowCount() > 0) {
            // Log schedule deletion
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                    VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                ");
                $stmt->execute([
                    'user_id'       => $_SESSION['user_id'],
                    'action'        => 'Backup schedule deleted',
                    'table_name'    => $schedule_table,
                    'record_id'     => $schedule_id,
                    'action_type'   => 'DELETE',
                    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'affected_data' => json_encode(['schedule_id' => $schedule_id])
                ]);
            } catch (Exception $e) {
                error_log("Failed to log schedule deletion: " . $e->getMessage());
            }
            
            return [
                'status' => 'success',
                'message' => 'Schedule was deleted successfully!'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Schedule not found'
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Failed to delete schedule: ' . $e->getMessage()
        ];
    }
}

/**
 * Toggle a backup schedule active/inactive
 * 
 * @param int $schedule_id Schedule ID
 * @return array Operation result
 */
function toggle_schedule($schedule_id) {
    global $pdo, $schedule_table;
    
    try {
        // First get current status
        $stmt = $pdo->prepare("SELECT is_active FROM {$schedule_table} WHERE id = :id");
        $stmt->execute(['id' => $schedule_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule) {
            $new_status = $schedule['is_active'] ? 0 : 1;
            
            // Update status
            $stmt = $pdo->prepare("UPDATE {$schedule_table} SET is_active = :status WHERE id = :id");
            $stmt->execute([
                'status' => $new_status,
                'id' => $schedule_id
            ]);
            
            // Log status change
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                    VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                ");
                $stmt->execute([
                    'user_id'       => $_SESSION['user_id'],
                    'action'        => $new_status ? 'Backup schedule activated' : 'Backup schedule deactivated',
                    'table_name'    => $schedule_table,
                    'record_id'     => $schedule_id,
                    'action_type'   => 'UPDATE',
                    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'affected_data' => json_encode(['schedule_id' => $schedule_id, 'new_status' => $new_status])
                ]);
            } catch (Exception $e) {
                error_log("Failed to log schedule status change: " . $e->getMessage());
            }
            
            return [
                'status' => 'success',
                'message' => 'Schedule status was updated successfully!',
                'is_active' => $new_status
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Schedule not found'
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Failed to update schedule: ' . $e->getMessage()
        ];
    }
}

/**
 * Clean up old backups based on retention policy
 * 
 * @param int $retention_days Number of days to keep backups
 * @return void
 */
function cleanup_old_backups($retention_days = 30) {
    // Calculate cut-off date
    $cutoff_date = strtotime("-{$retention_days} days");
    
    // Get all backup files
    $backup_dir = "../backups";
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && strpos($file, '.sql') !== false) {
                $file_path = "{$backup_dir}/{$file}";
                
                // Check if file is older than retention period
                if (filemtime($file_path) < $cutoff_date) {
                    if (unlink($file_path)) {
                        error_log("Deleted old backup: {$file}");
                    } else {
                        error_log("Failed to delete old backup: {$file}");
                    }
                }
            }
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $response = array();
    
    // Determine the action from either GET or POST
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];
    
    // For debugging
    error_log("Processing action: " . $action);
    
    switch ($action) {
        case 'get_schedules':
            $response = array(
                'status' => 'success',
                'schedules' => get_schedules()
            );
            break;
            
        case 'add_schedule':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Log request data for debugging
                error_log("Received schedule data: " . json_encode($_POST));
                
                // Get schedule data from POST
                $schedule = array(
                    'frequency' => $_POST['frequency'] ?? 'daily',
                    'day_of_week' => isset($_POST['day_of_week']) ? (int)$_POST['day_of_week'] : null,
                    'day_of_month' => isset($_POST['day_of_month']) ? (int)$_POST['day_of_month'] : null,
                    'hour' => isset($_POST['hour']) ? (int)$_POST['hour'] : 0,
                    'minute' => isset($_POST['minute']) ? (int)$_POST['minute'] : 0,
                    'retention_days' => isset($_POST['retention_days']) ? (int)$_POST['retention_days'] : 30,
                    'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
                );
                
                try {
                    $response = add_schedule($schedule);
                } catch (Exception $e) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Exception: ' . $e->getMessage()
                    );
                    error_log("Exception in add_schedule: " . $e->getMessage());
                }
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']
                );
            }
            break;
            
        case 'delete_schedule':
            if (isset($_GET['id'])) {
                $response = delete_schedule($_GET['id']);
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'No schedule ID specified'
                );
            }
            break;
            
        case 'toggle_schedule':
            if (isset($_GET['id'])) {
                $response = toggle_schedule($_GET['id']);
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'No schedule ID specified'
                );
            }
            break;
            
        case 'run_scheduled_backups':
            // This action would normally be called by a cron job
            require_once 'db_backup.php'; // Include the backup functionality
            
            // Get active schedules
            $stmt = $pdo->query("
                SELECT * FROM {$schedule_table} 
                WHERE is_active = 1
            ");
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $current_time = time();
            $current_day = date('N', $current_time) % 7; // 0 (Sunday) to 6 (Saturday)
            $current_day_of_month = date('j', $current_time); // 1 to 31
            $current_hour = date('G', $current_time); // 0 to 23
            $current_minute = date('i', $current_time); // 0 to 59
            
            $backups_run = 0;
            
            foreach ($schedules as $schedule) {
                $should_run = false;
                
                // Check if it's time to run based on frequency
                switch ($schedule['frequency']) {
                    case 'daily':
                        // Run if current hour/minute matches schedule
                        $should_run = ($current_hour == $schedule['hour'] && $current_minute == $schedule['minute']);
                        break;
                        
                    case 'weekly':
                        // Run if current day/hour/minute matches schedule
                        $should_run = ($current_day == $schedule['day_of_week'] && 
                                      $current_hour == $schedule['hour'] && 
                                      $current_minute == $schedule['minute']);
                        break;
                        
                    case 'monthly':
                        // Run if current day of month/hour/minute matches schedule
                        $should_run = ($current_day_of_month == $schedule['day_of_month'] && 
                                      $current_hour == $schedule['hour'] && 
                                      $current_minute == $schedule['minute']);
                        break;
                }
                
                // If it's time to run, perform backup
                if ($should_run) {
                    $result = backup_database(); // Using function from db_backup.php
                    
                    if ($result['status'] === 'success') {
                        // Update last_run timestamp
                        $pdo->prepare("
                            UPDATE {$schedule_table} 
                            SET last_run = NOW() 
                            WHERE id = :id
                        ")->execute(['id' => $schedule['id']]);
                        
                        // Clean up old backups based on retention policy
                        cleanup_old_backups($schedule['retention_days']);
                        
                        $backups_run++;
                    }
                }
            }
            
            $response = array(
                'status' => 'success',
                'message' => "Scheduled check completed. {$backups_run} backups executed."
            );
            break;
            
        default:
            $response = array(
                'status' => 'error',
                'message' => 'Invalid action'
            );
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 