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
        'status',       // active, inactive, blocked
        'user_type',    // super_admin, admin, provider, worker, participant
        'profile_photo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // JWT required
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


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
            'participant' => $this->participantProfile,
            default       => null,
        };
    }
}
