<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('rating');
            $table->text('review_text')->nullable();
            $table->json('photo_urls')->nullable();
            $table->text('provider_response')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reviews'); }
};