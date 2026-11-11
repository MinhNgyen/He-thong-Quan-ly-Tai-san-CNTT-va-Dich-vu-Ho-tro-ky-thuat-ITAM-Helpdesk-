<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['priority', 'name', 'resolution_minutes'])]
class SlaPolicy extends Model
{
}
