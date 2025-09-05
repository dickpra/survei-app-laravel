<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser; // <-- TAMBAHKAN INI
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'approved_at', 
        'is_instrument_creator', // Tambahkan ini
        'is_researcher', 
        'requested_creator_at',
        'requested_researcher_at',
    ];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'approved_at' => 'datetime',
        'requested_creator_at' => 'datetime',
        'requested_researcher_at' => 'datetime',
    ];

    public function surveys(): HasMany 
    { 
        return $this->hasMany(Survey::class); 
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Hanya izinkan akses jika 'approved_at' tidak null (sudah disetujui)
        return $this->approved_at !== null;
    }
}