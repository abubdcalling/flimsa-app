<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',  // name or title of the series
        'description',  // brief description or summary
        'release_date',  // optional, date when the series started
        // add other relevant fields you want mass-assignable
    ];

    public function episode()
    {
        return $this->hasMany(Episode::class);
    }

    public function season()
    {
        return $this->hasMany(Season::class);
    }
}
