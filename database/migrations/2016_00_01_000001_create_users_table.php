<?php

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

            $table->uuid('id')->primary();
            $table->uuid('chief_id')->unique();

            $table->string('name');
            $table->string('email')->unique();

            $table->string('password', 60);
            $table->rememberToken();

            $table->text('preferences')->nullable();
            $table->string('timezone')->nullable();

            $table->boolean('is_admin')->default(0);
            $table->timestamp('last_login')->nullable();
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
        Schema::drop('users');
    }
}
