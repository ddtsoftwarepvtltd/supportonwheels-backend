<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone'))        $table->string('phone', 20)->nullable()->unique()->after('email');
            if (!Schema::hasColumn('users', 'user_type'))    $table->string('user_type', 30)->default('customer')->after('phone');
            if (!Schema::hasColumn('users', 'status'))       $table->enum('status', ['active','inactive','blocked','deleted'])->default('active')->after('user_type');
            if (!Schema::hasColumn('users', 'profile_photo'))$table->string('profile_photo')->nullable()->after('status');
            if (!Schema::hasColumn('users', 'deleted_at'))   $table->timestamp('deleted_at')->nullable()->after('updated_at');
        });
    }
    public function down(): void {}
};
