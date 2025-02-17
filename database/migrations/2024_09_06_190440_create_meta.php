<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = Schema::connection(config('quickrep.QUICKREP_DB'));

        if (!$connection->hasTable('quickrep_meta')) {
            $connection->create('quickrep_meta', function (Blueprint $table) {
                $table->increments('id');
                $table->string('key');
                $table->string('meta_key');
                $table->text('meta_value');
                $table->timestamps();
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
        Schema::connection(config('quickrep.QUICKREP_DB'))->dropIfExists('quickrep_meta');
    }
}