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
        if (! Schema::hasColumn('vehicles', 'uuid')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }

        if (Schema::hasColumn('vehicles', 'uuid')) {
            DB::table('vehicles')
                ->whereNull('uuid')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('vehicles')
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicles', 'uuid')) {
            Schema::table('vehicles', function (Blueprint $table): void {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};

