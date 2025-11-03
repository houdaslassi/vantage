# Vantage

A Laravel package that tracks and monitors your queue jobs. Automatically records job execution history, failures, retries, and provides a simple web interface to view everything.

## Installation

```bash
composer require houdaslassi/vantage
```

The package will automatically register itself and run migrations.

## Features

### Job Tracking

Every job gets tracked in the `queue_job_runs` table with:
- Job class, queue, connection
- Status (processing, processed, failed)
- Start/finish times and duration
- UUID for tracking across retries

### Failure Details

When jobs fail, we store the exception class, message, and full stack trace. Much easier to debug than Laravel's default failed_jobs table.

### Web Interface

Visit `/vantage` to see:
- Dashboard with stats and charts
- List of all jobs with filtering
- Individual job details with retry chains
- Failed jobs page

### Retry Failed Jobs

```bash
php artisan vantage:retry {job_id}
```

Or use the web interface - just click retry on any failed job.

### Job Tagging

Jobs with tags (using Laravel's `tags()` method) are automatically tracked. Filter and view jobs by tag in the web interface.

### Queue Depth Monitoring

Real-time queue depth tracking for all your queues. See how many jobs are pending in each queue with health status indicators.

Visit `/vantage` to see queue depths displayed with:
- Current pending job count per queue
- Health status (healthy/normal/warning/critical)
- Support for database and Redis queue drivers

### Performance Telemetry

Automatic tracking of job performance metrics:
- **Memory usage**: Start, end, peak memory, and memory delta
- **CPU usage**: User and system CPU time (milliseconds)

Configurable via `config/vantage.php`:
- Enable/disable telemetry
- Sampling rate (reduce overhead)
- Optional CPU capture

Note: Run the migration to add telemetry columns: `php artisan migrate`

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=vantage-config
```

Main settings:
- `store_payload` - Whether to store job payloads (for debugging/retry)
- `redact_keys` - Keys to redact from payloads (password, token, etc.)
- `retention_days` - How long to keep job history
- `notify.email` - Email to notify on failures
- `notify.slack_webhook` - Slack webhook URL for failures
- `telemetry.enabled` - Enable performance telemetry (memory/CPU)
- `telemetry.sample_rate` - Sampling rate (0.0-1.0, default: 1.0)
- `telemetry.capture_cpu` - Enable CPU time tracking

## Testing

Run the test suite:

```bash
composer test
```

Generate test jobs for load testing:

```bash
php artisan vantage:generate-test-jobs --count=1000 --success-rate=80
```

## Commands

- `vantage:retry {id}` - Retry a failed job
- `vantage:generate-test-jobs` - Generate test jobs for load testing
- `vantage:cleanup-stuck` - Clean up jobs stuck in processing state

## License

MIT
