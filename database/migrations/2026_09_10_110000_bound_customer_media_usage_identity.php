<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'customer_media_usage_unique';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('customer_media_usages')) {
            return;
        }

        $oversized = DB::table('customer_media_usages')
            ->whereRaw('CHAR_LENGTH(consumer_type) > 64 OR CHAR_LENGTH(reference_path) > 512')
            ->exists();
        if ($oversized) {
            throw new RuntimeException('Cannot bound customer media usage identity: existing values exceed canonical limits.');
        }

        $duplicates = DB::table('customer_media_usages')
            ->select('customer_media_id', 'consumer_type', 'consumer_id', 'reference_path')
            ->groupBy('customer_media_id', 'consumer_type', 'consumer_id', 'reference_path')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($duplicates) {
            throw new RuntimeException('Cannot create customer media usage identity index: duplicate identities exist.');
        }

        $schema = DB::getDatabaseName();
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', $schema)
            ->where('table_name', 'customer_media_usages')
            ->where('index_name', self::INDEX)
            ->exists();

        $columns = DB::select("SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = ? AND table_name = 'customer_media_usages' AND column_name IN ('consumer_type', 'reference_path')", [$schema]);
        $lengths = collect($columns)->pluck('CHARACTER_MAXIMUM_LENGTH', 'COLUMN_NAME');
        if ($indexExists && (int) $lengths->get('consumer_type') === 64 && (int) $lengths->get('reference_path') === 512) {
            return;
        }

        $supportIndex = 'customer_media_usage_media_fk_support';
        if ($indexExists) {
            // MySQL may use the composite unique index as the supporting
            // index for the media FK. Keep the FK valid while replacing it.
            $supportExists = DB::table('information_schema.statistics')
                ->where('table_schema', $schema)
                ->where('table_name', 'customer_media_usages')
                ->where('index_name', $supportIndex)
                ->exists();
            if (! $supportExists) {
                DB::statement("ALTER TABLE customer_media_usages ADD INDEX {$supportIndex} (customer_media_id)");
            }
            DB::statement('ALTER TABLE customer_media_usages DROP INDEX customer_media_usage_unique');
        }
        DB::statement('ALTER TABLE customer_media_usages MODIFY consumer_type VARCHAR(64) NOT NULL, MODIFY reference_path VARCHAR(512) NOT NULL');
        DB::statement('ALTER TABLE customer_media_usages ADD UNIQUE INDEX customer_media_usage_unique (customer_media_id, consumer_type, consumer_id, reference_path)');
        if ($indexExists) {
            DB::statement("ALTER TABLE customer_media_usages DROP INDEX {$supportIndex}");
        }
    }

    public function down(): void
    {
        // Bounds are canonical and widening would restore a MySQL-invalid key.
    }
};
