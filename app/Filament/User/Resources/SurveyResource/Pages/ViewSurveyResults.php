<?php

namespace App\Filament\User\Resources\SurveyResource\Pages;

use App\Filament\User\Resources\SurveyResource;
use Filament\Resources\Pages\Page;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;
use App\Filament\User\Widgets\SurveyResultsChart; // <-- TAMBAHKAN INI
use Filament\Actions\Action; // <-- TAMBAHKAN INI
use App\Exports\SurveyResultsExport; // <-- TAMBAHKAN INI
use Maatwebsite\Excel\Facades\Excel; // <-- TAMBAHKAN INI

class ViewSurveyResults extends Page
{
    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.user.resources.survey-resource.pages.view-survey-results';

    public Survey $record;
    public $totalResponses = 0;
    public $results = [];

    public function mount(): void
    {
        // Pastikan user hanya bisa melihat survei miliknya
        abort_unless($this->record->user_id === auth()->id(), 403);

        $this->totalResponses = $this->record->responses()->count();
        $this->processResults();

        // dd($this->results);
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Ekspor ke CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $filename = 'hasil-' . $this->record->unique_code . '.csv';
                    $responses = $this->record->responses()->with('answers')->get();

                    $questions = $this->record->questionnaireTemplate->questions;
                    $headers = ['ID Responden'];
                    foreach ($questions as $question) {
                        $headers[] = $question->content;
                    }

                    $rows = [];
                    foreach ($responses as $response) {
                        $row = [$response->id];
                        foreach ($questions as $question) {
                            $answer = $response->answers->where('question_id', $question->id)->first();
                            $row[] = $answer ? $answer->value : '';
                        }
                        $rows[] = $row;
                    }

                    $callback = function () use ($headers, $rows) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $headers);
                        foreach ($rows as $row) {
                            fputcsv($file, $row);
                        }
                        fclose($file);
                    };

                    return response()->streamDownload($callback, $filename, [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => "attachment; filename=\"$filename\"",
                    ]);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Daftarkan widget chart dan kirim data record survei ke dalamnya
            // SurveyResultsChart::make(['record' => $this->record]),
        ];
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