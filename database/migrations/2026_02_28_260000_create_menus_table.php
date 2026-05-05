<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable()->comment('Optional tenant/site scope');
            $table->string('name');
            $table->string('location', 32)->default('primary')->comment('primary, footer, mobile, topbar');
            $table->timestamps();
            $table->unique(['site_id', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
