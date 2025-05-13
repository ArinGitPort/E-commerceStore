<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
    header("Location: ../pages/login.php");
    exit;
}

// Database credentials from your connection file
// Note: These should be extracted from your db_connection.php in a real implementation
$DB_HOST = 'localhost'; // Default for local development
$DB_USER = 'root';      // Default MySQL username for localhost
$DB_PASS = '1234';          // Empty password for localhost
$DB_NAME = 'bunnishop'; // Your local database name

/**
 * Create database backup
 * 
 * @return array Array containing status and message
 */
function backup_database() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    
    // Set execution time limit
    set_time_limit(300); // 5 minutes should be enough for most databases
    
    // Get timestamp for the filename
    $date = date("Y-m-d-H-i-s");
    $backup_file = "../backups/{$DB_NAME}-backup-{$date}.sql";
    
    // Create backups directory if it doesn't exist
    if (!file_exists("../backups")) {
        mkdir("../backups", 0777, true);
    }
    
    // Command for mysqldump
    // Note: For production, use more secure methods to handle credentials
    $command = "mysqldump --opt --host={$DB_HOST} --user={$DB_USER} " . 
              ($DB_PASS ? "--password={$DB_PASS} " : "") . 
              "--databases {$DB_NAME} > {$backup_file}";
    
    // Execute command
    try {
        // For Windows environments - Using XAMPP default MySQL location
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
                if (file_exists($path . "mysqldump.exe")) {
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
        
        // Check if backup was successful
        if ($return_var === 0 && file_exists($backup_file)) {
            // Record backup operation in audit log
            global $pdo;
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                    VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                ");
                $stmt->execute([
                    'user_id'       => $_SESSION['user_id'],
                    'action'        => 'Database backup created',
                    'table_name'    => 'system',
                    'record_id'     => 0,
                    'action_type'   => 'SYSTEM',
                    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'affected_data' => json_encode(['file' => $backup_file, 'time' => date('Y-m-d H:i:s')])
                ]);
            } catch (Exception $e) {
                error_log("Failed to log backup operation: " . $e->getMessage());
            }
            
            return [
                'status' => 'success',
                'message' => 'Database backup was created successfully!',
                'file' => basename($backup_file)
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to create backup: Command execution returned error code ' . $return_var
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Exception during backup: ' . $e->getMessage()
        ];
    }
}

// Alternative method using PHP to export database (if mysqldump is not available)
function php_backup_database() {
    global $pdo, $DB_NAME;
    
    // Get timestamp for the filename
    $date = date("Y-m-d-H-i-s");
    $backup_file = "../backups/{$DB_NAME}-phpbackup-{$date}.sql";
    
    // Create backups directory if it doesn't exist
    if (!file_exists("../backups")) {
        mkdir("../backups", 0777, true);
    }
    
    try {
        // Get all tables
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Open backup file
        $handle = fopen($backup_file, 'w');
        
        // Add header/metadata
        fwrite($handle, "-- Database Backup for {$DB_NAME}\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        
        // Process each table
        foreach ($tables as $table) {
            // Get create table syntax
            $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $create_table_sql = $row[1] . ";";
            fwrite($handle, "-- Table structure for table `$table`\n\n");
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n\n");
            fwrite($handle, $create_table_sql . "\n\n");
            
            // Get table data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                fwrite($handle, "-- Dumping data for table `$table`\n");
                
                // Start INSERT statement
                $insert_sql = "INSERT INTO `$table` VALUES ";
                $values = [];
                
                // Process each row
                foreach ($rows as $row) {
                    $row_values = [];
                    foreach ($row as $value) {
                        // Handle NULL values and escape strings
                        if ($value === null) {
                            $row_values[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $row_values[] = $value;
                        } else {
                            // Replace simple addslashes with proper PDO escaping
                            $escaped_value = $pdo->quote($value);
                            // Remove the quotes added by PDO::quote as we add our own
                            $escaped_value = substr($escaped_value, 1, -1);
                            $row_values[] = "'" . $escaped_value . "'";
                        }
                    }
                    $values[] = "(" . implode(", ", $row_values) . ")";
                }
                
                // Complete INSERT statement with all rows
                $insert_sql .= implode(",\n", $values) . ";\n\n";
                fwrite($handle, $insert_sql);
            }
        }
        
        // Add footer
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
        
        // Record backup operation in audit log
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
            ");
            $stmt->execute([
                'user_id'       => $_SESSION['user_id'],
                'action'        => 'Database PHP backup created',
                'table_name'    => 'system',
                'record_id'     => 0,
                'action_type'   => 'SYSTEM',
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'affected_data' => json_encode(['file' => $backup_file, 'time' => date('Y-m-d H:i:s')])
            ]);
        } catch (Exception $e) {
            error_log("Failed to log backup operation: " . $e->getMessage());
        }
        
        return [
            'status' => 'success',
            'message' => 'Database PHP backup was created successfully!',
            'file' => basename($backup_file)
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Exception during PHP backup: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $response = array();
    
    switch ($_GET['action']) {
        case 'backup':
            // For localhost development, prefer the PHP method directly
            // This avoids the empty files from failed mysqldump commands
            $is_localhost = ($_SERVER['SERVER_NAME'] === 'localhost' || 
                           $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
                           stripos($_SERVER['HTTP_HOST'], 'localhost') !== false);
            
            if ($is_localhost) {
                // Skip the mysqldump attempt and go straight to PHP method
                error_log("Using PHP backup method for localhost");
                $response = php_backup_database();
            } else {
                // On production, try mysqldump first
                $response = backup_database();
                
                // Check if the file was created but is empty (failed silently)
                if ($response['status'] === 'success') {
                    $backup_file = "../backups/" . $response['file'];
                    if (filesize($backup_file) === 0) {
                        // Delete the empty file
                        unlink($backup_file);
                        error_log("Deleted empty backup file: " . $backup_file);
                        
                        // Try PHP method instead
                        $response = php_backup_database();
                    }
                } else {
                    // If failed explicitly, try PHP method
                    $response = php_backup_database();
                }
            }
            break;
            
        case 'list_backups':
            $backups = array();
            if (file_exists('../backups')) {
                $files = scandir('../backups');
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && strpos($file, '.sql') !== false) {
                        $full_path = "../backups/{$file}";
                        
                        // Skip or delete empty backup files
                        if (filesize($full_path) === 0) {
                            // Delete empty files to clean up
                            unlink($full_path);
                            error_log("Deleted empty backup file during listing: {$file}");
                            continue;
                        }
                        
                        $backups[] = array(
                            'filename' => $file,
                            'size' => round(filesize($full_path) / 1024, 2), // Size in KB
                            'date' => date("Y-m-d H:i:s", filemtime($full_path))
                        );
                    }
                }
                // Sort by date (newest first)
                usort($backups, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
            }
            $response = array(
                'status' => 'success',
                'backups' => $backups
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