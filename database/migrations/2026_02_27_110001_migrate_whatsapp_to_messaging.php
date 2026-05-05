<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('messaging_channels') || !Schema::hasTable('settings')) {
            return;
        }
        $phone = DB::table('settings')->where('key', 'whatsapp_phone')->value('value') ?? '';
        $message = DB::table('settings')->where('key', 'whatsapp_message')->value('value') ?? '';
        $enabled = (bool) (DB::table('settings')->where('key', 'whatsapp_enabled')->value('value') ?? false);
        if ($phone) {
            DB::table('messaging_channels')->insertOrIgnore([
                'platform' => 'whatsapp',
                'phone' => $phone,
                'message' => $message,
                'is_active' => $enabled,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // نمی‌توان به‌راحتی برگرداند
    }
};
