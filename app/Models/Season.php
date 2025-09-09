<?php

// app/Models/Season.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'serie_id',
        'season_number',
        'title',
        'release_date',
    ];

    public function serie()
    {
        return $this->belongsTo(serie::class);
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }
}
