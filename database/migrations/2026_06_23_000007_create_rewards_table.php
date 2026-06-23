<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->decimal('amount', 10, 2);
            $table->string('currency_code', 3)->default('NGN');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->string('transaction_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('phone_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
