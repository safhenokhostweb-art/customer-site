<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // طرح‌های اقساطی (تعریف‌شده توسط ادمین سایت)
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('period_months');          // 3, 6, 12, 24
            $table->unsignedTinyInteger('down_payment_percent');   // 0-100
            $table->decimal('monthly_interest_rate', 5, 2)->default(0); // درصد بهره ماهانه
            $table->unsignedBigInteger('min_amount')->default(0);  // حداقل مبلغ خرید (تومان)
            $table->unsignedBigInteger('max_amount')->default(0);  // 0 = بی‌محدودیت
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // سفارش‌های قسطی
        Schema::create('installment_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable(); // FK to orders
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->foreign('plan_id')->references('id')->on('installment_plans');

            $table->unsignedBigInteger('total_amount');           // مجموع مبلغ کالا
            $table->unsignedBigInteger('down_payment');           // پیش‌پرداخت
            $table->unsignedBigInteger('remaining_amount');       // مانده پس از پیش‌پرداخت
            $table->unsignedBigInteger('monthly_installment');    // مبلغ هر قسط
            $table->unsignedTinyInteger('installments_total');    // تعداد اقساط
            $table->unsignedTinyInteger('installments_paid')->default(0);
            $table->string('status', 20)->default('pending');     // pending|active|completed|defaulted|cancelled
            $table->string('gateway_ref', 100)->nullable();       // مرجع پیش‌پرداخت
            $table->date('start_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // اقساط فردی
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('installment_order_id');
            $table->foreign('installment_order_id')->references('id')->on('installment_orders')->onDelete('cascade');

            $table->unsignedTinyInteger('installment_number'); // شماره قسط (۱، ۲، ...)
            $table->date('due_date');
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('pending');  // pending|paid|overdue|cancelled
            $table->timestamp('paid_at')->nullable();
            $table->string('gateway', 30)->nullable();         // zarinpal|idpay
            $table->string('gateway_ref', 100)->nullable();
            $table->timestamps();

            $table->index(['installment_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_orders');
        Schema::dropIfExists('installment_plans');
    }
};
