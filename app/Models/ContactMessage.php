<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'message',
        'status',
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->orderBy('sent_at')->orderBy('created_at');
    }
}
