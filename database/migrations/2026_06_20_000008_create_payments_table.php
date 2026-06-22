<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('gateway_order_id', 100)->nullable();
            $table->string('gateway_payment_id', 100)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('method', ['upi','card','netbanking','wallet','cash']);
            $table->enum('status', ['initiated','success','failed','refunded'])->default('initiated');
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};