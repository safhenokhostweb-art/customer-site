<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media Library 2.0 — the existing customer_media schema has title/alt_text/
 * caption but no separate "description" field the commercial UX requires.
 * Smallest typed addition; no other structural change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customer_media', 'description')) {
            Schema::table('customer_media', function (Blueprint $table) {
                $table->text('description')->nullable()->after('caption');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_media', 'description')) {
            Schema::table('customer_media', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
