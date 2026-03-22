<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('plan_id')->nullable()->after('timezone');
            $table->boolean('plan_discounted')->nullable()->after('plan_id');
        });
    }
};
