<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Outbound webhooks: endpoints a team configures, the events the app sent them, a delivery per event and endpoint and
 * every HTTP attempt of a delivery, see `\ChiefTools\SDK\Webhook\Outbound\Webhooks`. Only apps with
 * `chief.webhooks.enabled` run this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', static function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnUpdate()->cascadeOnDelete();

            $table->text('url');
            $table->string('description')->nullable();
            // The event types it receives, `["*"]` for every event
            $table->json('events');
            // Encrypted, signing needs the plain secret
            $table->text('secret');
            // Encrypted, still signs deliveries until it expires so receivers can switch without dropping any
            $table->text('previous_secret')->nullable();
            $table->dateTime('previous_secret_expires_at')->nullable();
            $table->string('status', 16);
            $table->string('disabled_reason', 16)->nullable();
            $table->dateTime('disabled_at')->nullable();
            // The first failed attempt since the last success, null while it succeeds
            $table->dateTime('failing_since')->nullable();
            $table->dateTime('failure_notified_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['team_id', 'status']);
        });

        Schema::create('webhook_events', static function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('type', 64);
            $table->string('api_version', 16);
            // The exact JSON body every delivery sends and signs, so retries and endpoints get the same bytes
            $table->longText('payload');
            $table->dateTime('created_at')->nullable();

            $table->index(['team_id', 'created_at']);
            $table->index(['team_id', 'type', 'created_at']);
        });

        Schema::create('webhook_deliveries', static function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('endpoint_id')->constrained('webhook_endpoints')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUlid('event_id')->constrained('webhook_events')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('status', 16);
            $table->unsignedSmallInteger('attempts')->default(0);
            // When the next attempt is due, also pushed ahead while an attempt is queued so it is not queued twice
            $table->dateTime('next_attempt_at')->nullable();
            $table->dateTime('last_attempted_at')->nullable();
            $table->unsignedSmallInteger('last_response_status')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['endpoint_id', 'created_at']);
            $table->index(['status', 'next_attempt_at']);
        });

        Schema::create('webhook_delivery_attempts', static function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('delivery_id')->constrained('webhook_deliveries')->cascadeOnUpdate()->cascadeOnDelete();
            // Copied from the delivery so the endpoint's graphs need no join
            $table->foreignUlid('endpoint_id')->constrained('webhook_endpoints')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedSmallInteger('number');
            $table->boolean('succeeded');
            $table->json('request_headers');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            // Cut off at `\ChiefTools\SDK\Webhook\Outbound\Deliverer::MAX_RESPONSE_BODY_BYTES`
            $table->text('response_body')->nullable();
            $table->boolean('response_truncated')->default(false);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error', 16)->nullable();
            $table->string('error_message')->nullable();
            $table->dateTime('attempted_at');

            $table->index(['endpoint_id', 'attempted_at']);
        });
    }
};
