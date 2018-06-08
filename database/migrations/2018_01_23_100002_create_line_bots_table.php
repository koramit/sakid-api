<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLineBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_bots', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->primary('id');
            $table->smallInteger('service_domain_id')->unsigned();
            $table->string('name');
            $table->string('channel_access_token', 512); // encrypt
            $table->string('channel_secret'); // encrypt
            $table->tinyInteger('qrcode_sent_count')->unsigned()->default(0)->index();
            $table->tinyInteger('followers_count')->unsigned()->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('line_bots');
    }
}