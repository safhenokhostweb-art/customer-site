<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solar calculator module (license key `solar_calculator`).
 *
 *  - solar_calculator_configs:  key => JSON settings (access policy,
 *    company branding, every UI text and image, regions, result cards,
 *    quote lines);
 *  - solar_calculator_formulas: the named constants and formulas the
 *    calculation engine evaluates;
 *  - solar_calculator_leads:    every quotation request with its inputs,
 *    computed specs, itemized quote and the exchange-rate snapshot.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('solar_calculator_configs')) {
            Schema::create('solar_calculator_configs', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('solar_calculator_formulas')) {
            Schema::create('solar_calculator_formulas', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('label');
                $table->string('group', 32)->default('constants');
                $table->text('expression');
                $table->string('unit', 32)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['group', 'sort_order']);
            });
        }

        if (!Schema::hasTable('solar_calculator_leads')) {
            Schema::create('solar_calculator_leads', function (Blueprint $table) {
                $table->id();
                $table->string('public_token', 64)->unique();
                $table->string('quote_number', 32)->unique();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('crm_contact_id')->nullable()->index();
                $table->string('name');
                $table->string('mobile', 20)->index();
                $table->string('email')->nullable();
                $table->string('region_key', 64)->nullable();
                $table->string('system_type', 16);
                $table->json('inputs');
                $table->json('results');
                $table->json('quote');
                $table->json('exchange_rates')->nullable();
                $table->decimal('total_price', 20, 0)->nullable();
                $table->string('status', 20)->default('new')->index();
                $table->text('customer_note')->nullable();
                $table->text('admin_note')->nullable();
                $table->string('ip_hash', 64)->nullable();
                $table->timestamp('quote_viewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_calculator_leads');
        Schema::dropIfExists('solar_calculator_formulas');
        Schema::dropIfExists('solar_calculator_configs');
    }
};
