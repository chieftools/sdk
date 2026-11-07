<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** Attempts someone resent by hand are part of the delivery like any other, but do not use up its automatic retries. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_delivery_attempts', static function (Blueprint $table) {
            $table->boolean('manual')->default(false)->after('number');
        });
    }
};
