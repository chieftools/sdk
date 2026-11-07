<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Deliveries of one endpoint run at the same time and are ordered by when their requests were sent, which often falls
 * within the same second. Endpoint health compares these times, so they keep their microseconds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_delivery_attempts', static function (Blueprint $table) {
            $table->dateTime('attempted_at', 6)->change();
        });

        Schema::table('webhook_endpoints', static function (Blueprint $table) {
            $table->dateTime('failing_since', 6)->nullable()->change();
        });
    }
};
