<?php

namespace App\Exports;

use App\Models\Survey;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveyResultsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $survey;
    protected $questions;

    public function __construct(int $surveyId)
    {
        // Ambil data survei beserta pertanyaan-pertanyaannya
        $this->survey = Survey::with('questionnaireTemplate.questions')->find($surveyId);
        $this->questions = $this->survey->questionnaireTemplate->questions;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Ambil semua data respons dari survei ini
        return $this->survey->responses()->with('answers')->get();
    }

    /**
     * Mendefinisikan judul untuk setiap kolom di Excel.
     */
    public function headings(): array
    {
        $headings = [
            'ID Responden',
            'Waktu Mengisi',
        ];

        // Tambahkan setiap pertanyaan sebagai judul kolom
        foreach ($this->questions as $question) {
            $headings[] = $question->content;
        }

        return $headings;
    }

    /**
     * Memetakan data dari setiap respons ke dalam baris Excel.
     * @param mixed $response
     */
    public function map($response): array
    {
        $row = [
            $response->id,
            $response->created_at->format('d-m-Y H:i:s'),
        ];

        // Siapkan jawaban dalam format yang mudah dicari
        $answers = $response->answers->pluck('value', 'question_id');

        // Untuk setiap pertanyaan, cari jawabannya dan masukkan ke baris
        foreach ($this->questions as $question) {
            $row[] = $answers[$question->id] ?? '-'; // Beri tanda '-' jika responden tidak menjawab
        }

        return $row;
    }
}