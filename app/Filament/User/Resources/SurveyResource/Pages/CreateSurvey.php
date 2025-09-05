<?php

namespace App\Filament\User\Resources\SurveyResource\Pages;

use App\Filament\User\Resources\SurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str; // Penting untuk menambahkan ini

class CreateSurvey extends CreateRecord
{
    protected static string $resource = SurveyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['unique_code'] = 'SURV-' . strtoupper(Str::random(8)); // Kita beri prefix beda untuk tes

        // Jika layar ini muncul, berarti file inilah yang dieksekusi.
        // Ini adalah tes paling penting.
        // dd('DEBUG DARI FILE CreateSurvey.php', $data, auth()->user());

        return $data;
    }
}
