<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_id', 'performed_by', 'title', 'description', 'maintenance_date', 'cost', 'status', 'completed_at'])]
class Maintenance extends Model
{
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}