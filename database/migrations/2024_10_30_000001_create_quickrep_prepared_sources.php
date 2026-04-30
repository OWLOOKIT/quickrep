<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connectionName = config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', 'pgsql');
        $tableName = config('quickrep.PREPARED_SOURCES_TABLE', 'quickrep_prepared_sources');

        $schema = Schema::connection($connectionName);

        if ($schema->hasTable($tableName)) {
            return;
        }

        $schema->create($tableName, function (Blueprint $table): void {
            $table->id();

            $table->string('report_key', 190)->unique();
            $table->string('report_class', 512);

            $table->string('source_connection', 128);
            $table->string('source_schema', 128)->default('application');
            $table->string('source_name', 190);
            $table->string('source_type', 64);

            $table->string('refresh_strategy', 64)->default('manual');
            $table->string('refresh_command', 512)->nullable();
            $table->string('refresh_cron_expression', 128)->nullable();
            $table->unsignedInteger('refresh_interval_seconds')->nullable();
            $table->string('refresh_schedule_description', 512)->nullable();

            $table->unsignedInteger('expected_freshness_seconds');
            $table->unsignedInteger('stale_after_seconds');

            $table->timestamp('last_refresh_started_at')->nullable();
            $table->timestamp('last_refresh_finished_at')->nullable();
            $table->timestamp('last_successful_refresh_at')->nullable();
            $table->unsignedInteger('last_refresh_duration_ms')->nullable();
            $table->string('last_refresh_status', 32)->default('never');
            $table->text('last_refresh_error')->nullable();
            $table->unsignedBigInteger('last_source_row_count')->nullable();

            $table->string('cache_connection', 128)->nullable();
            $table->unsignedInteger('cache_ttl_seconds')->nullable();
            $table->timestamp('last_cache_built_at')->nullable();
            $table->timestamp('last_cache_cleared_at')->nullable();

            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            $table->index(['report_key', 'is_enabled']);
            $table->index(['source_connection', 'source_schema', 'source_name'], 'quickrep_prepared_sources_source_idx');
            $table->index(['last_refresh_status', 'last_successful_refresh_at'], 'quickrep_prepared_sources_status_idx');
        });
    }

    public function down(): void
    {
        $connectionName = config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', 'pgsql');
        $tableName = config('quickrep.PREPARED_SOURCES_TABLE', 'quickrep_prepared_sources');

        Schema::connection($connectionName)->dropIfExists($tableName);
    }
};