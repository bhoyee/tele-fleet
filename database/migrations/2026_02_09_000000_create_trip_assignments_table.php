<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->cascadeOnDelete();

            $table->foreignId('from_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('to_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('from_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('to_driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 1000)->nullable();

            $table->timestamps();

            $table->index(['trip_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};

