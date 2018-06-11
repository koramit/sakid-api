<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->primary('id');
            $table->integer('service_domain_id')->unsigned()->index();
            $table->foreign('service_domain_id')->references('id')->on('service_domains');
            $table->string('name');
            $table->string('email')->nullable();
            $table->integer('line_bot_id')->unsigned()->nullable()->index();
            $table->foreign('line_bot_id')->references('id')->on('line_bots');
            $table->string('line_user_id')->nullable()->index();
            $table->string('line_display_name')->nullable();
            $table->string('line_picture_url')->nullable();
            $table->string('line_status_message')->nullable();
            $table->string('line_verify_code',6)->index();
            $table->boolean('line_unfollowed')->default(false);
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
        Schema::dropIfExists('users');
    }
}
