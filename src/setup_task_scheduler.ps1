# PowerShell script to set up a task in Windows Task Scheduler
# This script needs to be run with Administrator privileges

# Get the absolute paths
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpScriptPath = Join-Path $scriptPath "cron.php"
$logFilePath = Join-Path $scriptPath "cron_log.txt"

# Find PHP executable
$phpPath = ""
$possiblePaths = @(
    "C:\PHP\php.exe",
    "C:\PHP8\php.exe",
    "C:\PHP7\php.exe",
    "C:\xampp\php\php.exe",
    "C:\laragon\bin\php\php-8.3.0\php.exe",
    "C:\laragon\bin\php\php-8.2.0\php.exe", 
    "C:\laragon\bin\php\php-8.1.0\php.exe",
    "C:\laragon\bin\php\php-8.0.0\php.exe"
)

foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $phpPath = $path
        break
    }
}

# If PHP is not found in common locations, try to find it in PATH
if ($phpPath -eq "") {
    try {
        $phpPath = (Get-Command php -ErrorAction Stop).Source
    } catch {
        Write-Host "PHP executable not found. Please make sure PHP is installed and in your PATH."
        exit 1
    }
}

# Check if the cron.php file exists
if (-Not (Test-Path $phpScriptPath)) {
    Write-Host "Error: cron.php file not found at $phpScriptPath"
    exit 1
}

Write-Host "PHP executable: $phpPath"
Write-Host "Script path: $phpScriptPath"

# Task name
$taskName = "TaskScheduler_HourlyReminder"

# Check if the task already exists
$taskExists = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($taskExists) {
    Write-Host "Task already exists. Removing the existing task..."
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create the action that will be performed by the scheduled task
$action = New-ScheduledTaskAction -Execute $phpPath -Argument "$phpScriptPath >> $logFilePath 2>&1"

# Create the trigger (hourly)
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1)

# Create the task settings
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -AllowStartIfOnBatteries

# Register the task (requires Administrator privileges)
try {
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Runs the Task Scheduler cron.php script hourly to send task reminders" -User "SYSTEM"
    Write-Host "Task has been scheduled successfully to run every hour."
    Write-Host "Task will run as SYSTEM user, which ensures it can run even when no user is logged in."
} catch {
    Write-Host "Error creating the scheduled task. Make sure you run this script with Administrator privileges."
    Write-Host $_.Exception.Message
    exit 1
}

Write-Host "Setup complete. The task reminder will be sent every hour."
Write-Host "Logs will be saved to: $logFilePath" 