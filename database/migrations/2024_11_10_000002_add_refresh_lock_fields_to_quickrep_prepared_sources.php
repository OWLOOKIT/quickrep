<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connectionName = config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', config('database.default'));
        $tableName = config('quickrep.PREPARED_SOURCES_TABLE', 'quickrep_prepared_sources');

        $schema = Schema::connection($connectionName);

        if (! $schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table): void {
            if (! Schema::connection(config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', config('database.default')))
                ->hasColumn($table->getTable(), 'refresh_lock_owner')) {
                $table->string('refresh_lock_owner', 128)->nullable()->after('last_refresh_error');
            }

            if (! Schema::connection(config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', config('database.default')))
                ->hasColumn($table->getTable(), 'refresh_lock_expires_at')) {
                $table->timestamp('refresh_lock_expires_at')->nullable()->after('refresh_lock_owner');
            }

            if (! Schema::connection(config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', config('database.default')))
                ->hasColumn($table->getTable(), 'last_refresh_triggered_by')) {
                $table->string('last_refresh_triggered_by', 64)->nullable()->after('refresh_lock_expires_at');
            }
        });
    }

    public function down(): void
    {
        $connectionName = config('quickrep.QUICKREP_CONFIG_DB_CONNECTION', config('database.default'));
        $tableName = config('quickrep.PREPARED_SOURCES_TABLE', 'quickrep_prepared_sources');

        $schema = Schema::connection($connectionName);

        if (! $schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table): void {
            $table->dropColumn([
                'refresh_lock_owner',
                'refresh_lock_expires_at',
                'last_refresh_triggered_by',
            ]);
        });
    }
};