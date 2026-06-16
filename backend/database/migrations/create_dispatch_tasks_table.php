<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dispatch_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('truck_id');
            $table->unsignedBigInteger('bin_id');
            $table->unsignedBigInteger('zone_id');
            $table->enum('priority', ['normal','urgent','emergency'])->default('normal');
            $table->enum('status', ['assigned','on_the_way','arrived','completed','cancelled'])->default('assigned');
            $table->enum('triggered_by', ['manual','ai','schedule'])->default('manual');
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('truck_id');
            $table->index('bin_id');
            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void {
        Schema::dropIfExists('dispatch_tasks');
    }
};