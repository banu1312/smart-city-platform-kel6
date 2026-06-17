<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sanitation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reporter_name', 100);
            $table->string('reporter_phone', 20)->nullable();
            $table->text('issue_description')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('geo_coordinate', 100)->nullable();
            $table->enum('verification_status', ['Pending', 'Reviewed', 'Dispatched', 'Resolved'])->default('Pending');
            $table->timestamps();

            $table->index('verification_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sanitation_reports');
    }
};