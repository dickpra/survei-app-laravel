<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use App\Filament\User\Resources\SurveyResource\Pages\ViewSurveyResults;
use App\Models\QuestionnaireTemplate;
use App\Models\DashboardSetting;
class PublicDashboardController extends Controller
{
    public function index(Request $request)
    {
        $settings = DashboardSetting::first();

        $query = Survey::where('is_public', true)
            ->withCount('responses')
            ->latest('created_at');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('template_id')) {
            $query->where('questionnaire_template_id', $request->template_id);
        }

        $surveys = $query->paginate(6)->withQueryString();

        $publicSurveyTemplateIds = Survey::where('is_public', true)->distinct()->pluck('questionnaire_template_id');
        $templates = QuestionnaireTemplate::whereIn('id', $publicSurveyTemplateIds)->pluck('title','id');

        return view('welcome', [
            'surveys'   => $surveys,
            'templates' => $templates,
            'settings'  => $settings,
        ]);
    }

    // [UPDATE] Tambahkan Request $request ke parameter
    public function show(Survey $survey, Request $request)
    {
        // 1. Security Check
        if (!$survey->is_public) {
            abort(404);
        }

        // =========================================================================
        // A. LOGIC FILTERING (Tidak Berubah)
        // =========================================================================
        $filteredResponses = $survey->responses; 

        if ($request->filled('filters')) {
            $filters = $request->input('filters');
            $filteredResponses = $filteredResponses->filter(function ($response) use ($filters) {
                foreach ($filters as $questionText => $filterValue) {
                    if (empty($filterValue)) continue;
                    $demoAnswers = $response->answers['demographic'] ?? [];
                    $isMatch = false;
                    foreach ($demoAnswers as $key => $item) {
                        $dbQuestion = $item['question_text'] ?? '';
                        $dbAnswer   = $item['answer'] ?? '';
                        if ($dbQuestion === $questionText && $dbAnswer === $filterValue) { $isMatch = true; break; }
                        if (($key === 'origin_country' || str_contains(strtolower($dbQuestion), 'negara')) && 
                            ($questionText === 'Asal Negara' || str_contains(strtolower($questionText), 'negara'))) {
                            if ($dbAnswer === $filterValue) { $isMatch = true; break; }
                        }
                    }
                    if (!$isMatch) return false; 
                }
                return true; 
            });
        }

        // =========================================================================
        // B. HITUNG ULANG DATA GRAFIK ($results) - [UPDATED FIX CHART ISSUE]
        // =========================================================================
        
        $results = [
            'demographic' => [],
            'likert' => []
        ];

        // --- 1. Persiapkan Template Pertanyaan ---
        $template = $survey->questionnaireTemplate;
        $demoQuestions = $template->demographic_questions ?? [];
        
        if(!empty($template->origin_country_question)) {
            array_unshift($demoQuestions, [
                'question_text' => 'Asal Negara', 
                'type' => 'dropdown',
                'is_permanent' => true
            ]);
        }

        // [FIX] Tentukan Tipe Data (Aggregate vs List) di Awal
        foreach ($demoQuestions as $idx => $q) {
            $qTitle = $q['question_text'];
            $qType  = $q['type'] ?? 'isian';
            $isPerm = $q['is_permanent'] ?? false;

            // Jika Dropdown ATAU Negara -> Tipe 'aggregate' (Chart)
            // Jika Isian Singkat -> Tipe 'list' (Text List)
            $dataType = ($isPerm || $qType === 'dropdown') ? 'aggregate' : 'list';

            $results['demographic'][$qTitle] = [
                'type' => $dataType, 
                'answers' => [] // Aggregate: Key=>Count, List: Array of Strings
            ];
        }

        // Setup Likert (Sama seperti sebelumnya)
        $likertQuestions = $template->likert_questions ?? [];
        foreach ($likertQuestions as $idx => $q) {
            $qTitle = $q['question_text'];
            $scale  = (int)($q['likert_scale'] ?? 5);
            $results['likert'][$qTitle] = [
                'scale' => $scale,
                'manner' => $q['manner'] ?? 'positive',
                'labels' => [],
                'distribution' => array_fill(1, $scale, 0),
                'average_score' => 0
            ];
            for($i=1; $i<=$scale; $i++) $results['likert'][$qTitle]['labels'][$i] = "Skor $i";
        }

        // --- 2. Looping Data Terfilter ---
        foreach ($filteredResponses as $resp) {
            $answers = $resp->answers ?? [];

            // a. Hitung Demografi [LOGIC BARU]
            if (isset($answers['demographic'])) {
                foreach ($answers['demographic'] as $key => $val) {
                    $qText = $val['question_text'] ?? '';
                    $ansText = $val['answer'] ?? null;
                    
                    if ($key === 'origin_country') $qText = 'Asal Negara';

                    if ($ansText && isset($results['demographic'][$qText])) {
                        // Cek apakah tipe penyimpanannya Aggregate (Chart) atau List (Teks)
                        if ($results['demographic'][$qText]['type'] === 'aggregate') {
                            // Hitung Jumlah (Key = Jawaban, Value = Count)
                            if (!isset($results['demographic'][$qText]['answers'][$ansText])) {
                                $results['demographic'][$qText]['answers'][$ansText] = 0;
                            }
                            $results['demographic'][$qText]['answers'][$ansText]++;
                        } else {
                            // Simpan Teks Mentah (Array Biasa)
                            $results['demographic'][$qText]['answers'][] = $ansText;
                        }
                    }
                }
            }

            // b. Hitung Likert (Sama seperti sebelumnya)
            if (isset($answers['likert'])) {
                foreach ($answers['likert'] as $val) {
                    $qText = $val['question_text'] ?? '';
                    $score = (int)($val['answer'] ?? 0);
                    if ($score > 0 && isset($results['likert'][$qText])) {
                        if (isset($results['likert'][$qText]['distribution'][$score])) {
                            $results['likert'][$qText]['distribution'][$score]++;
                        }
                    }
                }
            }
        }

        // --- 3. Finalisasi Data Likert ---
        foreach ($results['likert'] as $qKey => &$data) {
            $totalScore = 0; $totalCount = 0;
            foreach ($data['distribution'] as $score => $count) {
                $totalScore += ($score * $count);
                $totalCount += $count;
            }
            $data['average_score'] = $totalCount > 0 ? round($totalScore / $totalCount, 2) : 0;
        }

        // =========================================================================
        // C. SIAPKAN OPSI FILTER SIDEBAR (HANYA DROPDOWN) - (Tidak Berubah)
        // =========================================================================
        $sidebarFilters = [];
        $allResponses = $survey->responses;
        
        foreach ($demoQuestions as $q) {
            $qTitle = $q['question_text'];
            $qType  = $q['type'] ?? 'isian';
            $isPerm = $q['is_permanent'] ?? false;
            
            if (!$isPerm && $qType !== 'dropdown') { continue; } // Skip isian singkat

            $qKey = $isPerm ? 'origin_country' : null;
            $uniqueOptions = [];
            foreach ($allResponses as $resp) {
                $dAnswers = $resp->answers['demographic'] ?? [];
                foreach($dAnswers as $k => $v) {
                    $isTarget = ($v['question_text'] ?? '') === $qTitle;
                    if($qKey === 'origin_country' && ($k === 'origin_country' || str_contains($v['question_text'] ?? '', 'Negara'))) { $isTarget = true; }
                    if ($isTarget && !empty($v['answer'])) { $uniqueOptions[$v['answer']] = true; }
                }
            }
            if(!empty($uniqueOptions)) {
                $sidebarFilters[$qTitle] = array_keys($uniqueOptions);
                sort($sidebarFilters[$qTitle]);
            }
        }

        return view('public-survey-results', [
            'survey'         => $survey,
            'results'        => $results,
            'responses'      => $filteredResponses,
            'totalResponses' => $filteredResponses->count(),
            'filters'        => $sidebarFilters,
        ]);
    }
}