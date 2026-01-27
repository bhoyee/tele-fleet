<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            $table->dateTime('assignment_reminder_notified_at')->nullable()->after('reminder_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            $table->dropColumn('assignment_reminder_notified_at');
        });
    }
};
