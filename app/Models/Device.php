<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;  // <-- added
use Illuminate\Database\Eloquent\Relations\HasMany;    // <-- added

class Device extends Model
{
    use HasFactory;
     protected $fillable = ['user_id', 'duration'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
