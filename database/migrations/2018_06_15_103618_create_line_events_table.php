<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLineEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_events', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->primary('id');
            $table->integer('line_bot_id')->unsigned();
            $table->foreign('line_bot_id')->references('id')->on('line_bots');
            $table->text('payload');
            $table->boolean('handleable')->default(false);
            $table->tinyInteger('action_code')->unsigned()->index()->nullable();
            $table->smallInteger('response_code')->unsigned()->index()->nullable();
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
        Schema::dropIfExists('line_events');
    }
}
