<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('amount');          // مثبت = کسب، منفی = مصرف
            $table->integer('balance_after');   // موجودی بعد از این تراکنش (snapshot)
            $table->string('type', 30);         // earn | spend | expire | admin_bonus | admin_deduct
            $table->string('source', 50);       // purchase | subscribe | renew | birthday | referral | admin | checkout_spend
            $table->nullableMorphs('reference'); // reference_id + reference_type (order / subscription)
            $table->string('description')->nullable();
            $table->timestamp('expires_at')->nullable(); // null = هرگز منقضی نمی‌شود
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'expires_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_points');
    }
};
