<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'response_id',
        'question_id',
        'value',
    ];

    /**
     * Get the response that this answer belongs to.
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }
    
    /**
     * Get the question that this answer belongs to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}