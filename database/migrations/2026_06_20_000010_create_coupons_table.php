<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->enum('discount_type', ['percentage','flat']);
            $table->decimal('discount_value', 8, 2);
            $table->decimal('max_discount_cap', 8, 2)->nullable();
            $table->decimal('min_order_amount', 8, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('per_user_limit')->nullable()->default(1);
            $table->integer('used_count')->default(0);
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_new_user_only')->default(false);
            $table->timestamps();
        });
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 8, 2);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};