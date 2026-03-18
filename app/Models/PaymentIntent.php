<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'purchase_type',
        'purchase_id',
        'buyer_type',
        'email',
        'currency',
        'amount',
        'payment_method',
        'status',
        'lenco_payload',
        'lenco_response',
        'return_path',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'lenco_payload' => 'array',
            'lenco_response' => 'array',
            'verified_at' => 'datetime',
        ];
    }
}
