<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messaging_channels', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // whatsapp, eitaa, bale, soroush, gap, igap, rubika
            $table->string('url')->nullable(); // لینک مستقیم یا آیدی
            $table->string('phone')->nullable(); // برای واتساپ
            $table->string('message')->nullable(); // پیام پیش‌فرض واتساپ
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('messaging_channels');
        Schema::enableForeignKeyConstraints();
    }
};
