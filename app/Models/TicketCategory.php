<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'description', 'is_active'])]
class TicketCategory extends Model
{
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}