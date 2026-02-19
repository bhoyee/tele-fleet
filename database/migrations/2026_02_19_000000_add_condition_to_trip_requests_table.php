<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('trip_requests', 'condition_notes')) {
                $table->text('condition_notes')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('trip_requests', 'condition_set_by_user_id')) {
                $table->foreignId('condition_set_by_user_id')
                    ->nullable()
                    ->after('condition_notes')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('trip_requests', 'condition_set_at')) {
                $table->timestamp('condition_set_at')->nullable()->after('condition_set_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('trip_requests', 'condition_set_by_user_id')) {
                $table->dropConstrainedForeignId('condition_set_by_user_id');
            }
            if (Schema::hasColumn('trip_requests', 'condition_set_at')) {
                $table->dropColumn('condition_set_at');
            }
            if (Schema::hasColumn('trip_requests', 'condition_notes')) {
                $table->dropColumn('condition_notes');
            }
        });
    }
};

