<?php

namespace App\Filament\Admin\Pages;

use App\Models\DashboardSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageDashboardSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.admin.manage-dashboard-settings';
    protected static ?int $navigationSort = 1;

    // Grup Navigasi
    public static function getNavigationGroup(): ?string
    {
        return __('Pengaturan');
    }

    // Judul Halaman (title)
    public static function getPluralModelLabel(): string
    {
        return __('Pengaturan Tampilan Dashboard');
    }

    // Label Navigasi (sidebar)
    public static function getNavigationLabel(): string
    {
        return __('Pengaturan Tampilan Dashboard');
    }
    

    public ?array $data = [];
    public DashboardSetting $settings;

    public function mount(): void
    {
        // Ambil baris pertama, atau buat jika belum ada. Ini memastikan selalu ada 1 baris.
        $this->settings = DashboardSetting::firstOrCreate([]);
        $this->form->fill($this->settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Hero/Dashboard Utama'))->schema([
                    TextInput::make('hero_title')->label(__('Judul Utama')),
                    Textarea::make('hero_subtitle')->label(__('Subjudul/Deskripsi')),
                ]),

                Section::make(__('Konten Halaman'))
                    ->description(__('Bangun konten halaman Anda dengan menambahkan dan menyusun blok di bawah ini.'))
                    ->collapsible()
                    ->schema([
                        // Builder untuk Konten "About Me"
                        Builder::make('about_me')
                            ->label(__('Konten About Me'))
                            ->blocks($this->getContentBlocks()) // Memanggil metode untuk blok
                            ->columnSpanFull(),

                        // Builder untuk Konten "Credit"
                        Builder::make('credit')
                            ->label(__('Konten Credit'))
                            ->blocks($this->getContentBlocks())
                            ->columnSpanFull(),
                        
                        // Builder untuk Konten "Guidebook"
                        Builder::make('guidebook')
                            ->label(__('Konten Guidebook'))
                            ->blocks($this->getContentBlocks())
                            ->columnSpanFull(),

                        // Builder untuk Konten "Metodologi"
                        Builder::make('metodologi')
                            ->label(__('Konten Metodologi'))
                            ->blocks($this->getContentBlocks())
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Kontak (Footer)'))->schema([
                    TextInput::make('contact_email')->label(__('Email Kontak'))->email(),
                    TextInput::make('contact_phone')->label(__('Telepon Kontak')),
                ]),
            ])
            ->statePath('data');
    }

    /**
     * Mendefinisikan blok-blok yang bisa digunakan di dalam Builder.
     * Dibuat menjadi metode terpisah agar bisa digunakan kembali.
     */
    protected function getContentBlocks(): array
    {
        return [
            Builder\Block::make('heading')
                ->label(__('Judul'))
                ->icon('heroicon-o-bookmark')
                ->schema([
                    TextInput::make('content')->label(__('Teks Judul'))->required(),
                    Select::make('level')->options(['h2' => __('Besar'), 'h3' => __('Sedang'), 'h4' => __('Kecil')])->default('h2')->required(),
                ]),
            Builder\Block::make('paragraph')
                ->label(__('Paragraf'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    RichEditor::make('content')->label(__('Konten Paragraf'))->required(),
                ]),
            Builder\Block::make('image')
                ->label(__('Gambar'))
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('url')->label(__('Upload Gambar'))->image()->required(),
                    TextInput::make('alt')->label(__('Teks Alternatif (untuk SEO)')),
                ]),

                Builder\Block::make('pdf_document')
                ->label(__('Dokumen PDF'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    FileUpload::make('url')
                        ->label(__('Upload PDF'))
                        ->acceptedFileTypes(['application/pdf']) // Hanya izinkan PDF
                        ->required(),
                    TextInput::make('height')
                        ->label(__('Tinggi Tampilan (opsional)'))
                        ->default('800px')
                        ->helperText(__('Contoh: 800px atau 100%')),
                ]),
            
            // ==== BLOK BARU YANG PINTAR UNTUK SEMUA EMBED ====
            Builder\Block::make('smart_embed')
                ->label(__('Embed Cerdas (YouTube, GDrive, Canva)'))
                ->icon('heroicon-o-link')
                ->schema([
                    TextInput::make('url')
                        ->label(__('URL (YouTube, Google Drive, Canva, dll.)'))
                        ->helperText(__('Cukup tempelkan link "share" dari layanan yang Anda inginkan. Sistem akan mengubahnya secara otomatis.'))
                        ->required(),
                ]),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $this->settings->update($data);

        Notification::make()
            ->title(__('Pengaturan berhasil disimpan'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Simpan Perubahan'))
                ->submit('save'),
        ];
    }
}
