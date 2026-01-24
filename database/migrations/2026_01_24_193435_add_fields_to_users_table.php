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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', ['super_admin', 'fleet_manager', 'branch_head', 'branch_admin'])
                ->default('branch_admin')
                ->after('password');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
            $table->string('avatar')->nullable()->after('branch_id');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'phone',
                'role',
                'branch_id',
                'avatar',
                'status',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
