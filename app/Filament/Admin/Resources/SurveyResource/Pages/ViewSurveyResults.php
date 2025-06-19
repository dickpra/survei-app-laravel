<?php

namespace App\Filament\Admin\Resources\SurveyResource\Pages;

use App\Filament\Admin\Resources\SurveyResource;
use App\Models\Survey;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;


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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Ekspor ke CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $filename = 'hasil-' . $this->record->unique_code . '.csv';

                    // --- PERUBAHAN 1: Mengambil Header dari JSON ---
                    // Ambil semua pertanyaan dari semua blok dan jadikan satu daftar
                    $questions = collect($this->record->questionnaireTemplate->content_blocks)
                        ->pluck('data.questions')
                        ->flatten(1);
                    
                    // Buat header dari konten pertanyaan
                    $headers = ['ID Responden', 'Waktu Mengisi'];
                    foreach ($questions as $question) {
                        // Pastikan question adalah array dan memiliki 'content'
                        if (is_array($question) && isset($question['content'])) {
                            $headers[] = $question['content'];
                        }
                    }

                    // --- PERUBAHAN 2: Mengambil Data dari JSON ---
                    $responses = $this->record->responses; // Ambil semua response
                    
                    $callback = function () use ($headers, $responses, $questions) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $headers);

                        // Loop melalui setiap baris data response
                        foreach ($responses as $response) {
                            $rowData = [
                                $response->id,
                                $response->created_at->format('Y-m-d H:i:s'),
                            ];
                            
                            // Loop melalui setiap pertanyaan untuk memastikan urutan kolom benar
                            foreach ($questions as $question) {
                                if (is_array($question) && isset($question['content'])) {
                                    $questionContent = $question['content'];
                                    // Ambil jawaban dari kolom JSON 'answers' di response
                                    $rowData[] = $response->answers[$questionContent] ?? '';
                                }
                            }
                            fputcsv($file, $rowData);
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
                $likertResult = [];
                $answerCounts = array_count_values($tempAnswers);

                // --- BAGIAN BARU: Terjemahkan nilai numerik ke label ---
                // Cek apakah ada label custom yang disediakan
                if (!empty($question['options'])) {
                    // Loop melalui label custom untuk memastikan urutan benar
                    foreach ($question['options'] as $index => $labelText) {
                        $numericValue = $index + 1;
                        $likertResult[$labelText] = $answerCounts[$numericValue] ?? 0;
                    }
                } else {
                    // Jika tidak ada label custom, gunakan angka sebagai label
                    for ($i = 1; $i <= 5; $i++) {
                        $likertResult[(string)$i] = $answerCounts[$i] ?? 0;
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