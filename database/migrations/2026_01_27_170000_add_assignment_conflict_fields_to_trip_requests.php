<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            $table->boolean('requires_reassignment')->default(false)->after('status');
            $table->string('assignment_conflict_reason', 190)->nullable()->after('requires_reassignment');
            $table->timestamp('assignment_conflict_at')->nullable()->after('assignment_conflict_reason');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'requires_reassignment',
                'assignment_conflict_reason',
                'assignment_conflict_at',
            ]);
        });
    }
};
