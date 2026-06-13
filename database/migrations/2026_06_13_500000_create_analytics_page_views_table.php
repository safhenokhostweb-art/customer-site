<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500);
            $table->string('page_title', 255)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('session_id', 64)->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('country', 4)->nullable();
            $table->string('device', 20)->nullable(); // mobile/tablet/desktop
            $table->string('browser', 60)->nullable();
            $table->timestamp('visited_at')->useCurrent()->index();

            $table->index(['path', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_page_views');
    }
};
