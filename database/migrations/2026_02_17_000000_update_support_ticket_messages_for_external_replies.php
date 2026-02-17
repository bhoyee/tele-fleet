<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        // Ensure user_id exists (older installs may not have it).
        if (! Schema::hasColumn('support_ticket_messages', 'user_id')) {
            Schema::table('support_ticket_messages', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('support_ticket_id');
            });
        } else {
            Schema::table('support_ticket_messages', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        // Drop any existing FK on user_id (constraint name varies by install).
        try {
            $row = DB::selectOne(
                "SELECT CONSTRAINT_NAME AS name
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'support_ticket_messages'
                   AND COLUMN_NAME = 'user_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );
            if ($row && ! empty($row->name)) {
                DB::statement("ALTER TABLE `support_ticket_messages` DROP FOREIGN KEY `{$row->name}`");
            }
        } catch (\Throwable $exception) {
            // Best-effort; some environments restrict information_schema.
        }

        if (! Schema::hasColumn('support_ticket_messages', 'external_name')) {
            Schema::table('support_ticket_messages', function (Blueprint $table) {
                $table->string('external_name')->nullable()->after('user_id');
            });
        }

        if (! Schema::hasColumn('support_ticket_messages', 'external_email')) {
            Schema::table('support_ticket_messages', function (Blueprint $table) {
                $table->string('external_email')->nullable()->after('external_name');
            });
        }

        // Add FK back as nullOnDelete (optional).
        try {
            $row = DB::selectOne(
                "SELECT CONSTRAINT_NAME AS name
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'support_ticket_messages'
                   AND COLUMN_NAME = 'user_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );
            if (! $row || empty($row->name)) {
                DB::statement(
                    "ALTER TABLE `support_ticket_messages`
                     ADD CONSTRAINT `support_ticket_messages_user_id_foreign`
                     FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL"
                );
            }
        } catch (\Throwable $exception) {
            // Optional FK; ignore if it can't be created.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        // Best-effort rollback: remove external fields and restore FK.
        if (Schema::hasColumn('support_ticket_messages', 'external_email')) {
            Schema::table('support_ticket_messages', function (Blueprint $table) {
                $table->dropColumn('external_email');
            });
        }
        if (Schema::hasColumn('support_ticket_messages', 'external_name')) {
            Schema::table('support_ticket_messages', function (Blueprint $table) {
                $table->dropColumn('external_name');
            });
        }

        if (Schema::hasColumn('support_ticket_messages', 'user_id')) {
            try {
                $row = DB::selectOne(
                    "SELECT CONSTRAINT_NAME AS name
                     FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'support_ticket_messages'
                       AND COLUMN_NAME = 'user_id'
                       AND REFERENCED_TABLE_NAME IS NOT NULL
                     LIMIT 1"
                );
                if ($row && ! empty($row->name)) {
                    DB::statement("ALTER TABLE `support_ticket_messages` DROP FOREIGN KEY `{$row->name}`");
                }
            } catch (\Throwable $exception) {
            }
        }
    }
};
