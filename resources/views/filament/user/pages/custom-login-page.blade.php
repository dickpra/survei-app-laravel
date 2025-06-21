<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            <a href="{{ filament()->getRegistrationUrl() }}">
                {{ __('filament-panels::pages/auth/login.actions.register.label') }}
            </a>
        </x-slot>
    @endif

    {{-- 
        Kode ini akan menampilkan form dan tombolnya secara langsung
        menggunakan komponen inti Filament, tanpa ada div atau style tambahan dari kita.
        Ini adalah cara paling dasar dan anti-gagal.
    --}}
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

</x-filament-panels::page.simple>