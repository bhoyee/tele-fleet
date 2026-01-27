<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_availability_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->unsignedInteger('total_vehicles');
            $table->unsignedInteger('available_vehicles');
            $table->unsignedInteger('maintenance_vehicles');
            $table->unsignedInteger('assigned_vehicles');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_availability_snapshots');
    }
};
