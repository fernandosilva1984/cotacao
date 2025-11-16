<?php
// app/Filament/Imports/ProdutoImporter.php

namespace App\Filament\Imports;

use App\Models\Produto;
use App\Models\Marca;
use App\Models\Empresa;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProdutoImporter extends Importer
{
    protected static ?string $model = Produto::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('descricao')
                ->label('Descrição')
                ->rules(['required', 'max:255']),
            ImportColumn::make('id_marca')
                ->label('ID Marca')
                ->numeric()
                ->rules(['required', 'exists:marcas,id']),
            ImportColumn::make('codigo_barras')
                ->label('Código de Barras')
                ->rules(['max:255']),
            ImportColumn::make('unidade_medida')
                ->label('Unidade de Medida')
                ->rules(['max:10']),
            ImportColumn::make('observacao')
                ->label('Observação')
                ->rules(['max:65535']),
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

    public function resolveRecord(): ?Produto
    {
        $produto = Produto::where('descricao', $this->data['descricao'])
            ->where('id_empresa', $this->data['id_empresa'])
            ->first();
        
        return $produto ?? new Produto();
    }

    public function beforeSave(): void
    {
        $this->record->fill([
            'descricao' => $this->data['descricao'],
            'id_marca' => (int) $this->data['id_marca'],
            'codigo_barras' => $this->data['codigo_barras'] ?? null,
            'unidade_medida' => $this->data['unidade_medida'] ?? 'UN',
            'observacao' => $this->data['observacao'] ?? null,
            'id_empresa' => (int) $this->data['id_empresa'],
            'status' => filter_var($this->data['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importação de produtos concluída. ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas com sucesso.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam.';
        }

        return $body;
    }
}