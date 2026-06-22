<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->string('full_address', 500);
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->string('city', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('customer_addresses'); }
};