<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'serie_id',
        'season_id',
        'content_id',
        'episode_number',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function serie()
    {
        return $this->season->serie(); // shortcut via season
    }
}
