<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rangers', function (Blueprint $table) {
            $table->integer('pin_attempts')->default(0)->after('pin');
            $table->timestamp('locked_until')->nullable()->after('pin_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('rangers', function (Blueprint $table) {
            $table->dropColumn(['pin_attempts', 'locked_until']);
        });
    }
};
