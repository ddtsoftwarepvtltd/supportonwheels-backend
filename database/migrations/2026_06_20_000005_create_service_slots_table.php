<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('service_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end');
            $table->integer('max_bookings')->default(5);
            $table->integer('booked_count')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->index(['service_id','date','is_available']);
        });
    }
    public function down(): void { Schema::dropIfExists('service_slots'); }
};