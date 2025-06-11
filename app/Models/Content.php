<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;  // <-- added
use Illuminate\Database\Eloquent\Relations\HasMany;    // <-- added

class Content extends Model
{
    use HasFactory;
    protected $fillable = [
        'video1', 'title', 'description', 'publish',
        'schedule', 'genre_id', 'image'
    ];

    public function genres(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
