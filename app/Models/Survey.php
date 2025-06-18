<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * PASTIKAN 'user_id' DAN 'unique_code' ADA DI DALAM ARRAY INI.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'questionnaire_template_id',
        'title',
        'user_id', // <-- INI YANG PALING PENTING
        'unique_code',
        'enforce_single_submission', // <-- TAMBAHKAN INI
    ];

    // ... sisa relasi lainnya ...
    public function user(): BelongsTo 
    { 
        return $this->belongsTo(User::class); 
    }
    public function questionnaireTemplate(): BelongsTo 
    { 
        return $this->belongsTo(QuestionnaireTemplate::class); 
    }
    public function responses(): HasMany 
    { 
        return $this->hasMany(Response::class); 
    }
}