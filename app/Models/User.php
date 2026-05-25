<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'access_level_id',
        'password',
        'transaction_id',
        'counter_id',
    ];

    public const ACCESS_LEVEL_ADMINISTRATOR = 'Administrator';
    public const ACCESS_LEVEL_STAFF = 'Staff';
    public const ACCESS_LEVEL_GUARD = 'Guard';

    public function accessLevelLibrary()
    {
        return $this->belongsTo(AccessLevel::class, 'access_level_id');
    }

    public function isAdmin(): bool
    {
        return $this->accessLevelLibrary?->code === AccessLevel::CODE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->accessLevelLibrary?->code === AccessLevel::CODE_STAFF;
    }

    public function isGuard(): bool
    {
        return $this->accessLevelLibrary?->code === AccessLevel::CODE_GUARD;
    }

    /**
     * Helper to keep backward compatibility or easy access to role name
     */
    public function getAccessLevelAttribute()
    {
        return $this->accessLevelLibrary?->name;
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
