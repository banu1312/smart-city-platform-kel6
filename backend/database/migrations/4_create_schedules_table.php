<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trash_bin_id');
            $table->foreignId('truck_id')->constrained('trucks')->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->enum('priority_level', ['Low', 'Medium', 'Urgent', 'Critical'])->default('Medium');
            $table->enum('execution_status', ['Pending', 'In-Progress', 'Completed'])->default('Pending');
            $table->float('estimated_hours_full')->nullable();
            $table->timestamps();

            $table->index('trash_bin_id');
            $table->index('truck_id');
            $table->index('priority_level');
            $table->index('execution_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('schedules');
    }
};