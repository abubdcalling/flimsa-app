<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',         // required
        'description',   // optional summary
        'release_date',  // optional start date
        'author',        // optional creator/author
    ];

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }
}
