<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rangers', function (Blueprint $table) {
            $table->string('pin', 4)->default('0000')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('rangers', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
