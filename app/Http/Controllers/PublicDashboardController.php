<?php

namespace App\Http\Controllers;

use App\Models\QuestionnaireTemplate;
use App\Models\Response;
use App\Models\Survey;
use Illuminate\Http\Request;

class PublicDashboardController extends Controller
{
    public function index()
    {
        // Ambil data statistik dari database
        $totalTemplates = QuestionnaireTemplate::count();
        $totalSurveys = Survey::count();
        $totalResponses = Response::count();

        // Kirim data ke view 'welcome'
        return view('welcome', [
            'totalTemplates' => $totalTemplates,
            'totalSurveys' => $totalSurveys,
            'totalResponses' => $totalResponses,
        ]);
    }
}