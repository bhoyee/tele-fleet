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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('license_number')->unique();
            $table->string('license_type')->nullable();
            $table->date('license_expiry');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
