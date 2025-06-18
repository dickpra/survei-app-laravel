<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $casts = [
        'options' => 'array', // Memberitahu Laravel untuk mengelola kolom options sebagai array
    ];

    protected $fillable = ['questionnaire_template_id', 'content','section', 'type', 'options'];

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }
}
