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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('color')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric']);
            $table->string('engine_capacity')->nullable();
            $table->unsignedInteger('current_mileage')->default(0);
            $table->date('insurance_expiry')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->enum('status', ['available', 'in_use', 'maintenance', 'offline'])->default('available');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
