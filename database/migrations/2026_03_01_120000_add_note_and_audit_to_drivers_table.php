<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            if (! Schema::hasColumn('drivers', 'note')) {
                $table->text('note')->nullable()->after('address');
            }
            if (! Schema::hasColumn('drivers', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('drivers', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            if (Schema::hasColumn('drivers', 'updated_by_user_id')) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            }
            if (Schema::hasColumn('drivers', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
            if (Schema::hasColumn('drivers', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};

