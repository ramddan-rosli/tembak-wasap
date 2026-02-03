<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blast_schedules', function (Blueprint $table) {
            $table->unsignedInteger('delay_min')->default(5)->after('media_type');
            $table->unsignedInteger('delay_max')->default(15)->after('delay_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blast_schedules', function (Blueprint $table) {
            $table->dropColumn(['delay_min', 'delay_max']);
        });
    }
};
