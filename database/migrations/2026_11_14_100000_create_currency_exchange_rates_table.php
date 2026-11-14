<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site-level foreign-currency exchange rates (Toman per one unit), kept
 * up to date manually or by the optional online provider
 * (App\Services\ExchangeRates). Generic on purpose: the solar
 * calculator is its first consumer, not its owner.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('currency_exchange_rates')) {
            return;
        }

        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 64);
            // Toman per 1 unit of the currency; 0 means "not set yet".
            $table->decimal('rate_toman', 20, 4)->default(0);
            $table->string('source', 16)->default('manual');
            $table->boolean('is_active')->default(true);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
