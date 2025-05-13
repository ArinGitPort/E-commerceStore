<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
    header("Location: ../pages/login.php");
    exit;
}

// Database credentials (these should match your db_connection.php)
$DB_HOST = 'localhost'; // Default for local development
$DB_USER = 'root';      // Default MySQL username for localhost
$DB_PASS = '1234';          // Empty password for localhost
$DB_NAME = 'bunnishop'; // Your local database name

/**
 * Restore database from a SQL backup file
 * 
 * @param string $backup_file Path to the backup file
 * @return array Array containing status and message
 */
function restore_database($backup_file) {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $pdo;
    
    // Check if file exists
    if (!file_exists($backup_file)) {
        return [
            'status' => 'error',
            'message' => 'Backup file does not exist'
        ];
    }
    
    // Set execution time limit
    set_time_limit(300); // 5 minutes should be enough for most databases
    
    // Command for mysql restore
    $command = "mysql --host={$DB_HOST} --user={$DB_USER} " . 
              ($DB_PASS ? "--password={$DB_PASS} " : "") . 
              "{$DB_NAME} < {$backup_file}";
    
    // Execute command
    try {
        // For Windows environments
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Check for common MySQL installation paths on Windows
            $possible_paths = [
                "C:\\xampp\\mysql\\bin\\",
                "C:\\wamp64\\bin\\mysql\\mysql5.7.36\\bin\\",
                "C:\\wamp\\bin\\mysql\\mysql5.7.36\\bin\\",
                "C:\\laragon\\bin\\mysql\\mysql-5.7.33-winx64\\bin\\"
            ];
            
            $command_executed = false;
            foreach ($possible_paths as $path) {
                if (file_exists($path . "mysql.exe")) {
                    $full_command = $path . $command;
                    exec($full_command, $output, $return_var);
                    $command_executed = true;
                    break;
                }
            }
            
            // If no path worked, try without a path (rely on system PATH)
            if (!$command_executed) {
                exec($command, $output, $return_var);
            }
        } 
        // For Linux/Unix/MacOS
        else {
            exec($command, $output, $return_var);
        }
        
        // Check if restore was successful
        if ($return_var === 0) {
            // Record restore operation in audit log
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                    VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                ");
                $stmt->execute([
                    'user_id'       => $_SESSION['user_id'],
                    'action'        => 'Database restored',
                    'table_name'    => 'system',
                    'record_id'     => 0,
                    'action_type'   => 'SYSTEM',
                    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'affected_data' => json_encode(['file' => basename($backup_file), 'time' => date('Y-m-d H:i:s')])
                ]);
            } catch (Exception $e) {
                error_log("Failed to log restore operation: " . $e->getMessage());
            }
            
            return [
                'status' => 'success',
                'message' => 'Database was restored successfully!'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to restore database: Command execution returned error code ' . $return_var
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Exception during restore: ' . $e->getMessage()
        ];
    }
}

/**
 * Alternative method to restore database using PHP
 * This is more complex than using mysql command but works when shell_exec is disabled
 */
function php_restore_database($backup_file) {
    global $pdo;
    
    // Check if file exists
    if (!file_exists($backup_file)) {
        return [
            'status' => 'error',
            'message' => 'Backup file does not exist'
        ];
    }
    
    try {
        // Read SQL file
        $sql = file_get_contents($backup_file);
        
        // Split SQL file into statements
        $queries = parse_sql_file($sql);
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Disable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        
        // Execute each statement
        foreach ($queries as $query) {
            if (!empty(trim($query))) {
                $pdo->exec($query);
            }
        }
        
        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        // Commit
        $pdo->commit();
        
        // Record restore operation in audit log
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
            ");
            $stmt->execute([
                'user_id'       => $_SESSION['user_id'],
                'action'        => 'Database PHP restored',
                'table_name'    => 'system',
                'record_id'     => 0,
                'action_type'   => 'SYSTEM',
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'affected_data' => json_encode(['file' => basename($backup_file), 'time' => date('Y-m-d H:i:s')])
            ]);
        } catch (Exception $e) {
            error_log("Failed to log restore operation: " . $e->getMessage());
        }
        
        return [
            'status' => 'success',
            'message' => 'Database was restored successfully using PHP method!'
        ];
    } catch (Exception $e) {
        // Rollback if there's an error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        return [
            'status' => 'error',
            'message' => 'Exception during PHP restore: ' . $e->getMessage()
        ];
    }
}

/**
 * Helper function to split SQL file into individual queries
 */
function parse_sql_file($sql) {
    $sql = trim($sql);
    $sql = preg_replace('/\n\r|\r\n|\n|\r/m', "\n", $sql);
    
    $buffer = '';
    $queries = [];
    $in_string = false;
    
    for ($i = 0; $i < strlen($sql) - 1; $i++) {
        if ($sql[$i] == "'" && $sql[$i+1] != "\\") {
            $in_string = !$in_string;
        }
        
        if ($in_string || ($sql[$i] != ';')) {
            $buffer .= $sql[$i];
        } else {
            $queries[] = $buffer . ';';
            $buffer = '';
        }
    }
    
    if (!empty($buffer)) {
        $queries[] = $buffer;
    }
    
    return $queries;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $response = array();
    
    switch ($_GET['action']) {
        case 'restore':
            if (isset($_GET['file'])) {
                $backup_file = "../backups/" . basename($_GET['file']); // Prevent path traversal
                
                // Try mysql method first
                $response = restore_database($backup_file);
                
                // If failed, try PHP method
                if ($response['status'] === 'error') {
                    $response = php_restore_database($backup_file);
                }
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'No backup file specified'
                );
            }
            break;
            
        case 'delete_backup':
            if (isset($_GET['file'])) {
                $backup_file = "../backups/" . basename($_GET['file']); // Prevent path traversal
                
                if (file_exists($backup_file) && unlink($backup_file)) {
                    // Record backup deletion in audit log
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                            VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                        ");
                        $stmt->execute([
                            'user_id'       => $_SESSION['user_id'],
                            'action'        => 'Backup file deleted',
                            'table_name'    => 'system',
                            'record_id'     => 0,
                            'action_type'   => 'DELETE',
                            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                            'affected_data' => json_encode(['file' => basename($backup_file), 'time' => date('Y-m-d H:i:s')])
                        ]);
                    } catch (Exception $e) {
                        error_log("Failed to log backup deletion: " . $e->getMessage());
                    }
                    
                    $response = array(
                        'status' => 'success',
                        'message' => 'Backup file was deleted successfully!'
                    );
                } else {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Failed to delete backup file'
                    );
                }
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'No backup file specified'
                );
            }
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