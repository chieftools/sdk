<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (config('database.requires_primary_key')) {
            DB::statement('SET SESSION sql_require_primary_key=0');
        }

        Schema::create('team_user', function (Blueprint $table) {
            $table->foreignId('team_id')->references('id')->on('teams')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->timestamps();

            $table->primary(['user_id', 'team_id']);
        });

        if (config('database.requires_primary_key')) {
            DB::statement('SET SESSION sql_require_primary_key=1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
