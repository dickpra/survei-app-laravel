<?php

namespace App\Filament\Admin\Resources\QuestionnaireTemplateResource\Pages;

use App\Filament\Admin\Resources\QuestionnaireTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuestionnaireTemplates extends ListRecords
{
    protected static string $resource = QuestionnaireTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
