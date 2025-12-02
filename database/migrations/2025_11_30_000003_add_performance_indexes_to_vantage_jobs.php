<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the database connection for the migration.
     */
    public function getConnection(): ?string
    {
        return config('vantage.database_connection');
    }

    /**
     * Run the migrations.
     *
     * Performance optimization: Add indexes for common queries
     */
    public function up(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('vantage_jobs')) {
            return;
        }

        $schema->table('vantage_jobs', function (Blueprint $table) {
            // Index for filtering by created_at (most common filter)
            $table->index('created_at', 'idx_vantage_jobs_created_at');

            // Index for filtering by status (processed, failed, processing)
            $table->index('status', 'idx_vantage_jobs_status');

            // Composite index for created_at + status (common combination)
            $table->index(['created_at', 'status'], 'idx_vantage_jobs_created_status');

            // Index for job_class (for grouping and filtering)
            $table->index('job_class', 'idx_vantage_jobs_job_class');

            // Index for exception_class (for top exceptions query)
            $table->index('exception_class', 'idx_vantage_jobs_exception_class');

            // Index for queue (for queue filtering)
            $table->index('queue', 'idx_vantage_jobs_queue');

            // Index for retried_from_id (for retry chain queries)
            $table->index('retried_from_id', 'idx_vantage_jobs_retried_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('vantage_jobs')) {
            return;
        }

        $schema->table('vantage_jobs', function (Blueprint $table) {
            $table->dropIndex('idx_vantage_jobs_created_at');
            $table->dropIndex('idx_vantage_jobs_status');
            $table->dropIndex('idx_vantage_jobs_created_status');
            $table->dropIndex('idx_vantage_jobs_job_class');
            $table->dropIndex('idx_vantage_jobs_exception_class');
            $table->dropIndex('idx_vantage_jobs_queue');
            $table->dropIndex('idx_vantage_jobs_retried_from');
        });
    }
};
