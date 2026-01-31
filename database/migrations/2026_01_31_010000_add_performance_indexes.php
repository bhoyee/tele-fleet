<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        $this->addIndexIfMissing('trip_requests', ['branch_id'], 'trip_requests_branch_id_index', $driver);
        $this->addIndexIfMissing('trip_requests', ['requested_by_user_id'], 'trip_requests_requested_by_user_id_index', $driver);
        $this->addIndexIfMissing('trip_requests', ['status'], 'trip_requests_status_index', $driver);
        $this->addIndexIfMissing('trip_requests', ['trip_date'], 'trip_requests_trip_date_index', $driver);
        $this->addIndexIfMissing('trip_requests', ['assigned_vehicle_id'], 'trip_requests_assigned_vehicle_id_index', $driver);
        $this->addIndexIfMissing('trip_requests', ['assigned_driver_id'], 'trip_requests_assigned_driver_id_index', $driver);

        $this->addIndexIfMissing('incident_reports', ['branch_id'], 'incident_reports_branch_id_index', $driver);
        $this->addIndexIfMissing('incident_reports', ['reported_by_user_id'], 'incident_reports_reported_by_user_id_index', $driver);
        $this->addIndexIfMissing('incident_reports', ['status'], 'incident_reports_status_index', $driver);
        $this->addIndexIfMissing('incident_reports', ['incident_date'], 'incident_reports_incident_date_index', $driver);

        $this->addIndexIfMissing('vehicle_maintenances', ['vehicle_id'], 'vehicle_maintenances_vehicle_id_index', $driver);
        $this->addIndexIfMissing('vehicle_maintenances', ['status'], 'vehicle_maintenances_status_index', $driver);
        $this->addIndexIfMissing('vehicle_maintenances', ['scheduled_for'], 'vehicle_maintenances_scheduled_for_index', $driver);

        $this->addIndexIfMissing('drivers', ['status'], 'drivers_status_index', $driver);
        $this->addIndexIfMissing('drivers', ['license_expiry'], 'drivers_license_expiry_index', $driver);

        $this->addIndexIfMissing('vehicles', ['status'], 'vehicles_status_index', $driver);
        $this->addIndexIfMissing('vehicles', ['maintenance_state'], 'vehicles_maintenance_state_index', $driver);

        $this->addIndexIfMissing('chat_messages', ['chat_conversation_id'], 'chat_messages_conversation_id_index', $driver);
        $this->addIndexIfMissing('chat_messages', ['created_at'], 'chat_messages_created_at_index', $driver);
        $this->addIndexIfMissing('chat_participants', ['chat_conversation_id'], 'chat_participants_conversation_id_index', $driver);
        $this->addIndexIfMissing('chat_participants', ['user_id'], 'chat_participants_user_id_index', $driver);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('trip_requests', 'trip_requests_branch_id_index');
        $this->dropIndexIfExists('trip_requests', 'trip_requests_requested_by_user_id_index');
        $this->dropIndexIfExists('trip_requests', 'trip_requests_status_index');
        $this->dropIndexIfExists('trip_requests', 'trip_requests_trip_date_index');
        $this->dropIndexIfExists('trip_requests', 'trip_requests_assigned_vehicle_id_index');
        $this->dropIndexIfExists('trip_requests', 'trip_requests_assigned_driver_id_index');

        $this->dropIndexIfExists('incident_reports', 'incident_reports_branch_id_index');
        $this->dropIndexIfExists('incident_reports', 'incident_reports_reported_by_user_id_index');
        $this->dropIndexIfExists('incident_reports', 'incident_reports_status_index');
        $this->dropIndexIfExists('incident_reports', 'incident_reports_incident_date_index');

        $this->dropIndexIfExists('vehicle_maintenances', 'vehicle_maintenances_vehicle_id_index');
        $this->dropIndexIfExists('vehicle_maintenances', 'vehicle_maintenances_status_index');
        $this->dropIndexIfExists('vehicle_maintenances', 'vehicle_maintenances_scheduled_for_index');

        $this->dropIndexIfExists('drivers', 'drivers_status_index');
        $this->dropIndexIfExists('drivers', 'drivers_license_expiry_index');

        $this->dropIndexIfExists('vehicles', 'vehicles_status_index');
        $this->dropIndexIfExists('vehicles', 'vehicles_maintenance_state_index');

        $this->dropIndexIfExists('chat_messages', 'chat_messages_conversation_id_index');
        $this->dropIndexIfExists('chat_messages', 'chat_messages_created_at_index');
        $this->dropIndexIfExists('chat_participants', 'chat_participants_conversation_id_index');
        $this->dropIndexIfExists('chat_participants', 'chat_participants_user_id_index');
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName, string $driver): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if ($driver === 'mysql') {
            $exists = DB::selectOne(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $indexName]
            );
            if ($exists) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
            });
        } catch (\Throwable) {
            // Ignore if the index does not exist.
        }
    }
};
