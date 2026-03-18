<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PortalUser extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'portal_users';

    protected $fillable = [
        'role',
        'name',
        'email',
        'phone',
        'organization_name',
        'headline',
        'notes',
        'verified_at',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'notes' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ticketPurchases(): HasMany
    {
        return $this->hasMany(TicketPurchase::class);
    }

    public function otpChallenges(): HasMany
    {
        return $this->hasMany(PortalOtpChallenge::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function supportsTickets(): bool
    {
        return in_array($this->role, ['individual', 'corporate'], true);
    }
}
