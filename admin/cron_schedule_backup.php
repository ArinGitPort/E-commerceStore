<?php
// Initialize session if not already started (for authentication)
if(!isset($_SESSION)) {
    session_start();
}

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// For local development, allow execution from browser without key
$is_localhost = ($_SERVER['SERVER_NAME'] === 'localhost' || 
                 $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
                 stripos($_SERVER['HTTP_HOST'], 'localhost') !== false);

$running_from_cli = (php_sapi_name() == 'cli');

// Only restrict access if not on localhost
if (!$running_from_cli && !$is_localhost) {
    // Use a simple key for testing - in production, use a stronger key
    $cron_key = "localdev";
    $valid_web_request = isset($_GET['cron_key']) && $_GET['cron_key'] === $cron_key;
    
    if (!$valid_web_request) {
        header("HTTP/1.0 403 Forbidden");
        exit("Access Denied. For localhost testing, use http://localhost/path/admin/cron_schedule_backup.php");
    }
}

// Set the absolute path to your website root - handle both CLI and web context
$site_root = dirname(__DIR__);

// Load the necessary files - ensure we include full paths
require_once $site_root . '/config/db_connection.php';
// Explicitly set variables for db_backup.php to use
$DB_HOST = $host;
$DB_USER = $user;
$DB_PASS = $pass;
$DB_NAME = $db;

// Increase limits for backup process
set_time_limit(300);
ini_set('memory_limit', '256M');

// Include the backup functionality with full path
require_once $site_root . '/admin/db_backup.php';

// Schedule configuration table
$schedule_table = 'backup_schedules';

// Ensure backup directory exists
if (!file_exists($site_root . '/backups')) {
    mkdir($site_root . '/backups', 0777, true);
}

try {
    // Get active schedules
    $stmt = $pdo->query("
        SELECT * FROM {$schedule_table} 
        WHERE is_active = 1
    ");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no schedules found, this might be a database connection issue
    if (empty($schedules) && !$stmt) {
        throw new Exception("Database connection failed or no schedules found");
    }
    
    $current_time = time();
    $current_day = date('N', $current_time) % 7; // 0 (Sunday) to 6 (Saturday)
    $current_day_of_month = date('j', $current_time); // 1 to 31
    $current_hour = date('G', $current_time); // 0 to 23
    $current_minute = date('i', $current_time); // 0 to 59
    
    $backups_run = 0;
    $log_messages = [];
    
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
            $log_messages[] = "Running schedule ID {$schedule['id']} ({$schedule['frequency']})";
            
            // Try mysqldump method first
            $result = backup_database();
            
            // If failed, try PHP method
            if ($result['status'] === 'error') {
                $log_messages[] = "MySQL dump failed, trying PHP backup method";
                $result = php_backup_database();
            }
            
            if ($result['status'] === 'success') {
                // Update last_run timestamp
                $pdo->prepare("
                    UPDATE {$schedule_table} 
                    SET last_run = NOW() 
                    WHERE id = :id
                ")->execute(['id' => $schedule['id']]);
                
                $log_messages[] = "Backup successful: {$result['file']}";
                
                // Clean up old backups based on retention policy
                cleanup_old_backups($schedule['retention_days']);
                $log_messages[] = "Cleaned up backups older than {$schedule['retention_days']} days";
                
                $backups_run++;
            } else {
                $log_messages[] = "Backup failed: {$result['message']}";
            }
        }
    }
    
    $log_output = "=== Backup Cron Job: " . date('Y-m-d H:i:s') . " ===\n";
    $log_output .= "Schedules checked: " . count($schedules) . "\n";
    $log_output .= "Backups executed: {$backups_run}\n";
    
    if (count($log_messages) > 0) {
        $log_output .= "Details:\n - " . implode("\n - ", $log_messages) . "\n";
    }
    
    $log_output .= "=======================================\n";
    
    // Write to log file
    file_put_contents($site_root . '/backups/backup_cron.log', $log_output, FILE_APPEND);
    
    // If running from CLI, output the log
    if ($running_from_cli) {
        echo $log_output;
    }
    
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage() . "\n";
    file_put_contents($site_root . '/backups/backup_cron_error.log', date('Y-m-d H:i:s') . ": " . $error_message, FILE_APPEND);
    
    if ($running_from_cli) {
        echo $error_message;
    }
}

// A helper function to clean up old backups (copied from db_backup_schedule.php)
function cleanup_old_backups($retention_days = 30) {
    global $site_root;
    
    // Calculate cut-off date
    $cutoff_date = strtotime("-{$retention_days} days");
    
    // Get all backup files
    $backup_dir = $site_root . "/backups";
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && strpos($file, '.sql') !== false) {
                $file_path = "{$backup_dir}/{$file}";
                
                // Check if file is older than retention period
                if (filemtime($file_path) < $cutoff_date) {
                    if (unlink($file_path)) {
                        // Successfully deleted
                    }
                }
            }
        }
    }
}


/**
 * Cron job script for running scheduled database backups (Localhost Version)
 * 
 * For localhost development:
 * 1. You can run this script directly through the browser by visiting:
 *    http://localhost/your-path/admin/cron_schedule_backup.php
 * 
 * 2. No security key is required for localhost testing
 *
 * For InfinityFree hosting:
 * 1. Access via: https://your-site.com/admin/cron_schedule_backup.php?cron_key=localdev
 */

?> 