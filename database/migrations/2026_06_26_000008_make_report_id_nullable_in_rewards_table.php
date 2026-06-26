<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->foreignId('report_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Cannot safely revert to non-nullable without data loss
    }
};
