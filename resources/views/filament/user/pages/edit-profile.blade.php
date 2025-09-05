<x-filament-panels::page>

{{-- Form Edit Profile --}}
    <form wire:submit.prevent="updateProfile">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>

    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Pengajuan Peran Tambahan
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Ajukan permohonan untuk mendapatkan akses ke fitur tambahan di platform ini.
            </p>
        </div>

        {{-- Menampilkan status peran yang sudah dimiliki --}}
        <div class="flex flex-col space-y-2">
            @if(auth()->user()->is_researcher)
                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <x-heroicon-s-check-circle class="w-4 h-4" />
                    Akses Researcher Aktif
                </span>
            @endif
             @if(auth()->user()->is_instrument_creator)
                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <x-heroicon-s-check-circle class="w-4 h-4" />
                    Akses Instrument Creator Aktif
                </span>
            @endif
        </div>
        
        {{-- [FIX] Menampilkan semua tombol pengajuan yang tersedia --}}
        <div class="flex items-center space-x-4">
            <x-filament-actions::actions :actions="$this->getCachedHeaderActions()" />
        </div>
    </div>
</x-filament-panels::page>