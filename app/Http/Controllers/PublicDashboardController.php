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

    public function show(Survey $survey)
    {
        // Security: Ensure only public surveys can be viewed
        if (!$survey->is_public) {
            abort(404);
        }

        // Create a temporary instance of the Filament Page to process results
        $resultsProcessor = new ViewSurveyResults();
        $resultsProcessor->record = $survey; // Manually set the survey record
        $resultsProcessor->mount(); // Run the mount method to process data

        // The processed results are now available in the 'results' property
        $results = $resultsProcessor->results;
        $totalResponses = $resultsProcessor->totalResponses;

        // Prepare filter options
        $filters = [];
        if (isset($results['demographic'])) {
            foreach ($results['demographic'] as $question => $result) {
                if ($result['type'] === 'aggregate') {
                    $filters[$question] = collect($result['answers'])->keys()->all();
                }
            }
        }

        return view('public-survey-results', [
            'survey' => $survey,
            'results' => $results,
            'totalResponses' => $totalResponses,
            'filters' => $filters,
        ]);
    }
}