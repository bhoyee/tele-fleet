<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('logbook_entered_at');
        });

        Schema::table('trip_logs', function (Blueprint $table): void {
            $table->foreignId('edited_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('entered_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('trip_logs', function (Blueprint $table): void {
            $table->dropForeign(['edited_by_user_id']);
            $table->dropColumn('edited_by_user_id');
        });

        Schema::table('trip_requests', function (Blueprint $table): void {
            $table->dropForeign(['updated_by_user_id']);
            $table->dropColumn('updated_by_user_id');
        });
    }
};
