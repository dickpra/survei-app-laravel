<?php

namespace App\Filament\User\Resources\QuestionnaireTemplateResource\Pages;

use App\Filament\User\Resources\QuestionnaireTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestionnaireTemplate extends EditRecord
{
    protected static string $resource = QuestionnaireTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
