<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('trip_requests', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('condition_set_at');
            }
        });

        Schema::table('incident_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('incident_reports', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('resolution_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('trip_requests', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });

        Schema::table('incident_reports', function (Blueprint $table): void {
            if (Schema::hasColumn('incident_reports', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });
    }
};

