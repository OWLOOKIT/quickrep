<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSocketsAndWrenches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = Schema::connection(config('quickrep.QUICKREP_DB'));

        // Создаём таблицу socket, если её ещё нет
        if (!$connection->hasTable('quickrep_socket')) {
            $connection->create('quickrep_socket', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('wrench_id');
                $table->string('socket_value', 1024);
                $table->string('socket_label', 1024);
                $table->boolean('is_default_socket')->default(0);
                $table->integer('socketsource_id');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });
        }

        // Создаём таблицу socketsource, если её ещё нет
        if (!$connection->hasTable('quickrep_socketsource')) {
            $connection->create('quickrep_socketsource', function (Blueprint $table) {
                $table->increments('id');
                $table->string('socketsource_name', 1024);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });
        }

        // Создаём таблицу socket_user, если её ещё нет
        if (!$connection->hasTable('quickrep_socket_user')) {
            $connection->create('quickrep_socket_user', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('wrench_id');
                $table->integer('current_chosen_socket_id');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });
        }

        // Создаём таблицу wrench, если её ещё нет
        if (!$connection->hasTable('quickrep_wrench')) {
            $connection->create('quickrep_wrench', function (Blueprint $table) {
                $table->increments('id');
                $table->string('wrench_lookup_string', 200);
                $table->string('wrench_label', 200);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->unique('wrench_label');
                $table->unique('wrench_lookup_string');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $connection = Schema::connection(config('quickrep.QUICKREP_DB'));
        $connection->dropIfExists('quickrep_socket');
        $connection->dropIfExists('quickrep_socketsource');
        $connection->dropIfExists('quickrep_socket_user');
        $connection->dropIfExists('quickrep_wrench');
    }
}