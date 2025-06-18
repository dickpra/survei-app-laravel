<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Response extends Model
{
    use HasFactory;

    // Tambahkan 'answers' ke fillable
    protected $fillable = [
        'survey_id',
        'ip_address',
        'answers', 
    ];

    // Beritahu Laravel untuk meng-casting kolom 'answers' sebagai array
    protected $casts = [
        'answers' => 'array',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
    
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}