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
        Schema::create('trip_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose');
            $table->string('destination');
            $table->date('trip_date');
            $table->decimal('estimated_distance_km', 8, 2)->nullable();
            $table->unsignedSmallInteger('number_of_passengers')->default(1);
            $table->text('additional_notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'assigned', 'completed', 'cancelled'])
                ->default('pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->foreignId('logbook_entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logbook_entered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index('trip_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_requests');
    }
};
