<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'portal_user_id',
        'seller_id',
        'seller_code',
        'buyer_type',
        'email',
        'phone',
        'organization_name',
        'event_slug',
        'event_title',
        'date_label',
        'time_label',
        'location_label',
        'ticket_type_id',
        'ticket_type_label',
        'pricing_round_key',
        'pricing_round_label',
        'ticket_holder_name',
        'buyer_name',
        'quantity',
        'currency',
        'unit_price',
        'total',
        'status',
        'used_at',
        'source',
        'synced',
        'email_sent',
        'ticket_code',
        'qr_path',
        'pass_path',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'used_at' => 'datetime',
            'synced' => 'boolean',
            'email_sent' => 'boolean',
        ];
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
