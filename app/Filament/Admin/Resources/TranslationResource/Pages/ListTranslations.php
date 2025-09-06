<?php

namespace App\Filament\Admin\Resources\TranslationResource\Pages;

use App\Filament\Admin\Resources\TranslationResource;
use App\Models\Language;
use App\Models\Translation;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Filament\Forms;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
// Ganti ini dengan library client API terjemahan yang Anda gunakan.
// Contoh ini menggunakan library konseptual.
// Jika menggunakan Google, bisa jadi: use Google\Cloud\Translate\V2\TranslateClient;

class ListTranslations extends ListRecords
{
    protected static string $resource = TranslationResource::class;

    /**
     * Mendefinisikan actions yang akan muncul di header halaman.
     *
     * @return array
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // **GRUP AKSI EXPORT/IMPORT YANG BARU**
            Actions\ActionGroup::make([
                Actions\Action::make('exportCsv')
                    ->label(__('Export ke CSV (Backup)'))
                    ->icon('heroicon-o-table-cells')
                    ->action('exportToCsv'),
                
                Actions\Action::make('importCsv')
                    ->label(__('Import dari CSV (Backup)'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('attachment')
                            ->label(__('File CSV'))
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ])
                    ->action(fn(array $data) => $this->importFromCsv($data['attachment'])),

                // **TOMBOL BARU DITAMBAHKAN DI SINI**
                Actions\Action::make('exportLangToZip')
                    ->label(__('Export Folder Lang (.zip)'))
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->action('exportLangToZip'),
                
                Actions\Action::make('importLangFilesFromZip')
                    ->label(__('Import Folder Lang (.zip)'))
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->modalDescription(__('Upload satu file .zip yang berisi semua file bahasa Anda (e.g., id.json, en.json) untuk memperbarui server.'))
                    ->form([
                        Forms\Components\FileUpload::make('zip_file')
                            ->label(__('File .zip Bahasa'))
                            ->required()
                            ->acceptedFileTypes(['application/zip']),
                    ])
                    ->action(fn(array $data) => $this->importLangFilesFromZip($data['zip_file'])),
            ])->label(__('Import/Export File'))->button()->icon('heroicon-o-document-arrow-down')->color('info'),

            Actions\Action::make('scan')
                ->label(__('Scan Translations'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->action('scanAndSaveTranslations')
                ->requiresConfirmation()
                ->modalHeading(__('Scan for New Translations'))
                ->modalDescription(__('This will scan your app and resource folders for new translation keys. Are you sure?')),

            Actions\Action::make('autoTranslate')
                ->label('Auto Translate')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('target_language')
                        ->label(__('Terjemahkan ke bahasa'))
                        ->options(fn () => \App\Models\Language::query()->pluck('name', 'code'))
                        ->required()
                        ->helperText(__('Sistem akan mendeteksi bahasa sumber terbaik dari setiap key, memperbaiki kolom target jika salah bahasa, lalu menerjemahkan ke target.')),
                ])
                ->action(function (array $data) {
                    $this->runSmartAutoTranslation($data['target_language']);

                    try {
                        \Illuminate\Support\Facades\Artisan::call('translations:sync-files');
                        Notification::make()
                            ->title(__('Sinkronisasi Berhasil'))
                            ->body(__('Semua file terjemahan .json telah diperbarui sesuai data di database.'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Sinkronisasi Gagal'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),



                Actions\Action::make('syncFiles')
                ->label(__('Sinkronkan File JSON'))
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('Sinkronkan File Terjemahan'))
                ->modalDescription(__('Aksi ini akan menimpa semua file di resources/lang/*.json dengan data terbaru dari database. Lanjutkan?'))
                ->action(function () {
                    try {
                        // Jalankan Artisan command yang telah kita buat
                        Artisan::call('translations:sync-files');
                        
                        Notification::make()
                            ->title(__('Sinkronisasi Berhasil'))
                            ->body(__('Semua file terjemahan .json telah diperbarui sesuai data di database.'))
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Sinkronisasi Gagal'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function isPlaceholder(?string $value, string $key): bool
{
    return $value === null || $value === '' || $value === $key;
}


    /**
 * Deteksi bahasa sebuah teks menggunakan Stichoza.
 * Catatan: library mendeteksi saat translate dipanggil. Kita “menerjemahkan” ke en (atau target dummy),
 * lalu ambil sumber terdeteksi via getLastDetectedSource(). Hasil terjemahan diabaikan.
 */
protected function detectLang(\Stichoza\GoogleTranslate\GoogleTranslate $tr, string $text, string $dummyTarget = 'en'): ?string
{
    if (trim($text) === '') {
        return null;
    }
    try {
        $tr->setSource(); // auto-detect
        $tr->setTarget($dummyTarget ?: 'en');
        $tr->translate($text); // trigger detection
        return $tr->getLastDetectedSource(); // e.g. "id", "en", "ja", ...
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Pilih sumber terbaik dari sebuah record translation:
 * 1) Cari nilai yang bahasanya terdeteksi sama dengan kode kolomnya (valid-pair).
 * 2) Kalau tidak ada, ambil nilai non-kosong terpanjang (heuristik).
 */
protected function pickBestSource(\Stichoza\GoogleTranslate\GoogleTranslate $tr, array $textMap, string $targetCode): ?array
{
    $best = null;
    $longestFallback = null;

    foreach ($textMap as $code => $val) {
        if (!is_string($val) || trim($val) === '') continue;

        $detected = $this->detectLang($tr, $val);
        if ($detected && strtolower($detected) === strtolower($code)) {
            // valid pair, prioritas utama
            $best = ['code' => $code, 'text' => $val, 'detected' => $detected];
            break;
        }

        // simpan fallback: nilai terpanjang
        if (!$longestFallback || mb_strlen($val) > mb_strlen($longestFallback['text'])) {
            $longestFallback = ['code' => $code, 'text' => $val, 'detected' => $detected];
        }
    }

    return $best ?: $longestFallback; // bisa null kalau semua kosong
}

/**
 * Smart auto-translate:
 * - Untuk setiap key:
 *   - Jika kolom target sudah benar bahasanya => skip
 *   - Jika kolom target kosong / salah bahasa => cari sumber terbaik (lihat pickBestSource), lalu translate -> target
 *   - Semua berbasis deteksi otomatis Stichoza; tidak butuh default language
 */


/**
 * ini versi gacor run smart
 */
 
public function runSmartAutoTranslation(string $targetLangCode): void
{
    $targetLangCode = strtolower($targetLangCode);

    // Validasi target ada di tabel languages
    $exists = \App\Models\Language::where('code', $targetLangCode)->exists();
    if (!$exists) {
        Notification::make()
            ->title(__('Aksi Dibatalkan'))
            ->body(__('Bahasa tujuan tidak ditemukan di database.'))
            ->danger()->send();
        return;
    }

    $tr = new \Stichoza\GoogleTranslate\GoogleTranslate();
    $tr->setTarget($targetLangCode);

    $query = \App\Models\Translation::query();

    $total = 0;
    $updated = 0;
    $skippedCorrect = 0;
    $skippedNoSource = 0;

    foreach ($query->cursor() as $record) {
        $total++;

        $map = $record->text ?? [];
        $targetText = $map[$targetLangCode] ?? '';

        // Jika sudah ada targetText dan bahasanya sudah cocok => skip
        if (is_string($targetText) && trim($targetText) !== '') {
            $detTarget = $this->detectLang($tr, $targetText, 'en');
            if ($detTarget && strtolower($detTarget) === $targetLangCode) {
                $skippedCorrect++;
                continue;
            }
            // else: salah bahasa, akan ditimpa jika kita punya sumber yang baik
        }

        // Pilih sumber terbaik
        $best = $this->pickBestSource($tr, $map, $targetLangCode);
        if (!$best || trim((string)($best['text'] ?? '')) === '') {
            $skippedNoSource++;
            continue;
        }

        try {
            // Auto-detect source (setSource()) dan terjemahkan ke target
            $tr->setSource(); // auto-detect
            $tr->setTarget($targetLangCode);
            $translated = $tr->translate($best['text']);

            // Simpan
            $map[$targetLangCode] = $translated;
            $record->text = $map;
            $record->save();
            $updated++;
        } catch (\Throwable $e) {
            // Lewati jika gagal translate; bisa ditambahkan logging bila perlu
            continue;
        }
    }

    // Clear cache supaya sinkron file nanti bersih
    \Illuminate\Support\Facades\Artisan::call('cache:clear');

    Notification::make()
        ->title(__('Selesai'))
        ->body(
            "Target: **{$targetLangCode}**\n".
            "Total key diproses: **{$total}**\n".
            "Diperbarui: **{$updated}**\n".
            "Terlewati (sudah benar): **{$skippedCorrect}**\n".
            "Terlewati (tak ada sumber): **{$skippedNoSource}**"
        )
        ->success()
        ->send();
}

/** 
 *  ini versi gacor run auto 2 skip skip translate yang sudah terdeteksi
 * 
*/
    public function runAutoTranslation(string $targetLangCode): void
{
    // Ambil daftar bahasa supaya urutan preferensi sumber konsisten (mis. urutan di DB)
    $languages = Language::pluck('code')->toArray();
    if (!in_array($targetLangCode, $languages, true)) {
        Notification::make()
            ->title(__('Aksi Dibatalkan'))
            ->body(__('Bahasa target tidak ditemukan di daftar bahasa.'))
            ->danger()
            ->send();
        return;
    }

    $records = Translation::query()->cursor();

    // Translator instance (reusable)
    $tr = new GoogleTranslate();
    $translatedCount = 0;
    $movedCount = 0;
    $skippedCount = 0;

    foreach ($records as $translation) {
        $key      = $translation->key;
        $payload  = $translation->text ?? [];

        $targetVal = $payload[$targetLangCode] ?? null;

        // 1) Skip cepat: jika target sudah terdeteksi target language -> skip
        if (!$this->isPlaceholder($targetVal, $key)) {
            try {
                $tr->setSource();                 // auto-detect
                $tr->setTarget($targetLangCode);
                // Panggilan translate memang melakukan request; gunakan hasilnya untuk deteksi
                $test = $tr->translate($targetVal);
                $detected = $tr->getLastDetectedSource();
                // Kalau sudah target & output == input (atau sangat mirip), anggap sudah benar
                if ($detected === $targetLangCode && trim($test) === trim($targetVal)) {
                    $skippedCount++;
                    continue; // sudah benar
                }
            } catch (\Throwable $e) {
                // Lewatkan deteksi gagal dan lanjutkan strategi di bawah
            }
        }

        // 2) Self-heal misplacement: cari di bahasa lain yang isinya ternyata sudah target language
        $movedToTarget = false;
        foreach ($languages as $code) {
            if ($code === $targetLangCode) continue;
            $val = $payload[$code] ?? null;
            if ($this->isPlaceholder($val, $key)) continue;

            try {
                $tr->setSource(); // auto detect
                $tr->setTarget($targetLangCode);
                $probe = $tr->translate($val);
                $detected = $tr->getLastDetectedSource();

                if ($detected === $targetLangCode) {
                    // Nilai ini sebenarnya sudah dalam bahasa target.
                    if ($this->isPlaceholder($targetVal, $key)) {
                        // Pindahkan (copy) ke target kalau target masih kosong/placeholder
                        $payload[$targetLangCode] = $val;
                        $movedToTarget = true;
                        $movedCount++;
                    }
                    break; // tidak perlu cek sumber lain
                }
            } catch (\Throwable $e) {
                // Abaikan kendala probe
            }
        }

        if ($movedToTarget) {
            $translation->text = $payload;
            $translation->save();
            continue; // lanjut record lain
        }

        // 3) Translate jika diperlukan:
        //    pilih sumber pertama yang non-placeholder (berdasarkan urutan languages)
        $sourceVal = null;
        foreach ($languages as $code) {
            $v = $payload[$code] ?? null;
            if (!$this->isPlaceholder($v, $key)) {
                $sourceVal = $v;
                break;
            }
        }

        // Jika tidak ketemu sumber yang valid, lewati
        if ($sourceVal === null) {
            $skippedCount++;
            continue;
        }

        try {
            // Auto detect sumber, target = $targetLangCode
            $tr->setSource(); // detect automatically
            $tr->setTarget($targetLangCode);

            $translated = $tr->translate($sourceVal);
            // Kalau hasil sama dengan placeholder/empty, jangan tulis
            if ($this->isPlaceholder($translated, $key)) {
                $skippedCount++;
                continue;
            }

            $payload[$targetLangCode] = $translated;
            $translation->text = $payload;
            $translation->save();
            $translatedCount++;
        } catch (\Throwable $e) {
            // Lewati record ini jika gagal translate
            $skippedCount++;
        }
    }

    try {
        Artisan::call('cache:clear');
    } catch (\Throwable $e) {}

    Notification::make()
        ->title(__('Selesai'))
        ->body("Translated: {$translatedCount}, Moved: {$movedCount}, Skipped: {$skippedCount}")
        ->success()
        ->send();
}


    /**
     * Fungsi untuk menjalankan proses scan dan menyimpan ke database.
     */
    public function scanAndSaveTranslations(): void
    {
        Notification::make()->title('Scanning started...')->body('The system is now scanning project files.')->info()->send();

        $path = base_path();
        $excludeDirs = ['vendor', 'storage'];
        $pattern = "/(?:__|trans)\(\s*['\"]([^'\"]+)['\"]\s*[\),]/siU";

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->in($path)->exclude($excludeDirs)->name(['*.php', '*.vue', '*.js', '*.blade.php'])->files();

        $allKeys = [];
        foreach ($finder as $file) {
            if (preg_match_all($pattern, $file->getContents(), $matches)) {
                foreach ($matches[1] as $key) {
                    if (str_contains($key, '$') || strlen($key) < 2) continue;
                    $allKeys[] = $key;
                }
            }
        }

        $uniqueKeys = array_unique($allKeys);
        $foundCount = count($uniqueKeys);
        $newlyAdded = 0;

        if ($foundCount > 0) {
            $languages = \App\Models\Language::all();
            if ($languages->isEmpty()) {
                Notification::make()->title('Scan Failed')->body('No languages found. Please add languages first.')->danger()->send();
                return;
            }

            $existingKeysLower = array_map('strtolower', \App\Models\Translation::pluck('key')->all());

            foreach ($uniqueKeys as $key) {
                if (!in_array(strtolower($key), $existingKeysLower)) {
                    $newlyAdded++;
                    $textValues = [];
                    // >>> PERUBAHAN: isi SEMUA bahasa dengan teks yang sama (awalnya = key)
                    foreach ($languages as $language) {
                        $textValues[$language->code] = $key;
                    }
                    \App\Models\Translation::create(['key' => $key, 'text' => $textValues]);
                }
            }
        }

        if ($newlyAdded > 0) {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
        }

        Notification::make()->title('Scan Complete')->body("Found {$foundCount} unique keys. {$newlyAdded} new keys were added to the database.")->success()->send();
    }



    /**
     * Fungsi untuk menerjemahkan key yang kosong secara otomatis.
     */
    // public function runAutoTranslation(string $targetLangCode): void
    // {
    //     $defaultLanguage = Language::where('is_default', true)->first();

    //     if (!$defaultLanguage) {
    //         Notification::make()
    //         ->title(__('Aksi Dibatalkan'))
    //         ->body(__('Anda harus menetapkan satu bahasa sebagai default terlebih dahulu.'))
    //         ->danger()->send();
    //         return;
    //     }

    //     $sourceLangCode = $defaultLanguage->code;
    //     if ($sourceLangCode === $targetLangCode) {
    //         Notification::make()
    //         ->title(__('Aksi Dibatalkan'))
    //         ->body(__('Bahasa sumber dan tujuan tidak boleh sama.'))
    //         ->warning()->send();
    //         return;
    //     }

    //     // Kueri untuk menemukan key yang bisa diproses
    //     $translationsToProcessQuery = Translation::query()
    //         ->whereNotNull("text->{$sourceLangCode}")
    //         ->where("text->{$sourceLangCode}", '!=', '')
    //         ->where(function ($query) use ($targetLangCode) {
    //             $query->whereNull("text->{$targetLangCode}")
    //                   ->orWhere("text->{$targetLangCode}", '');
    //         });

    //     // **== KODE BARU: LAPORAN DETAIL ==**
    //     // Jika kueri tidak menemukan hasil, berikan laporan mendetail.
    //     if ($translationsToProcessQuery->count() === 0) {
    //         $totalKeys = Translation::count();
    //         $keysWithoutSourceText = Translation::query()
    //             ->where(function($q) use ($sourceLangCode) {
    //                 $q->whereNull("text->{$sourceLangCode}")
    //                   ->orWhere("text->{$sourceLangCode}", '');
    //             })
    //             ->count();
            
    //         $alreadyTranslatedCount = Translation::query()
    //             ->whereNotNull("text->{$targetLangCode}")
    //             ->where("text->{$targetLangCode}", '!=', '')
    //             ->count();

    //         $report = "Tidak ada key yang perlu diproses untuk bahasa '{$targetLangCode}'.\n\n" .
    //                   "• Total Key di Database: **{$totalKeys}**\n" .
    //                   "• Key dengan Teks Sumber Kosong: **{$keysWithoutSourceText}**\n" .
    //                   "• Key yang Sudah Diterjemahkan: **{$alreadyTranslatedCount}**\n\n" .
    //                   "**Solusi**: Pastikan teks untuk bahasa default (**{$sourceLangCode}**) sudah diisi sebelum menerjemahkan.";

    //         Notification::make()
    //             ->title(__('Tidak Ada Data Untuk Diproses'))
    //             ->body($report)
    //             ->warning()
    //             ->persistent() // Notifikasi tidak akan hilang sampai di-klik
    //             ->send();
    //         return;
    //     }
        
    //     // Lanjutkan proses jika ada data
    //     Notification::make()
    //     ->title(__('Proses Dimulai'))
    //     ->body(__("Menerjemahkan dari {$sourceLangCode} ke {$targetLangCode}..."))->info()->send();

    //     try {
    //         $tr = new GoogleTranslate();
    //         $tr->setSource($sourceLangCode);
    //         $tr->setTarget($targetLangCode);

    //         $translatedCount = 0;
    //         // Gunakan cursor pada kueri yang sudah kita buat
    //         foreach ($translationsToProcessQuery->cursor() as $translation) {
    //             $sourceText = $translation->text[$sourceLangCode];
    //             $translatedText = $tr->translate($sourceText);
    //             $currentText = $translation->text;
    //             $currentText[$targetLangCode] = $translatedText;
    //             $translation->text = $currentText;
    //             $translation->save();
    //             $translatedCount++;
    //         }

    //         if ($translatedCount > 0) {
    //             Artisan::call('cache:clear');
    //             Notification::make()
    //             ->title(__('Sukses'))
    //             ->body(__("{$translatedCount} key berhasil diterjemahkan ke bahasa '{$targetLangCode}'."))->success()->send();
    //         }

    //     } catch (\Exception $e) {
    //         Notification::make()
    //         ->title('Terjadi Error')
    //         ->body(__("Gagal menerjemahkan: ") . $e->getMessage())->danger()->send();
    //     }
    // }

    public function exportToCsv(): StreamedResponse
    {
        $fileName = 'translations-' . now()->format('Y-m-d') . '.csv';
        $languages = Language::all();
        $translations = Translation::all();

        // Buat header dinamis berdasarkan bahasa yang ada
        $headers = ['key'];
        foreach ($languages as $language) {
            $headers[] = $language->code;
        }

        return new StreamedResponse(function () use ($translations, $headers, $languages) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers); // Tulis header

            // Tulis setiap baris data
            foreach ($translations as $translation) {
                $row = [$translation->key];
                foreach ($languages as $language) {
                    $row[] = $translation->text[$language->code] ?? '';
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Import CSV ke tabel `translations`.
     *
     * @param  UploadedFile|TemporaryUploadedFile|string  $file
     */
    protected function importFromCsv($file): void
{
    try {
        // 1) Tentukan path fisik
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $cleanup = fn() => $file->delete();
        } elseif (is_string($file)) {
            // coba di disk lokal
            if (Storage::disk('local')->exists($file)) {
                $path = Storage::disk('local')->path($file);
                $cleanup = fn() => Storage::disk('local')->delete($file);
            }
            // jika tidak, coba di disk public
            elseif (Storage::disk('public')->exists($file)) {
                $path = Storage::disk('public')->path($file);
                $cleanup = fn() => Storage::disk('public')->delete($file);
            } else {
                throw new \Exception("File CSV tidak ditemukan: {$file}");
            }
        } else {
            throw new \Exception('Tipe file tidak didukung.');
        }

        if (! file_exists($path)) {
            throw new \Exception("Path file tidak ada: {$path}");
        }

        // 2) Buka CSV
        if (! $handle = fopen($path, 'r')) {
            throw new \Exception("Gagal membuka file CSV: {$path}");
        }

        // 3) Baca header
        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw new \Exception('Header CSV kosong atau tidak valid.');
        }

        // 4) Proses baris demi baris
        $importedCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row) ?: [];
            if (empty($data['key'])) {
                continue;
            }
            $key = $data['key'];
            unset($data['key']);

            Translation::updateOrCreate(
                ['key'  => $key],
                ['text' => $data]
            );

            $importedCount++;
        }
        fclose($handle);

        // 5) Hapus file
        $cleanup();

        // 6) Notifikasi sukses
        Notification::make()
            ->title('Import Berhasil')
            ->body("{$importedCount} baris berhasil diimpor.")
            ->success()
            ->send();
    } catch (\Exception $e) {
        Notification::make()
            ->title('Import Gagal')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}

public function exportLangToZip()
    {
        try {
            $langPath = lang_path();
            $files = File::files($langPath);

            if (empty($files)) {
                Notification::make()->title('Aksi Dibatalkan')->body('Folder lang tidak berisi file .json untuk diekspor.')->warning()->send();
                return;
            }

            $zip = new ZipArchive();
            $zipFileName = 'lang_files_' . now()->format('Y-m-d') . '.zip';
            // Buat file zip di lokasi temporary
            $tempZipPath = tempnam(sys_get_temp_dir(), 'zip');

            if ($zip->open($tempZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Tidak dapat membuat file .zip.');
            }

            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $zip->addFile($file->getRealPath(), $file->getFilename());
                }
            }
            $zip->close();
            
            // Kirim file zip ke browser dan hapus setelah terkirim
            return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Notification::make()->title('Export Gagal')->body($e->getMessage())->danger()->send();
            return null;
        }
    }

protected function importLangFilesFromZip($file): void
    {
        try {
            // 1) Tentukan path fisik menggunakan logika yang terbukti andal
            $path = null;
            $cleanup = function() {}; // Default cleanup function
            if ($file instanceof UploadedFile) {
                $path = $file->getRealPath();
            } elseif (is_string($file)) {
                if (Storage::disk('local')->exists($file)) {
                    $path = Storage::disk('local')->path($file);
                    $cleanup = fn() => Storage::disk('local')->delete($file);
                } elseif (Storage::disk('public')->exists($file)) {
                    $path = Storage::disk('public')->path($file);
                    $cleanup = fn() => Storage::disk('public')->delete($file);
                } else {
                    throw new \Exception("File .zip tidak ditemukan: {$file}");
                }
            } else {
                throw new \Exception('Tipe file tidak didukung.');
            }

            if (! file_exists($path)) {
                throw new \Exception("Path file tidak ada: {$path}");
            }

            // 2) Buka dan proses file .zip
            $zip = new ZipArchive;
            $res = $zip->open($path);
            $filesUpdated = 0;

            if ($res === TRUE) {
                $langDestinationPath = lang_path();
                if (!File::isDirectory($langDestinationPath)) {
                    File::makeDirectory($langDestinationPath, 0755, true, true);
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fileName = $zip->getNameIndex($i);
                    if (pathinfo($fileName, PATHINFO_EXTENSION) === 'json' && !str_contains($fileName, '/')) {
                        $fileContent = $zip->getFromIndex($i);
                        File::put($langDestinationPath . '/' . $fileName, $fileContent);
                        $filesUpdated++;
                    }
                }
                $zip->close();
            } else {
                throw new \Exception('Gagal membuka file .zip.');
            }

            // 3) Hapus file sementara jika perlu
            $cleanup();

            // 4) Notifikasi sukses
            Notification::make()->title('Update Berhasil')->body("{$filesUpdated} file bahasa berhasil diupdate.")->success()->send();

        } catch (\Exception $e) {
            Notification::make()->title('Update Gagal')->body($e->getMessage())->danger()->send();
        }
    }
    
}
