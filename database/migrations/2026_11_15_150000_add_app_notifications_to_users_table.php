<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Whether the app can notify the user through the Chief Tools app (`enabled` or `muted`), synced from Account Chief.
 * Empty for users without a signed-in Chief Tools app with notifications on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('app_notifications')->nullable();
        });
    }
};
