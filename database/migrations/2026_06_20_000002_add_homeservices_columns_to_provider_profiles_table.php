<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // KYC & Status
            $table->enum('kyc_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('user_id');

            $table->boolean('is_online')->default(false)->after('kyc_status');
            $table->boolean('is_featured')->default(false)->after('is_online');

            // Performance metrics
            $table->decimal('rating', 3, 2)->default(0)->after('is_featured');
            $table->integer('total_jobs')->default(0)->after('rating');
            $table->decimal('acceptance_rate', 5, 2)->default(0)->after('total_jobs');

            // Bank details
            $table->string('bank_account_no')->nullable()->after('acceptance_rate');
            $table->string('bank_ifsc', 11)->nullable()->after('bank_account_no');

            // GPS location
            $table->decimal('last_location_lat', 10, 8)->nullable()->after('bank_ifsc');
            $table->decimal('last_location_lng', 11, 8)->nullable()->after('last_location_lat');
            $table->timestamp('last_location_at')->nullable()->after('last_location_lng');

            // Profile extras
            $table->text('bio')->nullable()->after('last_location_at');
            $table->json('service_ids')->nullable()->after('bio');
            $table->json('weekly_schedule')->nullable()->after('service_ids');
            $table->json('leave_dates')->nullable()->after('weekly_schedule');

            // KYC rejection
            $table->string('rejection_reason')->nullable()->after('leave_dates');

            // KYC document URLs
            $table->string('government_id_url')->nullable()->after('rejection_reason');
            $table->string('police_verification_url')->nullable()->after('government_id_url');
            $table->string('skills_certificate_url')->nullable()->after('police_verification_url');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_status', 'is_online', 'is_featured',
                'rating', 'total_jobs', 'acceptance_rate',
                'bank_account_no', 'bank_ifsc',
                'last_location_lat', 'last_location_lng', 'last_location_at',
                'bio', 'service_ids', 'weekly_schedule', 'leave_dates',
                'rejection_reason',
                'government_id_url', 'police_verification_url', 'skills_certificate_url',
            ]);
        });
    }
};
