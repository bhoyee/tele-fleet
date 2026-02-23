<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('drivers', 'uuid')) {
            Schema::table('drivers', function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }

        if (Schema::hasColumn('drivers', 'uuid')) {
            DB::table('drivers')
                ->whereNull('uuid')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('drivers')
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('drivers', 'uuid')) {
            Schema::table('drivers', function (Blueprint $table): void {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};

