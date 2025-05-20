# Task Scheduler

A simple PHP-based task management system with email notifications.

## Features

- Add, edit, complete, and delete tasks
- Email subscription with verification
- Hourly task reminders via email
- No database required - uses simple text files

## Installation

1. Clone the repository
2. Make sure PHP 8.3 is installed
3. Start a PHP server:
   ```
   php -S localhost:8000 -t src/
   ```
4. Access the application at http://localhost:8000/index.php

## Setting Up Scheduled Reminders

### On Linux/Mac:
1. Make the setup script executable:
   ```
   chmod +x src/setup_cron.sh
   ```
2. Run the script:
   ```
   ./src/setup_cron.sh
   ```

### On Windows:
1. Right-click on PowerShell and select "Run as Administrator" (this is critical)
2. Navigate to the project directory
3. Run the setup script:
   ```
   cd path\to\project
   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
   .\src\setup_task_scheduler.ps1
   ```

**Note**: Administrator privileges are required for setting up the scheduled task on Windows.

## Alternative Manual Setup (Windows)
If the PowerShell script doesn't work, you can set up the task manually:

1. Open Task Scheduler (search for it in the Start menu)
2. Click "Create Basic Task" 
3. Name it "TaskScheduler_HourlyReminder"
4. Set the trigger to "Daily" and configure it to repeat every 1 hour
5. For the action, select "Start a program"
6. In "Program/script" enter the path to your PHP executable (e.g., "C:\PHP\php.exe")
7. In "Add arguments" enter the full path to cron.php (e.g., "D:\path\to\src\cron.php")
8. Complete the wizard

## File Structure

- `index.php` - Main interface for task management
- `functions.php` - Core functionality
- `mail_config.php` - Email configuration
- `verify.php` - Email verification handler
- `unsubscribe.php` - Unsubscribe handler
- `cron.php` - Scheduled task reminders
- `setup_cron.sh` - Linux/Mac cron setup
- `setup_task_scheduler.ps1` - Windows task scheduler setup
- `tasks.txt` - Stores task data
- `subscribers.txt` - Stores verified email subscribers
- `pending_subscriptions.txt` - Stores pending verifications

## Email Configuration

The application uses Gmail SMTP for sending emails. The configuration is in `mail_config.php`.

## Testing

To test email functionality, visit http://localhost:8000/test_email.php 