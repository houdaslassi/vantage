<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the database connection for the migration.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return config('vantage.database_connection');
    }

    public function up(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        if ($schema->hasTable('queue_job_runs') && ! $schema->hasTable('vantage_jobs')) {
            $schema->rename('queue_job_runs', 'vantage_jobs');
        }
    }

    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        if ($schema->hasTable('vantage_jobs') && ! $schema->hasTable('queue_job_runs')) {
            $schema->rename('vantage_jobs', 'queue_job_runs');
        }
    }
};
