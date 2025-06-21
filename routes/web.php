<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponseController;
use App\Exports\SurveyResultsExport;
use App\Models\Survey;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\PublicDashboardController; // <-- Tambahkan ini di atas




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [PublicDashboardController::class, 'index'])->name('home');


Route::get('/s/{unique_code}', [ResponseController::class, 'show'])->name('survey.show');
Route::post('/s/{unique_code}', [ResponseController::class, 'store'])->name('survey.store');
// routes/web.php
Route::get('/survei/selesai', function () {
    return view('survey-selesai');
})->name('survey.selesai');

// routes/web.php
Route::get('/survei/sudah-mengisi', function () {
    return view('survey-ditolak');
})->name('survey.ditolak');

Route::middleware('auth')->group(function () {
    Route::get('/surveys/{survey}/export', function (Survey $survey) {
        // Otorisasi: Pastikan user yang meminta adalah pemilik survei
        if (auth()->id() !== $survey->user_id) {
            abort(403, 'Unauthorized action.');
        }

        return Excel::download(new SurveyResultsExport($survey->id), 'hasil-'.$survey->unique_code.'.xlsx');
    })->name('surveys.export');
});