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
        'ticket_holder_name',
        'buyer_name',
        'quantity',
        'currency',
        'unit_price',
        'total',
        'status',
        'ticket_code',
        'qr_path',
        'pass_path',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }
}
