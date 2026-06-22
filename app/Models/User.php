<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',        // active, inactive, blocked, deleted
        'user_type',     // super_admin, ops_admin, finance_admin, admin, provider, worker, participant, customer
        'profile_photo',
        'deleted_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'deleted_at'        => 'datetime',
    ];

    // ─── JWT ───────────────────────────────────────────────────
    public function getJWTIdentifier()       { return $this->getKey(); }
    public function getJWTCustomClaims()     { return []; }

    // ─── Profile Relationships ──────────────────────────────────
    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function providerProfile()
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function participantProfile()
    {
        return $this->hasOne(ParticipantProfile::class);
    }

    // Role ke hisab se profile return karo
    public function getProfileAttribute()
    {
        return match($this->user_type) {
            'worker'      => $this->workerProfile,
            'provider'    => $this->providerProfile,
            'participant',
            'customer'    => $this->participantProfile,
            default       => null,
        };
    }

    // ─── Booking Relationships ──────────────────────────────────

    // Customer ke bookings (jab user customer hai)
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    // Provider ke jobs (jab user provider hai)
    public function jobs()
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    // ─── Review Relationships ───────────────────────────────────

    // Customer ne jo reviews diye
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    // Provider ko jo reviews mile
    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    // ─── Payout Relationship (Provider only) ───────────────────
    public function payouts()
    {
        return $this->hasMany(Payout::class, 'provider_id');
    }

    // ─── Address Relationship (Customer only) ──────────────────
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
