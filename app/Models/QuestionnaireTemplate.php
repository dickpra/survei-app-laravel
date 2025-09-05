<?php
// app/Models/QuestionnaireTemplate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'demographic_title',
        'demographic_questions',
        'likert_title',
        'likert_questions',
        'user_id', // <-- Tambahkan ini
        'published_at', // <-- Tambahkan ini
    ];

    protected $casts = [
        'demographic_questions' => 'array',
        'likert_questions' => 'array',
        'published_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
    /**
     * Mendefinisikan relasi ke model User (pembuat template).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surveys(): HasMany { return $this->hasMany(Survey::class); }
    
}