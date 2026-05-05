<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('mobile');
            $table->text('bio')->nullable()->after('avatar');
            $table->date('birth_date')->nullable()->after('bio');
            $table->string('phone', 30)->nullable()->after('birth_date');
            $table->string('fax', 30)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('fax');
            $table->string('company_name', 255)->nullable()->after('website');
            $table->string('company_role', 100)->nullable()->after('company_name');
            $table->text('company_address')->nullable()->after('company_role');
            $table->string('company_phone', 30)->nullable()->after('company_address');
            $table->string('company_email', 255)->nullable()->after('company_phone');
            $table->json('notification_preferences')->nullable()->after('company_email');
            $table->json('privacy_settings')->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar', 'bio', 'birth_date', 'phone', 'fax', 'website',
                'company_name', 'company_role', 'company_address', 'company_phone', 'company_email',
                'notification_preferences', 'privacy_settings',
            ]);
        });
    }
};
