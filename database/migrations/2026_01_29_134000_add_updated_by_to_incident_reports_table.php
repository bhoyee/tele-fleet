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
        Schema::table('incident_reports', function (Blueprint $table): void {
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->after('closed_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_reports', function (Blueprint $table): void {
            $table->dropForeign(['updated_by_user_id']);
            $table->dropColumn('updated_by_user_id');
        });
    }
};
