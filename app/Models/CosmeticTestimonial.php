<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CosmeticTestimonial extends Model
{
    //
    use SoftDeletes;
    protected $fillable=[
        'name',
        'message',
        'photo',
        'rating',
        'cosmetic_id',
    ];

    public function cosmetic(): BelongsTo
    {
        return $this->belongsTo(Cosmetic::class,'cosmetic_id');
    }
}
