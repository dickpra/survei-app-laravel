<?php

namespace App\Filament\Admin\Resources\SurveyResource\Pages;

use App\Filament\Admin\Resources\SurveyResource;
use App\Models\Survey;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ViewSurveyResults extends Page
{
    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.admin.resources.survey-resource.pages.view-survey-results';

    // Properti publik untuk menampung data
    public Survey $record;
    public $totalResponses = 0;
    public $results = [];

    public function mount(): void
    {
        // TIDAK ADA PENGECEKAN KEPEMILIKAN DI SINI, KARENA ADMIN BOLEH MELIHAT SEMUA
        $this->totalResponses = $this->record->responses()->count();
        $this->processResults();
    }

    protected function processResults(): void
{
    $allResponses = $this->record->responses()->pluck('answers');
    $processedResults = [];

    foreach ($this->record->questionnaireTemplate->content_blocks as $block) {
        if ($block['type'] !== 'section_block') continue;

        foreach ($block['data']['questions'] as $question) {
            $questionContent = $question['content'];
            $questionType = $question['type'];
            $tempAnswers = [];

            foreach ($allResponses as $responseAnswers) {
                if (isset($responseAnswers[$questionContent])) {
                    $tempAnswers[] = $responseAnswers[$questionContent];
                }
            }
            
            if ($questionType === 'isian pendek') {
                $processedResults[$questionContent] = [
                    'type' => 'isian pendek',
                    'content' => $questionContent,
                    'answers' => $tempAnswers,
                ];
            } elseif ($questionType === 'skala likert') {
                $likertResult = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
                $answerCounts = array_count_values($tempAnswers);

                foreach ($answerCounts as $value => $count) {
                    // PERBAIKAN: Ubah $value menjadi string saat pengecekan
                    if (isset($likertResult[(string)$value])) {
                        $likertResult[(string)$value] = $count;
                    }
                }

                $processedResults[$questionContent] = [
                    'type' => 'agregat',
                    'content' => $questionContent,
                    'answers' => $likertResult,
                ];
            } else { // Untuk 'pilihan ganda', 'dropdown', dll.
                $processedResults[$questionContent] = [
                    'type' => 'agregat',
                    'content' => $questionContent,
                    'answers' => array_count_values($tempAnswers),
                ];
            }
        }
    }
    
    $this->results = $processedResults;
}
}