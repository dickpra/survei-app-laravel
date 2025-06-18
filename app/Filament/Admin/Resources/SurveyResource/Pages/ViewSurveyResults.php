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
        // Ambil semua data respons untuk survei ini sekali saja
        $allResponses = $this->record->responses()->pluck('answers');

        // Siapkan array kosong untuk hasil akhir
        $processedResults = [];

        // Loop melalui setiap blok dan pertanyaan dari TEMPLATE survei
        foreach ($this->record->questionnaireTemplate->content_blocks as $block) {
            if ($block['type'] !== 'section_block') continue;

            foreach ($block['data']['questions'] as $question) {
                $questionContent = $question['content'];
                $questionType = $question['type'];
                $tempAnswers = [];

                // Sekarang, loop melalui setiap data respons untuk mengumpulkan jawaban
                // dari pertanyaan saat ini.
                foreach ($allResponses as $responseAnswers) {
                    // Cek apakah jawaban untuk pertanyaan ini ada di dalam respons
                    if (isset($responseAnswers[$questionContent])) {
                        $tempAnswers[] = $responseAnswers[$questionContent];
                    }
                }

                // Setelah semua jawaban terkumpul, proses hasilnya
                if ($questionType === 'isian pendek') {
                    $processedResults[$questionContent] = [
                        'type' => 'isian pendek',
                        'content' => $questionContent,
                        'answers' => $tempAnswers,
                    ];
                } else { // Untuk 'pilihan ganda', 'skala likert', 'dropdown'
                    $processedResults[$questionContent] = [
                        'type' => 'agregat',
                        'content' => $questionContent,
                        // array_count_values() adalah fungsi PHP untuk menghitung frekuensi setiap jawaban
                        'answers' => array_count_values($tempAnswers),
                    ];
                }
            }
        }

        $this->results = $processedResults;
    }
}