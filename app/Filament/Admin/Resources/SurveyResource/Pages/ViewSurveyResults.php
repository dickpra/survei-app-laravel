<?php

namespace App\Filament\Admin\Resources\SurveyResource\Pages;

use App\Filament\Admin\Resources\SurveyResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ViewSurveyResults extends Page
{
    protected static string $resource = SurveyResource::class;
    protected static string $view = 'filament.admin.resources.survey-resource.pages.view-survey-results';

    public Survey $record;
    public int $totalResponses = 0;
    public array $results = [
        'demographic' => [],
        'likert' => [],
    ];

    public function mount(): void
    {
        $this->totalResponses = $this->record->responses()->count();
        if ($this->totalResponses > 0) {
            $this->processResults();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Ekspor ke CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    /**
     * Membuat label Likert otomatis untuk skala 1–3, 1–4, 1–5, 1–6, 1–7
     */
    protected function generateLikertLabels(int $scale, string $manner): array
    {
        $baseLabels = [
            3 => [
                1 => 'Tidak Setuju',
                2 => 'Netral',
                3 => 'Setuju',
            ],
            4 => [
                1 => 'Sangat Tidak Setuju',
                2 => 'Tidak Setuju',
                3 => 'Setuju',
                4 => 'Sangat Setuju',
            ],
            5 => [
                1 => 'Sangat Tidak Setuju',
                2 => 'Tidak Setuju',
                3 => 'Netral',
                4 => 'Setuju',
                5 => 'Sangat Setuju',
            ],
            6 => [
                1 => 'Sangat Tidak Setuju',
                2 => 'Tidak Setuju',
                3 => 'Agak Tidak Setuju',
                4 => 'Agak Setuju',
                5 => 'Setuju',
                6 => 'Sangat Setuju',
            ],
            7 => [
                1 => 'Sangat Tidak Setuju',
                2 => 'Tidak Setuju',
                3 => 'Agak Tidak Setuju',
                4 => 'Netral',
                5 => 'Agak Setuju',
                6 => 'Setuju',
                7 => 'Sangat Setuju',
            ],
        ];

        $labels = $baseLabels[$scale] ?? [];
        // Balik urutan jika manner negative
        if ($manner === 'negative') {
            $reversed = [];
            foreach ($labels as $key => $label) {
                $reversed[$scale + 1 - $key] = $label;
            }
            ksort($reversed);
            return $reversed;
        }
        return $labels;
    }

    protected function processResults(): void
    {
        $allResponses = $this->record->responses()->pluck('answers');
        $template = $this->record->questionnaireTemplate;

        // ---------------- DEMOGRAFIS ----------------
        if (!empty($template->demographic_questions)) {
            foreach ($template->demographic_questions as $index => $question) {
                $questionText = $question['question_text'] ?? "Demografis #".($index + 1);
                $answers = $allResponses->pluck("demographic.{$index}.answer")->filter();

                if ($question['type'] === 'dropdown') {
                // [FIX] Tambahkan ->all() di sini untuk mengubah Collection menjadi array
                $this->results['demographic'][$questionText] = ['type' => 'aggregate', 'answers' => $answers->countBy()->all()];
            } else {
                $this->results['demographic'][$questionText] = ['type' => 'list', 'answers' => $answers->all()];
            }
            }
        }

        // ---------------- LIKERT ----------------
        if (!empty($template->likert_questions)) {
            foreach ($template->likert_questions as $index => $question) {
                $questionText = $question['question_text'] ?? "Pertanyaan #".($index + 1);
                $scale = (int) ($question['likert_scale'] ?? 5);
                $manner = strtolower($question['manner'] ?? 'positive');

                $labels = $this->generateLikertLabels($scale, $manner);

                $answers = $allResponses->pluck("likert.{$index}.answer")
                    ->filter()
                    ->map(fn ($v) => max(1, min($scale, (int)$v)));

                if ($answers->isEmpty()) {
                    $this->results['likert'][$questionText] = null;
                    continue;
                }

                $distribution = collect(range(1, $scale))
                    ->mapWithKeys(fn ($v) => [$v => 0])
                    ->replace($answers->countBy()->all());

                $adjustedScores = $answers->map(fn ($v) => $manner === 'negative' ? ($scale + 1) - $v : $v);

                $this->results['likert'][$questionText] = [
                    'distribution' => $distribution->all(),
                    'labels' => $labels,
                    'average_score' => round($adjustedScores->avg(), 2),
                    'scale' => $scale,
                    'manner' => $manner,
                ];
            }
        }
    }

    public function exportCsv()
        {
            $template = $this->record->questionnaireTemplate;
            $responses = $this->record->responses;

            $demographicQuestions = collect($template->demographic_questions ?? []);
            $likertQuestions = collect($template->likert_questions ?? []);

            // --- [START] PERBAIKAN HEADER ---
            $headers = ['Waktu Mengisi'];
            foreach ($demographicQuestions as $q) {
                $headers[] = $q['question_text'];
            }
            
            // Modifikasi loop ini untuk menambahkan manner
            foreach ($likertQuestions as $q) {
                // Ambil manner, default ke 'positif' jika tidak ada
                $manner = ucfirst($q['manner'] ?? 'positif'); 
                $headers[] = $q['question_text'] . " ({$manner})";
            }
            // --- [END] PERBAIKAN HEADER ---

            $filename = 'hasil-' . $this->record->unique_code . '.csv';

            $callback = function () use ($headers, $responses, $demographicQuestions, $likertQuestions) {
                $file = fopen('php://output', 'w');

                // BOM UTF-8 agar Excel baca dengan benar
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Header
                fputcsv($file, $headers);

                // Baris jawaban (tidak perlu diubah)
                foreach ($responses as $response) {
                    $row = [];
                    $row[] = $response->created_at->format('Y-m-d H:i:s');

                    foreach ($demographicQuestions as $i => $q) {
                        $row[] = $response->answers['demographic'][$i]['answer'] ?? '';
                    }

                    foreach ($likertQuestions as $i => $q) {
                        $row[] = $response->answers['likert'][$i]['answer'] ?? '';
                    }

                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
        }


}
