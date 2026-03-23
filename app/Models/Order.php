<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'portal_user_id',
        'buyer_type',
        'email',
        'phone',
        'organization_name',
        'buyer_name',
        'product_slug',
        'product_title',
        'format',
        'quantity',
        'currency',
        'unit_price',
        'total',
        'status',
        'timeline',
        'current_step',
        'payment_status',
        'payment_method',
        'source',
        'download_ready',
        'download_path',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'timeline' => 'array',
            'download_ready' => 'boolean',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class);
    }
}
