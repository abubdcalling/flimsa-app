<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cover extends Model
{
    use HasFactory;
    protected $fillable = [
        'content_id',
    ];

    /**
     * Relationship: A cover belongs to one content.
     */
    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
