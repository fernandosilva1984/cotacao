<?php

namespace App\Filament\Imports;

use App\Models\Fornecedor;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;

class FornecedorImporter extends Importer
{
    protected static ?string $model = Fornecedor::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nome')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('razao_social')
                ->rules(['max:255']),
            ImportColumn::make('nome_fantasia')
                ->rules(['max:255']),
            ImportColumn::make('endereco')
                ->rules(['max:255']),
            ImportColumn::make('id_empresa')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'exists:empresas,id']),
            ImportColumn::make('status')
                ->boolean()
                ->rules(['boolean']),
        ];
    }

    public function resolveRecord(): ?Fornecedor
    {
        Log::info('Resolvendo registro', ['email' => $this->data['email'], 'id_empresa' => $this->data['id_empresa']]);
        
        $fornecedor = Fornecedor::where('email', $this->data['email'])
            ->where('id_empresa', $this->data['id_empresa'])
            ->first();

        if ($fornecedor) {
            Log::info('Fornecedor encontrado', ['id' => $fornecedor->id]);
            return $fornecedor;
        }

        Log::info('Criando novo fornecedor');
        return new Fornecedor();
    }

    public function beforeSave(): void
    {
        Log::info('Antes de salvar', $this->data);
        
        $this->record->fill([
            'nome' => $this->data['nome'],
            'email' => $this->data['email'],
            'razao_social' => $this->data['razao_social'] ?? null,
            'nome_fantasia' => $this->data['nome_fantasia'] ?? null,
            'endereco' => $this->data['endereco'] ?? null,
            'id_empresa' => (int) $this->data['id_empresa'],
            'status' => filter_var($this->data['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);

        Log::info('Dados preenchidos', $this->record->toArray());
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        Log::info('Importação concluída', [
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->getFailedRowsCount()
        ]);

        $body = 'Importação de fornecedores concluída. ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas com sucesso.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam.';
        }

        return $body;
    }
}