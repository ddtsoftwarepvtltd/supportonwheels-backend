<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('fee', 8, 2)->default(0);
            $table->enum('type', ['weekly','instant','manual'])->default('weekly');
            $table->enum('status', ['pending','processing','completed','failed'])->default('pending');
            $table->string('bank_account')->nullable();
            $table->string('bank_ifsc', 11)->nullable();
            $table->string('gateway_ref')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payouts'); }
};