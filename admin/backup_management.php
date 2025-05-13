<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Super Admin')) {
    header("Location: ../pages/login.php");
    exit;
}

// Include sidebar if it exists
$sidebar_file = '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup & Restore - Bunniwinkle</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        .backup-card {
            transition: all 0.3s ease;
        }
        
        .backup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: #8e44ad;
            margin-bottom: 1rem;
        }
        
        .backup-item {
            border-left: 3px solid #8e44ad;
            transition: all 0.2s ease;
        }
        
        .backup-item:hover {
            background-color: rgba(142, 68, 173, 0.05);
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .schedule-controls {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .tab-content {
            padding-top: 1.5rem;
        }
        
        .btn-primary {
            background-color: #8e44ad;
            border-color: #8e44ad;
        }
        
        .btn-primary:hover {
            background-color: #7d3c98;
            border-color: #7d3c98;
        }
        
        .btn-outline-primary {
            color: #8e44ad;
            border-color: #8e44ad;
        }
        
        .btn-outline-primary:hover {
            background-color: #8e44ad;
            border-color: #8e44ad;
        }
        
        .nav-pills .nav-link.active {
            background-color: #8e44ad;
        }
        
        .spinner-border {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }
    </style>
</head>

<body class="bg-light">
    <?php if (file_exists($sidebar_file)) include $sidebar_file; ?>
    
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-database me-2"></i> Database Management</h2>
            <div>
                <button id="createBackupBtn" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Create Backup
                </button>
            </div>
        </div>
        
        <!-- Toast container for notifications -->
        <div class="toast-container"></div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card backup-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <h5 class="card-title">Manual Backup</h5>
                        <p class="card-text">Create an on-demand backup of your entire database with a single click.</p>
                        <button id="manualBackupBtn" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i> Backup Now
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card backup-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h5 class="card-title">Scheduled Backups</h5>
                        <p class="card-text">Configure automatic backups on daily, weekly, or monthly schedules.</p>
                        <button id="scheduleBackupBtn" class="btn btn-outline-primary">
                            <i class="fas fa-cog me-2"></i> Configure Schedules
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card backup-card h-100">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h5 class="card-title">Restore Database</h5>
                        <p class="card-text">Restore your database from a previously created backup file.</p>
                        <button id="viewBackupsBtn" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i> View Backups
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Nav tabs -->
        <ul class="nav nav-pills mb-3" id="dbTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="backups-tab" data-bs-toggle="tab" data-bs-target="#backups" type="button" role="tab" aria-controls="backups" aria-selected="true">
                    <i class="fas fa-list me-2"></i> Available Backups
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="schedules-tab" data-bs-toggle="tab" data-bs-target="#schedules" type="button" role="tab" aria-controls="schedules" aria-selected="false">
                    <i class="fas fa-calendar me-2"></i> Scheduled Backups
                </button>
            </li>
        </ul>
        
        <!-- Tab content -->
        <div class="tab-content">
            <!-- Backups tab -->
            <div class="tab-pane fade show active" id="backups" role="tabpanel" aria-labelledby="backups-tab">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Backup Files</h5>
                            <button id="refreshBackupsBtn" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="backupsList" class="list-group">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2">Loading backup files...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Schedules tab -->
            <div class="tab-pane fade" id="schedules" role="tabpanel" aria-labelledby="schedules-tab">
                <div class="schedule-controls">
                    <h5 class="mb-3">Create New Schedule</h5>
                    <form id="scheduleForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="frequency" class="form-label">Frequency</label>
                            <select id="frequency" class="form-select" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 day-select" id="weeklyOptions" style="display:none;">
                            <label for="dayOfWeek" class="form-label">Day of Week</label>
                            <select id="dayOfWeek" class="form-select">
                                <option value="0">Sunday</option>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 day-select" id="monthlyOptions" style="display:none;">
                            <label for="dayOfMonth" class="form-label">Day of Month</label>
                            <select id="dayOfMonth" class="form-select">
                                <?php for ($i = 1; $i <= 28; $i++) : ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="timeHour" class="form-label">Time</label>
                            <div class="input-group">
                                <select id="timeHour" class="form-select">
                                    <?php for ($i = 0; $i < 24; $i++) : ?>
                                        <option value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                                <span class="input-group-text">:</span>
                                <select id="timeMinute" class="form-select">
                                    <?php for ($i = 0; $i < 60; $i += 5) : ?>
                                        <option value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="retention" class="form-label">Keep for (days)</label>
                            <input type="number" class="form-control" id="retention" min="1" max="365" value="30">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i> Add Schedule
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Active Schedules</h5>
                            <button id="refreshSchedulesBtn" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="schedulesList" class="list-group">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2">Loading schedules...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreModalLabel">Confirm Restore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        <strong>Warning:</strong> Restoring will replace your current database with the selected backup.
                        This action cannot be undone.
                    </div>
                    <p>Are you sure you want to restore the database from the backup file: <strong id="restoreFileName">filename.sql</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRestoreBtn" class="btn btn-danger">
                        <i class="fas fa-database me-2"></i> Yes, Restore Database
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Backup Confirmation Modal -->
    <div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-labelledby="deleteBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteBackupModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the backup file: <strong id="deleteFileName">filename.sql</strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i> Delete Backup
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Global variables
            let selectedBackupFile = '';
            let restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
            let deleteBackupModal = new bootstrap.Modal(document.getElementById('deleteBackupModal'));
            
            // Load backups list
            function loadBackups() {
                $('#backupsList').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-muted mt-2">Loading backup files...</p></div>');
                
                $.ajax({
                    url: 'db_backup.php',
                    type: 'GET',
                    data: { action: 'list_backups' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            renderBackupsList(response.backups);
                        } else {
                            showToast('Error', response.message, 'danger');
                            $('#backupsList').html('<div class="alert alert-danger">Failed to load backups: ' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                        $('#backupsList').html('<div class="alert alert-danger">Failed to connect to the server</div>');
                    }
                });
            }
            
            // Render backups list
            function renderBackupsList(backups) {
                let html = '';
                
                if (backups.length === 0) {
                    html = '<div class="alert alert-info">No backup files found.</div>';
                } else {
                    for (let backup of backups) {
                        html += `
                        <div class="list-group-item backup-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">${backup.filename}</h6>
                                    <small class="text-muted">
                                        <i class="far fa-calendar-alt me-1"></i> ${backup.date} 
                                        <i class="fas fa-file-archive ms-3 me-1"></i> ${backup.size} KB
                                    </small>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary restore-btn me-2" data-file="${backup.filename}">
                                        <i class="fas fa-upload me-1"></i> Restore
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-backup-btn" data-file="${backup.filename}">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        `;
                    }
                }
                
                $('#backupsList').html(html);
            }
            
            // Load schedules list
            function loadSchedules() {
                $('#schedulesList').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-muted mt-2">Loading schedules...</p></div>');
                
                $.ajax({
                    url: 'db_backup_schedule.php',
                    type: 'GET',
                    data: { action: 'get_schedules' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            renderSchedulesList(response.schedules);
                        } else {
                            showToast('Error', response.message, 'danger');
                            $('#schedulesList').html('<div class="alert alert-danger">Failed to load schedules: ' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                        $('#schedulesList').html('<div class="alert alert-danger">Failed to connect to the server</div>');
                    }
                });
            }
            
            // Render schedules list
            function renderSchedulesList(schedules) {
                let html = '';
                
                if (schedules.length === 0) {
                    html = '<div class="alert alert-info">No scheduled backups found.</div>';
                } else {
                    for (let schedule of schedules) {
                        // Format timing information
                        let timeInfo = formatScheduleTime(schedule);
                        
                        html += `
                        <div class="list-group-item backup-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge ${schedule.is_active ? 'bg-success' : 'bg-secondary'} me-2">
                                            ${schedule.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        <h6 class="mb-0">${timeInfo}</h6>
                                    </div>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i> Created by ${schedule.creator_name}
                                        ${schedule.last_run ? ' • Last run: ' + schedule.last_run : ''}
                                        <i class="fas fa-history ms-2 me-1"></i> Keep for ${schedule.retention_days} days
                                    </small>
                                </div>
                                <div>
                                    <button class="btn btn-sm ${schedule.is_active ? 'btn-outline-warning' : 'btn-outline-success'} toggle-schedule-btn me-2" data-id="${schedule.id}">
                                        <i class="fas ${schedule.is_active ? 'fa-pause' : 'fa-play'} me-1"></i> ${schedule.is_active ? 'Pause' : 'Activate'}
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-schedule-btn" data-id="${schedule.id}">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        `;
                    }
                }
                
                $('#schedulesList').html(html);
            }
            
            // Format schedule time for display
            function formatScheduleTime(schedule) {
                let time = `${String(schedule.hour).padStart(2, '0')}:${String(schedule.minute).padStart(2, '0')}`;
                
                switch (schedule.frequency) {
                    case 'daily':
                        return `Daily at ${time}`;
                        
                    case 'weekly':
                        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        return `Weekly on ${days[schedule.day_of_week]} at ${time}`;
                        
                    case 'monthly':
                        return `Monthly on day ${schedule.day_of_month} at ${time}`;
                        
                    default:
                        return `${schedule.frequency} at ${time}`;
                }
            }
            
            // Create a new backup
            function createBackup() {
                showToast('Creating Backup', 'Backup process started...', 'info');
                
                $.ajax({
                    url: 'db_backup.php',
                    type: 'GET',
                    data: { action: 'backup' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showToast('Success', response.message, 'success');
                            loadBackups(); // Refresh the list
                        } else {
                            showToast('Error', response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                    }
                });
            }
            
            // Restore from backup
            function restoreBackup(filename) {
                showToast('Restoring', 'Restoration process started...', 'info');
                
                $.ajax({
                    url: 'db_restore.php',
                    type: 'GET',
                    data: { 
                        action: 'restore',
                        file: filename
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showToast('Success', response.message, 'success');
                        } else {
                            showToast('Error', response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                    }
                });
            }
            
            // Delete backup file
            function deleteBackup(filename) {
                $.ajax({
                    url: 'db_restore.php',
                    type: 'GET',
                    data: { 
                        action: 'delete_backup',
                        file: filename
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showToast('Success', response.message, 'success');
                            loadBackups(); // Refresh the list
                        } else {
                            showToast('Error', response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                    }
                });
            }
            
            // Add a new schedule
            function addSchedule(scheduleData) {
                showToast('Creating Schedule', 'Processing request...', 'info');
                
                // For debugging
                console.log('Sending schedule data:', scheduleData);
                
                $.ajax({
                    url: 'db_backup_schedule.php',
                    type: 'POST',
                    data: { 
                        action: 'add_schedule',
                        ...scheduleData
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Schedule response:', response);
                        if (response.status === 'success') {
                            showToast('Success', response.message, 'success');
                            loadSchedules(); // Refresh the list
                        } else {
                            showToast('Error', response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        console.log('Response text:', xhr.responseText);
                        showToast('Error', 'Failed to connect to the server. Check console for details.', 'danger');
                    }
                });
            }
            
            // Delete a schedule
            function deleteSchedule(scheduleId) {
                $.ajax({
                    url: 'db_backup_schedule.php',
                    type: 'GET',
                    data: { 
                        action: 'delete_schedule',
                        id: scheduleId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showToast('Success', response.message, 'success');
                            loadSchedules(); // Refresh the list
                        } else {
                            showToast('Error', response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                    }
                });
            }
            
            // Toggle schedule status
            function toggleSchedule(scheduleId) {
                $.ajax({
                    url: 'db_backup_schedule.php',
                    type: 'GET',
                    data: { 
                        action: 'toggle_schedule',
                        id: scheduleId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showToast('Success', response.message, 'success');
                            loadSchedules(); // Refresh the list
                        } else {
                            showToast('Error', response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Failed to connect to the server', 'danger');
                    }
                });
            }
            
            // Show toast notification
            function showToast(title, message, type = 'info') {
                const toastId = 'toast-' + Date.now();
                const toast = `
                <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                    <div class="toast-header bg-${type} text-white">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
                        <strong class="me-auto">${title}</strong>
                        <small>just now</small>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
                `;
                
                $('.toast-container').append(toast);
                const toastElement = new bootstrap.Toast(document.getElementById(toastId));
                toastElement.show();
                
                // Auto-remove after hidden
                $(`#${toastId}`).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            }
            
            // Handle frequency change to show/hide day options
            $('#frequency').change(function() {
                const frequency = $(this).val();
                
                // Hide all day selectors first
                $('.day-select').hide();
                
                // Show the appropriate one based on frequency
                if (frequency === 'weekly') {
                    $('#weeklyOptions').show();
                } else if (frequency === 'monthly') {
                    $('#monthlyOptions').show();
                }
            });
            
            // Manual backup button
            $('#manualBackupBtn, #createBackupBtn').click(function() {
                createBackup();
            });
            
            // Schedule form submission
            $('#scheduleForm').submit(function(e) {
                e.preventDefault();
                
                // Create schedule data object
                const scheduleData = {
                    frequency: $('#frequency').val(),
                    hour: $('#timeHour').val(),
                    minute: $('#timeMinute').val(),
                    retention_days: $('#retention').val()
                };
                
                // Add day information based on frequency
                if (scheduleData.frequency === 'weekly') {
                    scheduleData.day_of_week = $('#dayOfWeek').val();
                } else if (scheduleData.frequency === 'monthly') {
                    scheduleData.day_of_month = $('#dayOfMonth').val();
                }
                
                console.log('Submitting schedule:', scheduleData);
                addSchedule(scheduleData);
            });
            
            // View Backups button click
            $('#viewBackupsBtn').click(function() {
                // Switch to backups tab
                $('#dbTabs button[data-bs-target="#backups"]').tab('show');
            });
            
            // Configure Schedules button click
            $('#scheduleBackupBtn').click(function() {
                // Switch to schedules tab
                $('#dbTabs button[data-bs-target="#schedules"]').tab('show');
            });
            
            // Refresh buttons
            $('#refreshBackupsBtn').click(loadBackups);
            $('#refreshSchedulesBtn').click(loadSchedules);
            
            // Restore backup button click (delegated)
            $(document).on('click', '.restore-btn', function() {
                selectedBackupFile = $(this).data('file');
                $('#restoreFileName').text(selectedBackupFile);
                restoreModal.show();
            });
            
            // Confirm restore button
            $('#confirmRestoreBtn').click(function() {
                restoreModal.hide();
                restoreBackup(selectedBackupFile);
            });
            
            // Delete backup button click (delegated)
            $(document).on('click', '.delete-backup-btn', function() {
                selectedBackupFile = $(this).data('file');
                $('#deleteFileName').text(selectedBackupFile);
                deleteBackupModal.show();
            });
            
            // Confirm delete button
            $('#confirmDeleteBtn').click(function() {
                deleteBackupModal.hide();
                deleteBackup(selectedBackupFile);
            });
            
            // Toggle schedule button click (delegated)
            $(document).on('click', '.toggle-schedule-btn', function() {
                const scheduleId = $(this).data('id');
                toggleSchedule(scheduleId);
            });
            
            // Delete schedule button click (delegated)
            $(document).on('click', '.delete-schedule-btn', function() {
                const scheduleId = $(this).data('id');
                if (confirm('Are you sure you want to delete this schedule?')) {
                    deleteSchedule(scheduleId);
                }
            });
            
            // Initial load
            loadBackups();
            loadSchedules();
        });
    </script>
</body>
</html> 