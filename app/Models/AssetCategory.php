<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable(['name', 'description'])]
class AssetCategory extends Model
{
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}