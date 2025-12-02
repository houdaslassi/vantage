<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $schema->create('queue_job_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('job_class')->index();
            $table->string('queue')->nullable()->index();
            $table->string('connection')->nullable()->index();
            $table->unsignedInteger('attempt')->default(0);
            $table->unsignedInteger('retries')->default(0);
            $table->unsignedBigInteger('retried_from_id')->nullable()->references('id')->on('queue_job_runs')->nullOnDelete();
            $table->enum('status', ['processing', 'processed', 'failed'])->index();

            // extra details for observability
            $table->unsignedBigInteger('duration_ms')->nullable()->index();
            $table->string('exception_class')->nullable()->index();
            $table->text('exception_message')->nullable();
            $table->text('stack')->nullable();
            $table->longText('payload')->nullable();
            $table->json('job_tags')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        $schema->dropIfExists('queue_job_runs');
    }
};
