<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;
    protected $fillable = ['plan_name', 'price', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'plan_type');
    }
}
