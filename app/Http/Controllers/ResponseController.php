<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class ResponseController extends Controller
{
    /**
     * Menampilkan halaman survei untuk diisi oleh responden.
     * Termasuk logika untuk mencegah pengisian berulang.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $unique_code
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, string $unique_code)
    {
        // 1. Ambil data survei dari database, jika tidak ada akan error 404.
        $survey = Survey::where('unique_code', $unique_code)->firstOrFail();

        // 2. Jalankan logika pencegahan HANYA JIKA survei ini mengaturnya.
        if ($survey->enforce_single_submission) {
            // 2a. Cek apakah cookie penanda sudah ada di browser responden.
            if (Cookie::get('survey_completed_' . $unique_code)) {
                return redirect()->route('survey.ditolak');
            }

            // 2b. Cek apakah alamat IP responden sudah tercatat di database untuk survei ini.
            $ipAddress = $request->ip();
            $alreadyResponded = $survey->responses()->where('ip_address', $ipAddress)->exists();

            if ($alreadyResponded) {
                return redirect()->route('survey.ditolak');
            }
        }

        // 3. Jika semua pengecekan lolos, tampilkan halaman survei.
        return view('survey', [
            'survey' => $survey->load('questionnaireTemplate.questions'),
        ]);
    }

    /**
     * Menyimpan jawaban yang dikirim oleh responden.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $unique_code
     * @return \Illuminate\Http\RedirectResponse
     */
    // app/Http/Controllers/ResponseController.php

public function store(Request $request, string $unique_code)
{
    // Validasi dasar
    $validated = $request->validate([
        'answers' => 'required|array',
    ]);

    $survey = Survey::where('unique_code', $unique_code)->firstOrFail();
    
    // Cek IP jika perlu
    if ($survey->enforce_single_submission) {
        if ($survey->responses()->where('ip_address', $request->ip())->exists()) {
            return redirect()->route('survey.ditolak');
        }
    }

    // Logika penyimpanan menjadi sangat simpel
    // Kita tidak butuh transaction atau loop lagi
    $survey->responses()->create([
        'ip_address' => $request->ip(),
        'answers' => $validated['answers'], // Langsung simpan semua jawaban ke kolom JSON
    ]);
    
    $cookie = null;
    if ($survey->enforce_single_submission) {
        $cookie = cookie('survey_completed_' . $unique_code, 'true', 525600);
    }

    $redirectResponse = redirect()->route('survey.selesai');
    if ($cookie) {
        $redirectResponse->withCookie($cookie);
    }
    return $redirectResponse;
}
}