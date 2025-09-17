# AlFawz Qur'an Institute — generated with TRAE
# Author: Auto-scaffold (review required)

# cPanel Cron Job Setup Instructions

## Prerequisites
1. Create logs directory: `/home/USERNAME/logs/`
2. Replace `USERNAME` with your actual cPanel username in all commands below
3. Ensure PHP path is correct (usually `/usr/local/bin/php` or `/usr/bin/php`)

## Required Cron Jobs

### 1. Laravel Scheduler (Every Minute)
**Frequency:** `* * * * *` (every minute)
**Command:**
```bash
php /home/USERNAME/apps/api/artisan schedule:run >> /home/USERNAME/logs/schedule.log 2>&1
```

### 2. Queue Worker (Every 5 Minutes)
**Frequency:** `*/5 * * * *` (every 5 minutes)
**Command:**
```bash
php /home/USERNAME/apps/api/artisan queue:work --stop-when-empty >> /home/USERNAME/logs/queue.log 2>&1
```

### 3. Queue Restart (Daily at 2 AM)
**Frequency:** `0 2 * * *` (daily at 2:00 AM)
**Command:**
```bash
php /home/USERNAME/apps/api/artisan queue:restart >> /home/USERNAME/logs/queue-restart.log 2>&1
```

## Setup Steps in cPanel

1. **Access Cron Jobs:**
   - Login to cPanel
   - Navigate to "Advanced" section
   - Click "Cron Jobs"

2. **Add Each Cron Job:**
   - Select the appropriate frequency or use "Advanced (Unix Style)"
   - Paste the command (replace USERNAME with your actual username)
   - Click "Add New Cron Job"

3. **Verify Setup:**
   - Check that all 3 cron jobs are listed
   - Wait a few minutes and check log files in `/home/USERNAME/logs/`
   - Logs should show successful execution

## Troubleshooting

- **Permission Issues:** Ensure artisan file has execute permissions
- **PHP Path Issues:** Try `/usr/local/bin/php` if `/usr/bin/php` doesn't work
- **Log Issues:** Create logs directory manually if it doesn't exist
- **Queue Issues:** Check database connection in Laravel .env file

## Log Monitoring

Regularly check these log files:
- `/home/USERNAME/logs/schedule.log` - Laravel scheduler output
- `/home/USERNAME/logs/queue.log` - Queue worker output
- `/home/USERNAME/logs/queue-restart.log` - Queue restart output