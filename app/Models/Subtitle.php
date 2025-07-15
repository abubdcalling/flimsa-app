<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id', 'language', 'file_path'
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    
}
