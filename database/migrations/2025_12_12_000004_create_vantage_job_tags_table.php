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
     * Creates a denormalized tags table for efficient aggregation queries.
     * This table stores one row per (job_id, tag) pair, allowing for
     * O(1) GROUP BY aggregations instead of O(n) JSON parsing.
     *
     * This is essential for high-volume installations (100k+ jobs)
     * where JSON array parsing in application code becomes prohibitively slow.
     */
    public function up(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        $schema->create('vantage_job_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('tag', 255)->index();
            $table->timestamp('created_at')->nullable();

            // Composite indexes for efficient aggregation queries
            $table->index(['tag', 'created_at'], 'idx_vantage_job_tags_tag_created');
            $table->index(['job_id'], 'idx_vantage_job_tags_job_id');

            // Foreign key with cascade delete
            $table->foreign('job_id')
                ->references('id')
                ->on('vantage_jobs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        $schema->dropIfExists('vantage_job_tags');
    }
};
