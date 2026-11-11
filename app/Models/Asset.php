<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'asset_code',
    'name',
    'category_id',
    'serial_number',
    'brand',
    'model',
    'purchase_date',
    'purchase_price',
    'status',
    'description',
    'parent_id',
])]
class Asset extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Asset::class, 'parent_id');
    }
}
