<?php

namespace App\Models;

use App\Enums\DealStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'unit_id',
        'full_price',
        'deposit_amount',
        'deposit_paid_at',
        'stage',
    ];

    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'full_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'deposit_paid_at' => 'date',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
