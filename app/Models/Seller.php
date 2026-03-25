<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Seller extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'sellers';

    protected $fillable = [
        'name',
        'code',
        'phone',
        'pin_hash',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    public function verifyPin(string $pin): bool
    {
        return \Illuminate\Support\Facades\Hash::check($pin, $this->pin_hash);
    }

    public function setPinAttribute(string $pin): void
    {
        $this->attributes['pin_hash'] = \Illuminate\Support\Facades\Hash::make($pin);
    }
}
