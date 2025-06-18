<?php

namespace App\Filament\Admin\Resources\SurveyResource\Pages;

use App\Filament\Admin\Resources\SurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSurveys extends ListRecords
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
