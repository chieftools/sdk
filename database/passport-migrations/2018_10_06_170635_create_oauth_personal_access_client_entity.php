<?php

use Laravel\Passport\ClientRepository;
use Illuminate\Database\Migrations\Migration;

class CreateOauthPersonalAccessClientEntity extends Migration
{
    public function up(): void
    {
        app(ClientRepository::class)->createPersonalAccessClient(
            null, config('app.title'), config('app.url'),
        );
    }
}
