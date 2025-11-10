<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

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

        $schema->table('queue_job_runs', function (Blueprint $table) {
            // Memory metrics (bytes)
            $table->unsignedBigInteger('memory_start_bytes')->nullable()->after('duration_ms');
            $table->unsignedBigInteger('memory_end_bytes')->nullable()->after('memory_start_bytes');
            $table->unsignedBigInteger('memory_peak_start_bytes')->nullable()->after('memory_end_bytes');
            $table->unsignedBigInteger('memory_peak_end_bytes')->nullable()->after('memory_peak_start_bytes');
            $table->unsignedBigInteger('memory_peak_delta_bytes')->nullable()->after('memory_peak_end_bytes');

            // CPU time (milliseconds, deltas)
            $table->unsignedInteger('cpu_user_ms')->nullable()->after('memory_peak_delta_bytes');
            $table->unsignedInteger('cpu_sys_ms')->nullable()->after('cpu_user_ms');
        });
    }

    public function down(): void
    {
        $connection = $this->getConnection();
        $schema = Schema::connection($connection);

        $schema->table('queue_job_runs', function (Blueprint $table) {
            $table->dropColumn([
                'memory_start_bytes',
                'memory_end_bytes',
                'memory_peak_start_bytes',
                'memory_peak_end_bytes',
                'memory_peak_delta_bytes',
                'cpu_user_ms',
                'cpu_sys_ms',
            ]);
        });
    }
};


