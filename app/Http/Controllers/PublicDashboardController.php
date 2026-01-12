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

        // =========================================================================
        // [UPDATE] ENGINE STATISTIK MATEMATIKA LENGKAP
        // =========================================================================

        // 1. Fungsi Standar Deviasi
        if (!function_exists('stats_sd')) {
            function stats_sd($array) {
                $n = count($array);
                if ($n <= 1) return 0;
                $mean = array_sum($array) / $n;
                $carry = 0.0;
                foreach ($array as $val) { $carry += pow($val - $mean, 2); }
                return sqrt($carry / ($n - 1));
            }
        }

        // 2. Fungsi Gamma (Diperlukan untuk menghitung P-Value)
        if (!function_exists('stats_gamma_ln')) {
            function stats_gamma_ln($x) {
                $cof = [
                    76.18009172947146, -86.50532032941677, 24.01409824083091,
                    -1.231739572450155, 0.1208650973866179e-2, -0.5395239384953e-5
                ];
                $y = $x; $tmp = $x + 5.5;
                $tmp -= ($x + 0.5) * log($tmp);
                $ser = 1.000000000190015;
                for ($j = 0; $j <= 5; $j++) $ser += $cof[$j] / ++$y;
                return -$tmp + log(2.5066282746310005 * $ser / $x);
            }
        }

        // 3. Fungsi Beta Incomplete (Jantung perhitungan P-Value)
        if (!function_exists('stats_betai')) {
            function stats_betai($a, $b, $x) {
                if ($x < 0.0 || $x > 1.0) return 0.0; // Error domain
                if ($x == 0.0 || $x == 1.0) $bt = 0.0;
                else $bt = exp(stats_gamma_ln($a + $b) - stats_gamma_ln($a) - stats_gamma_ln($b) + $a * log($x) + $b * log(1.0 - $x));
                
                if ($x < ($a + 1.0) / ($a + $b + 2.0)) {
                    // Use continued fraction
                    $m = 1; $eps = 3.0e-7; $qab = $a + $b; $qap = $a + 1.0; $qam = $a - 1.0;
                    $c = 1.0; $d = 1.0 - $qab * $x / $qap;
                    if (abs($d) < 1.0e-30) $d = 1.0e-30;
                    $d = 1.0 / $d; $h = $d;
                    for (; $m <= 100; $m++) {
                        $m2 = 2 * $m; $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
                        $d = 1.0 + $aa * $d; if (abs($d) < 1.0e-30) $d = 1.0e-30;
                        $c = 1.0 + $aa / $c; if (abs($c) < 1.0e-30) $c = 1.0e-30;
                        $d = 1.0 / $d; $h *= $d * $c;
                        $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
                        $d = 1.0 + $aa * $d; if (abs($d) < 1.0e-30) $d = 1.0e-30;
                        $c = 1.0 + $aa / $c; if (abs($c) < 1.0e-30) $c = 1.0e-30;
                        $d = 1.0 / $d; $del = $d * $c; $h *= $del;
                        if (abs($del - 1.0) < $eps) break;
                    }
                    return $bt * $h / $a;
                } else {
                    // Use symmetry
                    // (Copy of logic above but swapped, truncated for brevity in chat, 
                    // usually this branch is rarely hit for low P-values, but essential for correctness)
                    // Simple fallback for high P values to avoid massive code block:
                    return 1.0 - stats_betai($b, $a, 1.0 - $x); // Recursive swap
                }
            }
        }

        // 4. Hitung P-Value dari F (Wrapper)
        if (!function_exists('stats_f_probability')) {
            function stats_f_probability($f, $df1, $df2) {
                if ($f <= 0 || $df1 <= 0 || $df2 <= 0) return 1.0;
                $x = $df2 / ($df2 + $df1 * $f);
                // P-value is getting the area of the tail
                // For F-distribution P(F > f) = Ix(df2/2, df1/2) where x = df2 / (df2 + df1*f)
                return stats_betai($df2 / 2, $df1 / 2, $x);
            }
        }

        // 5. ANOVA Calculation
        if (!function_exists('stats_anova')) {
            function stats_anova($groupedData) {
                $allValues = [];
                foreach ($groupedData as $group) { foreach ($group as $v) $allValues[] = $v; }
                $N = count($allValues); $k = count($groupedData);
                
                if ($N <= $k || $k < 2) return ['f' => 0, 'p' => null]; // Tidak valid

                $grandMean = array_sum($allValues) / $N;
                $ssb = 0; $ssw = 0;

                foreach ($groupedData as $group) {
                    $n_i = count($group);
                    if ($n_i == 0) continue;
                    $mean_i = array_sum($group) / $n_i;
                    $ssb += $n_i * pow($mean_i - $grandMean, 2);
                    foreach ($group as $val) $ssw += pow($val - $mean_i, 2);
                }

                $df1 = $k - 1;       // df between
                $df2 = $N - $k;      // df within
                $msb = $ssb / $df1;
                $msw = ($df2 > 0) ? ($ssw / $df2) : 0;

                if ($msw == 0) return ['f' => 0, 'p' => null];
                
                $f = $msb / $msw;
                
                // Panggil fungsi matematika baru kita
                $p = stats_f_probability($f, $df1, $df2);

                return ['f' => $f, 'p' => $p];
            }
        }

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