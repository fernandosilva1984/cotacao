<?php
// app/Filament/Imports/MarcaImporter.php

namespace App\Filament\Imports;

use App\Models\Marca;
use App\Models\Empresa;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class MarcaImporter extends Importer
{
    protected static ?string $model = Marca::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nome')
                ->label('Nome')
                ->rules(['required', 'max:255']),
            ImportColumn::make('id_empresa')
                ->label('ID Empresa')
                ->numeric()
                ->rules(['required', 'exists:empresas,id']),
            ImportColumn::make('status')
                ->label('Status')
                ->boolean()
                ->rules(['boolean']),
        ];
    }

    public function resolveRecord(): ?Marca
    {
        $marca = Marca::where('nome', $this->data['nome'])
            ->where('id_empresa', $this->data['id_empresa'])
            ->first();
        
        return $marca ?? new Marca();
    }

    public function beforeSave(): void
    {
        $this->record->fill([
            'nome' => $this->data['nome'],
            'id_empresa' => (int) $this->data['id_empresa'],
            'status' => filter_var($this->data['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importação de marcas concluída. ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas com sucesso.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam.';
        }

        return $body;
    }
}