<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Check if user is admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
    header("Location: ../pages/login.php");
    exit;
}

// Use database credentials from db_connection.php
$DB_HOST = $host;
$DB_USER = $user;
$DB_PASS = $pass;
$DB_NAME = $db;

// Check if running on InfinityFree hosting
$is_infinity_free = (strpos($DB_HOST, 'infinityfree.com') !== false);

/**
 * Restore database from a SQL backup file
 * 
 * @param string $backup_file Path to the backup file
 * @return array Array containing status and message
 */
function restore_database($backup_file) {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $pdo, $is_infinity_free;
    
    // Check if file exists
    if (!file_exists($backup_file)) {
        return [
            'status' => 'error',
            'message' => 'Backup file does not exist'
        ];
    }
    
    // For InfinityFree hosting, immediately use PHP method instead of mysql commands
    if ($is_infinity_free) {
        error_log("InfinityFree detected - using PHP restore method directly");
        return php_restore_database($backup_file);
    }
    
    // Set execution time limit
    set_time_limit(300); // 5 minutes should be enough for most databases
    
    // Command to drop and recreate the database
    $drop_create_command = "mysql --host={$DB_HOST} --user={$DB_USER} " . 
                         ($DB_PASS ? "--password={$DB_PASS} " : "") . 
                         "-e \"DROP DATABASE IF EXISTS {$DB_NAME}; CREATE DATABASE {$DB_NAME};\"";
    
    // Command for mysql restore
    $restore_command = "mysql --host={$DB_HOST} --user={$DB_USER} " . 
                     ($DB_PASS ? "--password={$DB_PASS} " : "") . 
                     "{$DB_NAME} < {$backup_file}";
    
    // Execute commands
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
                    // First drop and create the database
                    $full_drop_create = $path . $drop_create_command;
                    exec($full_drop_create, $output1, $return_var1);
                    
                    if ($return_var1 !== 0) {
                        error_log("Failed to reset database. Error code: " . $return_var1);
                        return [
                            'status' => 'error',
                            'message' => 'Failed to reset database: Command execution returned error code ' . $return_var1
                        ];
                    }
                    
                    // Then restore from backup
                    $full_restore = $path . $restore_command;
                    exec($full_restore, $output2, $return_var2);
                    $command_executed = true;
                    break;
                }
            }
            
            // If no path worked, try without a path (rely on system PATH)
            if (!$command_executed) {
                // First drop and create the database
                exec($drop_create_command, $output1, $return_var1);
                
                if ($return_var1 !== 0) {
                    error_log("Failed to reset database. Error code: " . $return_var1);
                    return [
                        'status' => 'error',
                        'message' => 'Failed to reset database: Command execution returned error code ' . $return_var1
                    ];
                }
                
                // Then restore from backup
                exec($restore_command, $output2, $return_var2);
            }
        } 
        // For Linux/Unix/MacOS
        else {
            // First drop and create the database
            exec($drop_create_command, $output1, $return_var1);
            
            if ($return_var1 !== 0) {
                error_log("Failed to reset database. Error code: " . $return_var1);
                return [
                    'status' => 'error',
                    'message' => 'Failed to reset database: Command execution returned error code ' . $return_var1
                ];
            }
            
            // Then restore from backup
            exec($restore_command, $output2, $return_var2);
        }
        
        // Check if restore was successful
        if ($return_var2 === 0) {
            // Record restore operation in audit log
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                    VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                ");
                $stmt->execute([
                    'user_id'       => $_SESSION['user_id'] ?? 0,
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
            error_log("Failed to restore database. Error code: " . $return_var2);
            return [
                'status' => 'error',
                'message' => 'Failed to restore database: Command execution returned error code ' . $return_var2
            ];
        }
    } catch (Exception $e) {
        error_log("Exception during command-line restore: " . $e->getMessage());
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
        error_log("Starting PHP restore process from: " . basename($backup_file));
        
        // Read SQL file
        $sql = file_get_contents($backup_file);
        if ($sql === false) {
            error_log("Failed to read backup file contents");
            return [
                'status' => 'error',
                'message' => 'Failed to read backup file'
            ];
        }
        
        error_log("Backup file read successfully, size: " . strlen($sql) . " bytes");
        
        // Split SQL file into statements
        $queries = parse_sql_file($sql);
        error_log("Parsed " . count($queries) . " SQL queries from backup file");
        
        // Start transaction
        $pdo->exec('START TRANSACTION');
        error_log("Transaction started");
        
        // Disable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        
        // First, get all existing tables
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        error_log("Found " . count($tables) . " existing tables to drop");
        
        // Drop all existing tables to ensure clean restore
        foreach ($tables as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                error_log("Dropped table: $table");
            } catch (Exception $e) {
                error_log("Error dropping table $table: " . $e->getMessage());
                // Continue with other tables
            }
        }
        
        // Now execute each query from the backup file
        $executed = 0;
        $errors = 0;
        
        foreach ($queries as $i => $query) {
            if (!empty(trim($query))) {
                try {
                    $pdo->exec($query);
                    $executed++;
                    
                    // Log progress for larger restores
                    if ($executed % 50 == 0) {
                        error_log("Executed $executed queries...");
                    }
                } catch (Exception $e) {
                    $errors++;
                    error_log("Error executing query #$i: " . $e->getMessage());
                    error_log("Problematic query: " . substr($query, 0, 100) . "...");
                    // Continue with next query
                }
            }
        }
        
        error_log("Executed $executed queries with $errors errors");
        
        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        // Commit transaction
        $pdo->exec('COMMIT');
        error_log("Transaction committed");
        
        // Record restore operation in audit log
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
            ");
            $stmt->execute([
                'user_id'       => $_SESSION['user_id'] ?? 0,
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
            'message' => 'Database was restored successfully'
        ];
    } catch (Exception $e) {
        // Rollback if there's an error
        try {
            $pdo->exec('ROLLBACK');
            error_log("Transaction rolled back due to error");
        } catch (Exception $rollbackEx) {
            error_log("Failed to rollback transaction: " . $rollbackEx->getMessage());
        }
        
        error_log("Critical exception during PHP restore: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Exception during PHP restore: ' . $e->getMessage()
        ];
    }
}

/**
 * Process an uploaded SQL file for restore
 * 
 * @return array Array containing status and message
 */
function process_upload() {
    global $pdo, $is_infinity_free;
    
    // Check if a file was uploaded
    if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] != UPLOAD_ERR_OK) {
        $error = isset($_FILES['sql_file']) ? $_FILES['sql_file']['error'] : 'No file uploaded';
        error_log("SQL upload error: " . $error);
        return [
            'status' => 'error',
            'message' => 'Upload failed: ' . get_upload_error_message($error)
        ];
    }
    
    // Validate file type
    $file_info = pathinfo($_FILES['sql_file']['name']);
    if (strtolower($file_info['extension']) !== 'sql') {
        return [
            'status' => 'error',
            'message' => 'Only SQL files are allowed'
        ];
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = "../uploads";
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return [
                'status' => 'error',
                'message' => 'Failed to create upload directory'
            ];
        }
    }
    
    // Generate a unique filename
    $timestamp = date('Y-m-d-H-i-s');
    $target_file = $upload_dir . '/imported-' . $timestamp . '-' . basename($_FILES['sql_file']['name']);
    
    // Move uploaded file to target location
    if (move_uploaded_file($_FILES['sql_file']['tmp_name'], $target_file)) {
        error_log("File uploaded successfully to: " . $target_file);
        
        // On InfinityFree, use PHP method directly
        if ($is_infinity_free) {
            $response = php_restore_database($target_file);
        } else {
            // Try mysql method first on other hosts
            $response = restore_database($target_file);
            
            // If failed, try PHP method
            if ($response['status'] === 'error') {
                error_log("MySQL restore failed: " . $response['message'] . ". Trying PHP method.");
                $response = php_restore_database($target_file);
            }
        }
        
        // Copy the file to backups folder if import was successful
        if ($response['status'] === 'success') {
            // Create backups directory if it doesn't exist
            $backup_dir = "../backups";
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0777, true);
            }
            
            $backup_filename = $backup_dir . '/imported-' . $timestamp . '-' . basename($_FILES['sql_file']['name']);
            copy($target_file, $backup_filename);
            error_log("Imported SQL file copied to backups folder: " . $backup_filename);
        }
        
        // Delete the file from uploads regardless of result
        unlink($target_file);
        
        return $response;
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to move uploaded file'
        ];
    }
}

/**
 * Get human-readable error message for upload errors
 */
function get_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
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
    $in_comment = false;
    $escaped = false;
    
    // Process the SQL character by character
    for ($i = 0; $i < strlen($sql); $i++) {
        $current = $sql[$i];
        $next = ($i < strlen($sql) - 1) ? $sql[$i+1] : '';
        
        // Handle comments
        if (!$in_string && $current == '-' && $next == '-') {
            $in_comment = true;
        }
        
        if ($in_comment && $current == "\n") {
            $in_comment = false;
        }
        
        // Skip processing if we're in a comment
        if ($in_comment) {
            $buffer .= $current;
            continue;
        }
        
        // Handle string escaping
        if ($in_string && $current == '\\') {
            $escaped = !$escaped;
        } else if ($in_string && $current == "'" && !$escaped) {
            $in_string = false;
        } else if (!$in_string && $current == "'") {
            $in_string = true;
            $escaped = false;
        } else {
            $escaped = false;
        }
        
        // If we're outside a string and see a semicolon, end the query
        if (!$in_string && $current == ';') {
            $queries[] = $buffer . ';';
            $buffer = '';
        } else {
            $buffer .= $current;
        }
    }
    
    // Add the last query if there is one
    if (!empty(trim($buffer))) {
        $queries[] = $buffer;
    }
    
    return $queries;
}

// Handle AJAX requests
if (isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    // Check if it's a POST request for file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
        $response = process_upload();
    } 
    // Otherwise handle GET requests
    else if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'restore':
                if (isset($_GET['file'])) {
                    $backup_file = "../backups/" . basename($_GET['file']); // Prevent path traversal
                    
                    // On InfinityFree, use PHP method directly
                    if ($is_infinity_free) {
                        $response = php_restore_database($backup_file);
                    } else {
                        // Try mysql method first on other hosts
                        $response = restore_database($backup_file);
                        
                        // If failed, try PHP method
                        if ($response['status'] === 'error') {
                            error_log("MySQL restore failed: " . $response['message'] . ". Trying PHP method.");
                            $response = php_restore_database($backup_file);
                        }
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
                                'user_id'       => $_SESSION['user_id'] ?? 0,
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
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 