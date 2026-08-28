<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->string('role')->nullable()->after('user_id');
        });

        DB::table('team_user')->whereNull('role')->update([
            'role' => 'owner',
        ]);

        Schema::table('team_user', function (Blueprint $table) {
            $table->string('role')->nullable(false)->change();
            $table->index(['team_id', 'role']);
        });
    }
};
