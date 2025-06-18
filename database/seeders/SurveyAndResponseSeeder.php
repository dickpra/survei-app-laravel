<?php

namespace Database\Seeders;

use App\Models\QuestionnaireTemplate;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SurveyAndResponseSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $templates = QuestionnaireTemplate::all();

        // Setiap user akan membuat 2 survei
        foreach ($users as $user) {
            for ($i = 0; $i < 2; $i++) {
                $template = $templates->random(); // Pilih template secara acak
                $survey = Survey::create([
                    'user_id' => $user->id,
                    'questionnaire_template_id' => $template->id,
                    'title' => 'Survei ' . $user->name . ' tentang ' . Str::words($template->title, 2),
                    'unique_code' => 'SURV-' . strtoupper(Str::random(8)),
                    'enforce_single_submission' => fake()->boolean(),
                ]);

                // Buat antara 10 sampai 50 jawaban palsu untuk setiap survei
                $this->createFakeResponses($survey, rand(10, 50));
            }
        }
    }

    private function createFakeResponses(Survey $survey, int $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $answers = [];
            // Loop melalui setiap blok dan pertanyaan di template untuk membuat jawaban
            foreach ($survey->questionnaireTemplate->content_blocks as $block) {
                if ($block['type'] === 'section_block') {
                    foreach ($block['data']['questions'] as $question) {
                        $answers[$question['content']] = $this->generateFakeAnswer($question);
                    }
                }
            }

            $survey->responses()->create([
                'ip_address' => fake()->ipv4(),
                'answers' => $answers,
            ]);
        }
    }

    private function generateFakeAnswer(array $question)
    {
        return match ($question['type']) {
            'pilihan ganda', 'dropdown' => fake()->randomElement($question['options']),
            'skala likert' => fake()->numberBetween(1, 5),
            'isian pendek' => fake()->sentence(3),
            default => '',
        };
    }
}