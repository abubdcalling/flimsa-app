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
        'video1',
        'title',
        'description',
        'publish',
        'schedule',
        'genre_id',
        'image',
        'view_count',
        'profile_pic',
        'director_name',
        'duration',
           'type',  // only for episodes
        // optional references
        'series_id',       // belongs to which series
        'season_id',       // belongs to which season
        'episode_id',  // only for episodes
    ];
    protected $casts = [
        'video1' => 'array', // Decode JSON to array on retrieval
    ];

    public function subtitles()
    {
        return $this->hasMany(Subtitle::class);
    }

    public function cover()
    {
        return $this->hasOne(Cover::class);
    }




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

    public function histories()
    {
        return $this->hasMany(History::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function episode()
    {
        return $this->hasOne(Episode::class);
    }
}
