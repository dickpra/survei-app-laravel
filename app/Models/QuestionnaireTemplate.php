<?php
// app/Models/QuestionnaireTemplate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'content_blocks'];

    // Cast kolom JSON menjadi array agar mudah diolah
    protected $casts = [
        'content_blocks' => 'array',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function surveys(): HasMany { return $this->hasMany(Survey::class); }

}