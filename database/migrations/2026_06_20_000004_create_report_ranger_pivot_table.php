<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_ranger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ranger_id')->constrained()->cascadeOnDelete();
            $table->timestamp('alerted_at')->useCurrent();
            $table->string('sms_status')->default('sent'); // sent, failed
            $table->string('sms_message_id')->nullable();

            $table->unique(['report_id', 'ranger_id']);
            $table->index('ranger_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_ranger');
    }
};
