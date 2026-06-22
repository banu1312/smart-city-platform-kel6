<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trash_bins', function (Blueprint $table) {
            $table->id();
            $table->string('bin_code', 50)->unique();
            $table->float('capacity_liters')->default(100.0);
            $table->float('tinggi')->default(100.0);
            $table->float('current_volume_percentage')->default(0);
            $table->float('methane_gas_level')->default(0);
            $table->float('temperature')->nullable();
            $table->enum('tipe_lokasi', ['Perumahan', 'Pasar', 'Taman'])->default('Perumahan');
            $table->enum('status', ['Active', 'Maintenance', 'Vandalized'])->default('Active');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->timestamp('last_pickup')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('current_volume_percentage');
        });
    }

    public function down(): void {
        Schema::dropIfExists('trash_bins');
    }
};