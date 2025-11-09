# Vantage

A Laravel package that tracks and monitors your queue jobs. 
Automatically records job execution history, failures, retries, 
and provides a simple web interface to view everything.

## Installation

```bash
composer require houdaslassi/vantage
php artisan vendor:publish --tag=vantage-config
php artisan migrate
```

The package will automatically register itself. Publish the config file to customize settings.

### Requirements

- Laravel 10.x or 11.x
- PHP 8.1 or higher
- One of the following databases:
  - MySQL 5.7+ / MariaDB 10.3+
  - PostgreSQL 9.6+
  - SQLite 3.8.8+

## Features

### Universal Queue Driver Support

**Works with all Laravel queue drivers** - database, Redis, SQS, Beanstalkd, and any other driver. Unlike other monitoring tools that require specific drivers, Vantage tracks jobs from **any queue driver** and saves all data to your database for persistent tracking and analysis.

### Job Tracking

Every job gets tracked in the `queue_job_runs` table with:
- Job class, queue, connection
- Status (processing, processed, failed)
- Start/finish times and duration
- UUID for tracking across retries
- **All data saved to database** - complete history of every job execution

### Failure Details

When jobs fail, we store the exception class, message, and full stack trace. Much easier to debug than Laravel's default failed_jobs table.

Visit `/vantage/failed` to see all failed jobs with exception details and retry options.

![Failed Jobs](screenshots/vantage_04.png)

![Failed Jobs List](screenshots/vanatge_06.png)

### Web Interface

Visit `/vantage` to access the comprehensive monitoring dashboard:

**Dashboard** (`/vantage`) - Overview of your queue system:
- **Statistics Cards**: Total jobs, processed, failed, processing, and success rate
- **Queue Depth Monitoring**: Real-time pending job counts per queue with health status
- **Success Rate Trend Chart**: Visual representation of job success/failure over time
- **Top Failing Jobs**: See which job classes fail most often
- **Top Exceptions**: Most common error types with counts
- **Recent Jobs Table**: Latest 20 jobs with quick actions
- **Recent Batches**: Track Laravel job batches with success/failure rates
- **Time Period Filters**: View data for last hour, 6 hours, 24 hours, 7 days, 30 days, or all time

![Dashboard](screenshots/vantage_01.png)

**Recent Jobs Table** - Quick view of the latest 20 jobs with status, duration, and quick actions:

The Recent Jobs table appears on the dashboard showing:
- Job ID, class name, and queue
- Tags associated with each job
- Status indicators (Processing, Processed, Failed)
- Duration and creation time
- Quick "View" action to see full job details

**Recent Batches** - Track Laravel job batches with success/failure rates:

![Recent Batches](screenshots/vantage_03.png)

**Jobs List** - View and filter all jobs with advanced filtering options:

Visit `/vantage/jobs` to access the jobs list with powerful filtering capabilities:
- Filter by status (processed, failed, processing)
- Filter by queue name
- Filter by job class (partial match supported)
- Filter by tags (supports multiple tags with "all" or "any" mode)
- Filter by date range
- Popular tags cloud for quick filtering
- Pagination (50 jobs per page)

![Jobs List](screenshots/vantage_02.png)

Filter jobs by status, queue, job class, tags, and date range:

![Jobs List with Filters](screenshots/vantage_08.png)

**Job Details** (`/vantage/jobs/{id}`) - Comprehensive job information:
- **Basic Information**: Status, UUID, queue, connection, job class
- **Timing**: Start time, finish time, duration
- **Exception Details**: Full exception class, message, and stack trace for failed jobs
- **Payload**: Complete job payload with JSON formatting
- **Tags**: All tags associated with the job
- **Retry Chain**: View original job and all retry attempts
- **Quick Actions**: Retry failed jobs directly from the details page

**Note:** The dashboard requires authentication by default. Make sure you're logged in or customize the `viewVantage` gate as described in the Configuration section.

### Retry Failed Jobs

```bash
php artisan vantage:retry {job_id}
```

Or use the web interface - just click retry on any failed job.

![Retry Jobs](screenshots/vantage_09.png)

### Job Tagging

Jobs with tags (using Laravel's `tags()` method) are automatically tracked. Visit `/vantage/tags` to see:

- **Tags Analytics**: View statistics for all tags (total jobs, processed, failed, processing, success rate, average duration)
- **Search**: Filter tags by name in real-time
- **Sortable Columns**: Click any column header to sort by that metric
- **Clickable Tags**: Click a tag to view all jobs with that tag
- **Time Filters**: View data for last 24 hours, 7 days, or 30 days

![Tags Analytics](screenshots/vantage_07.png)

Filter and view jobs by tag in the web interface.

### Queue Depth Monitoring

Real-time queue depth tracking for all your queues. See how many jobs are pending in each queue with health status indicators.

![Queue Depth](screenshots/vantage_10.png)

Visit `/vantage` to see queue depths displayed with:
- Current pending job count per queue
- Health status (healthy/normal/warning/critical)
- Support for database and Redis queue drivers

### Performance Telemetry

Vantage automatically tracks performance metrics for your jobs:
- Memory usage (start, end, peak)
- CPU time (user and system)
- Execution duration

Telemetry can be configured via environment variables (see Environment Variables section below).

## Configuration

The config file should already be published during installation. If you need to republish it:

```bash
php artisan vendor:publish --tag=vantage-config
```

### Main Settings

- `store_payload` - Whether to store job payloads (for debugging/retry)
- `redact_keys` - Keys to redact from payloads (password, token, etc.)
- `retention_days` - How long to keep job history
- `notify.email` - Email to notify on failures
- `notify.slack_webhook` - Slack webhook URL for failures
- `telemetry.enabled` - Enable performance telemetry (memory/CPU)
- `telemetry.sample_rate` - Sampling rate (0.0-1.0, default: 1.0)
- `telemetry.capture_cpu` - Enable CPU time tracking

### Enable/Disable Package

To disable the package entirely (useful for staging environments):

```env
VANTAGE_ENABLED=false
```

When disabled:
- No job tracking occurs
- Routes are not registered
- Event listeners are not active
- Commands are not registered
- No database writes
- Gate authorization is not registered

Perfect for testing in staging without affecting production data!

### Multi-Database Support

If your application uses multiple databases, you can specify which database connection to use for storing queue job runs:

```env
VANTAGE_DATABASE_CONNECTION=mysql
```

This ensures the `queue_job_runs` table is created and accessed from the correct database connection. The package automatically detects your database driver (MySQL, PostgreSQL, SQLite) and uses the appropriate SQL syntax for queries.

### Authentication

Vantage uses Laravel's Gate system for authorization (similar to Horizon). **Users must be authenticated via Laravel's authentication system** to access the dashboard. By default, all authenticated users can access Vantage.

**Important:** Make sure your application has authentication set up (e.g., Laravel Breeze, Laravel Jetstream, or custom auth). The dashboard requires users to be logged in.

To customize access (e.g., only allow admins), override the `viewVantage` gate in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewVantage', function ($user) {
        // Only allow admins
        return $user->isAdmin();
        
        // Or any other custom logic
        // return $user->hasRole('developer');
    });
}
```

To disable authentication entirely (not recommended for production):

```env
VANTAGE_AUTH_ENABLED=false
```

## Testing

Run the test suite:

```bash
composer test
```

## Commands

### Retry Failed Job

```bash
php artisan vantage:retry {job_id}
```

Retry a failed job by its ID. The job will be re-queued with the same payload and settings.

### Cleanup Stuck Jobs

```bash
php artisan vantage:cleanup-stuck [--timeout=1] [--dry-run]
```

Clean up jobs that are stuck in "processing" state. Useful for jobs that were interrupted or crashed.

- `--timeout=1` - Hours to consider a job stuck (default: 1 hour)
- `--dry-run` - Show what would be cleaned without actually cleaning

Example:
```bash
# Clean up jobs stuck for more than 2 hours
php artisan vantage:cleanup-stuck --timeout=2

# Preview what would be cleaned
php artisan vantage:cleanup-stuck --dry-run
```

## Environment Variables

```env
# Master switch - Enable/disable entire package (default: true)
VANTAGE_ENABLED=true

# Database connection for queue_job_runs table (optional)
VANTAGE_DATABASE_CONNECTION=mysql

# Authentication (default: true)
VANTAGE_AUTH_ENABLED=true

# Payload storage (default: true)
VANTAGE_STORE_PAYLOAD=true

# Telemetry (default: true)
VANTAGE_TELEMETRY_ENABLED=true
VANTAGE_TELEMETRY_SAMPLE_RATE=1.0
VANTAGE_TELEMETRY_CPU=true

# Notifications
VANTAGE_NOTIFY_EMAIL=admin@example.com
VANTAGE_SLACK_WEBHOOK=https://hooks.slack.com/services/...

# Routes (default: true)
VANTAGE_ROUTES=true
```

## Demo

Watch a brief demo of the Vantage dashboard in action:

https://github.com/houdaslassi/vantage/blob/main/screenshots/record-demo.mov

<video src="screenshots/record-demo.mov" controls width="100%"></video>

## License

MIT
