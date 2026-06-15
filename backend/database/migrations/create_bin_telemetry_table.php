<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bin_telemetry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bin_id');
            $table->unsignedBigInteger('zone_id');
            $table->float('fill_level');
            $table->float('gas_level')->nullable();
            $table->float('temperature')->nullable();
            $table->float('distance_cm')->nullable();
            $table->boolean('is_anomaly')->default(false);
            $table->float('fill_rate_est')->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index('bin_id');
            $table->index('zone_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bin_telemetry');
    }
};