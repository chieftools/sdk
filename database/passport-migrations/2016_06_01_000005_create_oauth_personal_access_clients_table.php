<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOauthPersonalAccessClientsTable extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_personal_access_clients', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_personal_access_clients');
    }
}
