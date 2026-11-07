<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * A resend someone asked for is due on its own clock, next to the automatic retries in `next_attempt_at`, so waiting
 * for room at its endpoint never moves a scheduled retry and the sweeper can still send it as a resend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', static function (Blueprint $table) {
            // When a resend is due, also pushed ahead while it is queued; null without one
            $table->dateTime('manual_attempt_at')->nullable()->after('next_attempt_at');

            $table->index('manual_attempt_at');
        });
    }
};
