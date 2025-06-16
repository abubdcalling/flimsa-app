<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;  // <-- added
use Illuminate\Database\Eloquent\Relations\HasMany;  // <-- added
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'video1', 'title', 'description', 'publish',
        'schedule', 'genre_id', 'image'
    ];

    // public function genres(): BelongsTo
    // {
    //     return $this->belongsTo(Genre::class);
    // }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likesCount()
    {
        return $this->likes()->count();
    }

    public function genres()
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }
}
