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


    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('export')
    //             ->label('Ekspor ke CSV')
    //             ->icon('heroicon-o-document-arrow-down')
    //             ->color('success')
    //             ->action(function () {
    //                 $filename = 'hasil-' . $this->record->unique_code . '.csv';

    //                 $questions = collect($this->record->questionnaireTemplate->content_blocks)
    //                     ->pluck('data.questions')
    //                     ->flatten(1);
                    
    //                 $headers = ['ID Responden', 'Waktu Mengisi'];
    //                 foreach ($questions as $question) {
    //                     if (is_array($question) && isset($question['content'])) {
    //                         $headers[] = $question['content'];
    //                     }
    //                 }

    //                 $responses = $this->record->responses;
    //                 $rows = [];

    //                 // --- PERSIAPAN UNTUK RINGKASAN ---
    //                 // Inisialisasi array untuk menyimpan data statistik (jumlah dan total nilai)
    //                 $summaryData = [];
    //                 foreach ($questions as $question) {
    //                     if ($question['type'] === 'skala likert') {
    //                         $summaryData[$question['content']] = ['sum' => 0, 'count' => 0];
    //                     }
    //                 }

    //                 foreach ($responses as $response) {
    //                     $rowData = [
    //                         $response->id,
    //                         $response->created_at->format('Y-m-d H:i:s'),
    //                     ];
                        
    //                     foreach ($questions as $question) {
    //                         if (is_array($question) && isset($question['content'])) {
    //                             $questionContent = $question['content'];
    //                             $answer = $response->answers[$questionContent] ?? null;
    //                             $rowData[] = $answer;

    //                             // --- UPDATE DATA RINGKASAN ---
    //                             // Jika pertanyaan adalah skala likert dan jawabannya numerik, tambahkan ke total
    //                             if ($question['type'] === 'skala likert' && is_numeric($answer)) {
    //                                 $summaryData[$questionContent]['sum'] += (float)$answer;
    //                                 $summaryData[$questionContent]['count']++;
    //                             }
    //                         }
    //                     }
    //                     $rows[] = $rowData;
    //                 }

    //                 // --- MEMBUAT BARIS RINGKASAN FINAL ---
    //                 $summaryRow = ['Rata-Rata (Average)', '']; // Kolom pertama untuk label
    //                 foreach ($questions as $question) {
    //                     $questionContent = $question['content'];
    //                     if ($question['type'] === 'skala likert') {
    //                         $stats = $summaryData[$questionContent];
    //                         // Hitung rata-rata, hindari pembagian dengan nol
    //                         $average = ($stats['count'] > 0) ? $stats['sum'] / $stats['count'] : 0;
    //                         // Format angka menjadi 2 desimal
    //                         $summaryRow[] = number_format($average, 2);
    //                     } else {
    //                         $summaryRow[] = ''; // Beri sel kosong untuk kolom non-numerik
    //                     }
    //                 }
                    
    //                 $callback = function () use ($headers, $rows, $summaryRow) {
    //                     $file = fopen('php://output', 'w');
    //                     fputcsv($file, $headers); // Tulis header

    //                     foreach ($rows as $row) { // Tulis semua baris data
    //                         fputcsv($file, $row);
    //                     }

    //                     // Tulis baris kosong sebagai pemisah
    //                     fputcsv($file, []); 
    //                     // Tulis baris ringkasan
    //                     fputcsv($file, $summaryRow); 

    //                     fclose($file);
    //                 };

    //                 return response()->streamDownload($callback, $filename);
    //             }),
    //     ];
    // }


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