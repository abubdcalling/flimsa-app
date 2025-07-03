<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'serie_id',
        'title',
        'episode_number',
        'duration',
        'release_date',
    ];

    public function serie()
    {
        return $this->belongsTo(Serie::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
