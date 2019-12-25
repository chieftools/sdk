<?php

use Laravel\Passport\ClientRepository;
use Illuminate\Database\Migrations\Migration;

class CreateOauthPersonalAccessClientEntity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        app(ClientRepository::class)->createPersonalAccessClient(
            null, config('app.title'), config('app.url')
        );
    }
}
