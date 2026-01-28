<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE vehicles SET deleted_at = NULL WHERE deleted_at = '0000-00-00 00:00:00'");

        $constraint = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'vehicles'
              AND COLUMN_NAME = 'branch_id'
              AND REFERENCED_TABLE_NAME = 'branches'
            LIMIT 1
        ");

        if ($constraint && $constraint->name) {
            DB::statement(sprintf(
                'ALTER TABLE vehicles DROP FOREIGN KEY %s',
                $constraint->name
            ));
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unsignedBigInteger('branch_id')->nullable()->change();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();

            $table->unsignedInteger('last_maintenance_mileage')->default(0)->after('current_mileage');
            $table->string('maintenance_state', 15)->default('ok')->after('status');
            $table->timestamp('maintenance_due_notified_at')->nullable()->after('maintenance_state');
            $table->timestamp('maintenance_overdue_notified_at')->nullable()->after('maintenance_due_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();

            $table->dropColumn([
                'last_maintenance_mileage',
                'maintenance_state',
                'maintenance_due_notified_at',
                'maintenance_overdue_notified_at',
            ]);
        });
    }
};
