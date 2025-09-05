<?php

namespace App\Filament\User\Resources\QuestionnaireTemplateResource\Pages;

use App\Filament\User\Resources\QuestionnaireTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestionnaireTemplate extends CreateRecord
{
    protected static string $resource = QuestionnaireTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set user_id dengan ID pengguna yang sedang login
        $data['user_id'] = auth()->id();
        return $data;
    }
}