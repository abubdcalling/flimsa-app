<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'thumbnail'];

    // In Content.php
    public function contents()
    {
        return $this->hasMany(Content::class, 'genre_id');
    }

    protected $table = 'genres';

}
