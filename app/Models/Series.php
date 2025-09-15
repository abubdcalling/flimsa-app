<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasFactory;
    protected $fillable = ['title','description','release_date','status','slug'];

    public function seasons(): HasMany {
        return $this->hasMany(Season::class);
    }

    public function episodes(): HasMany {
        return $this->hasMany(Episode::class);
    }
}
