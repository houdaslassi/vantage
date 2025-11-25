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
     * Additional composite indexes for better query performance on common
     * filter combinations used in the dashboard and jobs list.
     */
    public function up(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        if (!$schema->hasTable('vantage_jobs')) {
            return;
        }

        $schema->table('vantage_jobs', function (Blueprint $table) {
            // Composite index for jobs list filtering (status + queue + created_at)
            // Supports: WHERE status = ? AND queue = ? ORDER BY created_at DESC
            $table->index(['status', 'queue', 'created_at'], 'idx_vantage_status_queue_created');

            // Composite index for class-specific analytics (job_class + created_at)
            // Supports: WHERE job_class = ? ORDER BY created_at DESC
            $table->index(['job_class', 'created_at'], 'idx_vantage_class_created');

            // Composite index for performance metrics queries
            // Supports: WHERE created_at > ? AND duration_ms IS NOT NULL
            $table->index(['created_at', 'duration_ms'], 'idx_vantage_created_duration');

            // Composite index for memory metrics queries
            // Supports: WHERE created_at > ? AND memory_peak_end_bytes IS NOT NULL
            $table->index(['created_at', 'memory_peak_end_bytes'], 'idx_vantage_created_memory');

            // Composite index for queue analytics
            // Supports: WHERE queue = ? AND status = ? AND created_at > ?
            $table->index(['queue', 'status', 'created_at'], 'idx_vantage_queue_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        if (!$schema->hasTable('vantage_jobs')) {
            return;
        }

        $schema->table('vantage_jobs', function (Blueprint $table) {
            $table->dropIndex('idx_vantage_status_queue_created');
            $table->dropIndex('idx_vantage_class_created');
            $table->dropIndex('idx_vantage_created_duration');
            $table->dropIndex('idx_vantage_created_memory');
            $table->dropIndex('idx_vantage_queue_status_created');
        });
    }
};
