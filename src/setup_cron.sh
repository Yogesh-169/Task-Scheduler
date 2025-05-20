#!/bin/bash

# Get the absolute path of the script's directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Path to PHP executable (assumes PHP is in the PATH)
PHP_PATH=$(which php)

if [ -z "$PHP_PATH" ]; then
    echo "Error: PHP executable not found in PATH"
    exit 1
fi

# Path to the cron.php file
CRON_FILE="$SCRIPT_DIR/cron.php"

if [ ! -f "$CRON_FILE" ]; then
    echo "Error: cron.php file not found at $CRON_FILE"
    exit 1
fi

# Create a temporary file for the current crontab
TEMP_CRONTAB=$(mktemp)

# Export current crontab to the temporary file
crontab -l > "$TEMP_CRONTAB" 2>/dev/null || echo "# New crontab" > "$TEMP_CRONTAB"

# Check if the cron job already exists
if grep -q "$CRON_FILE" "$TEMP_CRONTAB"; then
    echo "Cron job for task reminders already exists."
else
    # Add the new cron job to run every hour
    echo "0 * * * * $PHP_PATH $CRON_FILE > /dev/null 2>&1" >> "$TEMP_CRONTAB"
    
    # Apply the new crontab
    crontab "$TEMP_CRONTAB"
    
    echo "Cron job has been set up to run task reminders every hour."
fi

# Clean up the temporary file
rm "$TEMP_CRONTAB"

echo "Setup complete. The task reminder will be sent every hour."
