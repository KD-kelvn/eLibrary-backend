<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_borrowing.penalty_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('borrowing_penalty_id');
            $table->timestamp('payment_date');
            $table->decimal('payment_amount', 12, 2);
            $table->string('payment_method', 40)->default('manual');
            $table->string('payer_mobile', 40)->nullable();
            $table->string('payer_acc', 80)->nullable();
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table
                ->foreign('borrowing_penalty_id')
                ->references('id')
                ->on('book_borrowing.borrowing_penalties')
                ->cascadeOnDelete();

            $table->index('payment_date', 'idx_brrw_pen_payment_date');
            $table->index('payment_method', 'idx_brrw_pen_payment_method');
            $table->index('payer_mobile', 'idx_brrw_pen_payer_mobile');
            $table->index('payer_acc', 'idx_brrw_pen_payer_acc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowing.penalty_payments');
    }
};
