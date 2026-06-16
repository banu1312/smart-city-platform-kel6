<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique();
            $table->string('driver_name', 100)->nullable();
            $table->float('capacity_kg')->default(2000.0);
            $table->enum('status', ['available','on_duty','maintenance','offline'])->default('available');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('zone_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('trucks');
    }
};