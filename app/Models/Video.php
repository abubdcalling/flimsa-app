<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;  // <-- added
use Illuminate\Database\Eloquent\Relations\HasMany;    // <-- added
class Video extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'device_id', 'content_id',
        'status', 'elapsed_time'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }



    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    
}
