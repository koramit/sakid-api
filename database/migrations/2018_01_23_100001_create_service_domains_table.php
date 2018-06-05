<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_domains', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->primary('id');
            $table->string('token');
            $table->string('secret');
            $table->string('name')->index();
            $table->string('url')->nullable();
            $table->string('email');
            $table->string('email_sender');
            $table->string('line_follow_message', 1024)->nullable();
            $table->string('line_greeting_message', 1024)->nullable();
            $table->string('line_reply_unverified', 1024)->nullable();
            $table->string('callback_url')->nullable();
            $table->string('callback_token')->nullable();
            $table->string('callback_secret')->nullable();
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
        Schema::dropIfExists('service_domains');
    }
}
