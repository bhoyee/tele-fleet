<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('trip_requests', 'attachments')) {
                $table->json('attachments')->nullable()->after('additional_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('trip_requests', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });
    }
};

