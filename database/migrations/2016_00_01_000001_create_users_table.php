<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::drop('users');
    }
}
