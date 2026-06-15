<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('citizen_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->enum('status', ['pending','in_progress','resolved'])->default('pending');
            $table->timestamps();

            $table->index('zone_id');
            $table->index('status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('citizen_reports');
    }
};