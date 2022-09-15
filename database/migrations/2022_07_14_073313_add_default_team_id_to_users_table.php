<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_team_id')->nullable()->after('preferences')->references('id')->on('teams')->cascadeOnUpdate()->nullOnDelete();
        });
    }
};
