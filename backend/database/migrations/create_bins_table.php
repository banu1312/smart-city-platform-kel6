<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');
            $table->string('location', 255);
            $table->float('capacity_kg')->default(100.0);
            $table->enum('status', ['active','full','damaged','inactive'])->default('active');
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamps();

            $table->index('zone_id');
            $table->index('status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bins');
    }
};