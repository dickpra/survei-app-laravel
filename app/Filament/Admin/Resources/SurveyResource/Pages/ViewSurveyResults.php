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
     * Label Likert standar (SELALU urutan 1..N). Tidak pernah dibalik oleh manner.
     */
    protected function generateLikertLabels(int $scale): array
    {
        $map = [
            3 => [1 => 'Disagree', 2 => 'Neutral', 3 => 'Agree'],
            4 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Agree', 4 => 'Strongly Agree'],
            5 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Neutral', 4 => 'Agree', 5 => 'Strongly Agree'],
            6 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Somewhat Disagree', 4 => 'Somewhat Agree', 5 => 'Agree', 6 => 'Strongly Agree'],
            7 => [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Somewhat Disagree', 4 => 'Neutral', 5 => 'Somewhat Agree', 6 => 'Agree', 7 => 'Strongly Agree'],
        ];

        if (isset($map[$scale])) return $map[$scale];

        // fallback label generik
        $labels = [];
        for ($i = 1; $i <= $scale; $i++) $labels[$i] = "Skor $i";
        return $labels;
    }

    protected function processResults(): void
    {
        $allResponses = $this->record->responses()->pluck('answers'); // Collection of arrays
        $template = $this->record->questionnaireTemplate;

        // ---------------- DEMOGRAFIS ----------------
        if (!empty($template->demographic_questions)) {
            foreach ($template->demographic_questions as $index => $question) {
                $questionText = $question['question_text'] ?? "Demografis #".($index + 1);
                $answers = $allResponses->pluck("demographic.{$index}.answer")->filter();

                if (($question['type'] ?? null) === 'dropdown') {
                    $this->results['demographic'][$questionText] = [
                        'type' => 'aggregate',
                        'answers' => $answers->countBy()->all(),
                    ];
                } else {
                    $this->results['demographic'][$questionText] = [
                        'type' => 'list',
                        'answers' => $answers->all(),
                    ];
                }
            }
        }

        // ---------------- LIKERT ----------------
        if (!empty($template->likert_questions)) {
            foreach ($template->likert_questions as $index => $question) {
                $questionText = $question['question_text'] ?? "Pertanyaan #".($index + 1);
                $scale  = max(1, (int) ($question['likert_scale'] ?? 5));
                $manner = strtolower($question['manner'] ?? 'positive');

                $labels = $this->generateLikertLabels($scale);

                // Kumpulkan nilai per respons + guard _flipped (kompatibel data lama & baru)
                $values = $this->record->responses->map(function ($resp) use ($index, $scale, $manner) {
                    $node = data_get($resp->answers, "likert.$index", []);
                    if ($node === [] || !isset($node['answer'])) return null;

                    $v = (int) $node['answer'];
                    $v = max(1, min($scale, $v));

                    $flippedFlag = (int) ($node['_flipped'] ?? 0);
                    if ($manner === 'negative' && $flippedFlag !== 1) {
                        // data lama: balik di backend
                        $v = ($scale + 1) - $v;
                    }

                    return $v;
                })->filter(); // buang null

                if ($values->isEmpty()) {
                    $this->results['likert'][$questionText] = null;
                    continue;
                }

                // Distribusi dengan kerangka lengkap 1..scale
                $base = array_fill_keys(range(1, $scale), 0);
                $distCounts = $values->countBy()->all();
                $distribution = $base;
                foreach ($distCounts as $k => $cnt) {
                    $k = (int) $k;
                    if ($k >= 1 && $k <= $scale) $distribution[$k] += (int) $cnt;
                }

                $this->results['likert'][$questionText] = [
                    'distribution'  => $distribution,                      // lengkap 1..scale (3/4/5/6/7)
                    'labels'        => $labels,                            // selalu “positif” 1..N
                    'average_score' => round($values->avg(), 2),           // nilai sudah disesuaikan
                    'scale'         => $scale,
                    'manner'        => $manner,                            // untuk badge/warna saja
                ];
            }
        }
    }

    public function exportCsv()
    {
        $template  = $this->record->questionnaireTemplate;
        $responses = $this->record->responses;

        $demographicQuestions = collect($template->demographic_questions ?? []);
        $likertQuestions      = collect($template->likert_questions ?? []);

        // Header
        $headers = ['Waktu Mengisi'];
        foreach ($demographicQuestions as $q) {
            $headers[] = $q['question_text'];
        }
        foreach ($likertQuestions as $q) {
            $m = strtolower($q['manner'] ?? 'positive');
            $mView = $m === 'negative' ? 'Negatif' : 'Positif';
            $headers[] = $q['question_text'] . " ({$mView})";
        }

        $filename = 'hasil-' . $this->record->unique_code . '.csv';

        $callback = function () use ($headers, $responses, $demographicQuestions, $likertQuestions) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $headers);

            foreach ($responses as $response) {
                $row = [];
                $row[] = $response->created_at->format('Y-m-d H:i:s');

                // Demografis
                foreach ($demographicQuestions as $i => $q) {
                    $row[] = data_get($response->answers, "demographic.$i.answer", '');
                }

                // Likert: langsung tulis nilai yang tersimpan (sudah disesuaikan via form / guard backend)
                foreach ($likertQuestions as $i => $q) {
                    $row[] = data_get($response->answers, "likert.$i.answer", '');
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
    }
}
