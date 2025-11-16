{{-- resources/views/filament/resources/user-resource/pages/import-user.blade.php --}}
<x-filament-panels::page>
    <x-filament-panels::form wire:submit="importar">
        {{ $this->form }}

        <div class="filament-page-actions">
            {{ $this->getHeaderActions() }}
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>