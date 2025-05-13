# Database Backup and Restore System

This document explains the backup and restore functionality implemented for your Bunnishop e-commerce website.

## Overview

The system provides several key features:
- Manual database backups
- Scheduled backups (daily, weekly, or monthly)
- Database restoration from backups
- Backup file management
- Backup retention policies

## Components

The backup and restore system consists of several PHP scripts:

1. **db_backup.php**: Core backup functionality
2. **db_restore.php**: Core restoration functionality
3. **db_backup_schedule.php**: Schedule management
4. **backup_management.php**: Admin UI for the backup system
5. **cron_schedule_backup.php**: Cron job for automated backups

## How to Use

### Accessing the Backup System

1. Log in to your admin account
2. Navigate to the Database Backup section in the admin menu

### Creating a Manual Backup

1. Click on the "Backup Now" button in the "Manual Backup" card
2. The system will create a backup of your entire database
3. Once complete, you will see a success notification

### Scheduling Automatic Backups

1. Navigate to the "Scheduled Backups" tab
2. Fill in the schedule form:
   - Select frequency (daily, weekly, monthly)
   - For weekly backups, select the day of the week
   - For monthly backups, select the day of the month
   - Set the time (hour:minute)
   - Set the retention period (how many days to keep backups)
3. Click "Add Schedule"

### Restoring a Database

1. Navigate to the "Available Backups" tab
2. Find the backup file you want to restore
3. Click the "Restore" button
4. Confirm the restoration in the popup dialog
5. Wait for the restoration process to complete

### Managing Backup Files

- **View Backups**: All backups are listed in the "Available Backups" tab
- **Delete Backups**: Click the "Delete" button next to any backup
- **Refresh List**: Click the "Refresh" button to update the backup list

## Setting Up Automated Backups (Cron Jobs)

For fully automated backups, you need to set up a cron job to run the backup script at regular intervals.

### On a Linux/Unix Server

Add the following line to your crontab:

```
* * * * * php /path/to/your/website/admin/cron_schedule_backup.php
```

This will check every minute if any scheduled backups need to be run.

### On Shared Hosting

If you don't have access to cron jobs:

1. Set up a secure key in `cron_schedule_backup.php`:
   ```php
   $cron_key = "YOUR_SECRET_KEY"; // Change this to a random string
   ```

2. Use a web-based cron service like cron-job.org, EasyCron, or SetCronJob
3. Point it to: `https://yourwebsite.com/admin/cron_schedule_backup.php?cron_key=YOUR_SECRET_KEY`

## Backup File Location

All backup files are stored in the `/backups` directory of your website.

## Retention Policy

- Backups will be automatically deleted after the specified retention period
- The default retention period is 30 days
- Each schedule can have its own retention period

## Troubleshooting

If the backup or restore functionality isn't working:

1. **Check Permissions**: Ensure the web server has write permissions to the `/backups` directory
2. **PHP Configuration**: Make sure `exec()` function is enabled for mysqldump method
3. **Memory Limits**: For large databases, you may need to increase PHP memory limit and execution time
4. **Database Connectivity**: Verify the database credentials in `db_connection.php`

## Security Considerations

- Backup files contain sensitive data - ensure the `/backups` directory is not publicly accessible
- Use a strong, random key for the cron job access
- Regularly test your backup and restore processes

## Technical Notes

- The system attempts to use `mysqldump` first for better performance
- If `mysqldump` is unavailable, it falls back to a pure PHP backup method
- Restoration can use either the `mysql` command or PHP-based restoration
- All backup/restore actions are logged in the audit_logs table 