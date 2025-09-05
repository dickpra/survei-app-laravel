<?php

namespace App\Filament\User\Resources\QuestionnaireTemplateResource\Pages;

use App\Filament\User\Resources\QuestionnaireTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListQuestionnaireTemplates extends ListRecords
{
    protected static string $resource = QuestionnaireTemplateResource::class;

    /**
     * [FIX] Menambahkan tombol "Create" di header halaman.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Kueri ini memfilter data yang tampil di tabel.
     */
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Logika ini sudah benar: Admin melihat semua, creator melihat miliknya saja.
        if (Auth::guard('admin')->check()) {
            return $query;
        }

        if ($user = Auth::guard('user')->user()) {
            if ($user->is_instrument_creator) {
                $query->where('user_id', $user->id);
            }
        }
        
        return $query;
    }
}