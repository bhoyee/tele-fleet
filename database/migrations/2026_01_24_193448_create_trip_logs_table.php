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
        Schema::create('trip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->cascadeOnDelete();
            $table->unsignedInteger('start_mileage');
            $table->unsignedInteger('end_mileage');
            $table->unsignedInteger('distance_traveled')->nullable();
            $table->decimal('fuel_before_trip', 8, 2)->nullable();
            $table->decimal('fuel_after_trip', 8, 2)->nullable();
            $table->decimal('fuel_consumed', 8, 2)->nullable();
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->decimal('trip_duration_hours', 6, 2)->nullable();
            $table->string('driver_name');
            $table->string('driver_license_number');
            $table->string('paper_logbook_ref_number')->nullable();
            $table->text('driver_notes')->nullable();
            $table->foreignId('entered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('log_date');
            $table->text('remarks')->nullable();
            $table->boolean('verified_by_branch_head')->default(false);
            $table->timestamp('branch_head_verified_at')->nullable();
            $table->timestamps();

            $table->unique('trip_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_logs');
    }
};
