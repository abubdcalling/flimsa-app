<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id', 'season_id', 'episode_number', 'title', 'synopsis',
        'runtime_minutes', 'release_date', 'status', 'content_id','slug',
    ];

        protected $casts = [
        'release_date'    => 'date',
        'runtime_minutes' => 'integer',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    // MAIN table link (your video asset row)
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
