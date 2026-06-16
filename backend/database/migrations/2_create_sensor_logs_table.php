<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trash_bin_id')->constrained('trash_bins')->onDelete('cascade');
            $table->float('distance_cm');
            $table->float('methane_ppm')->nullable();
            $table->float('temperature_c')->nullable();
            $table->float('delta_volume')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index('trash_bin_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sensor_logs');
    }
};