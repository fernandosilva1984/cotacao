<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nome')
                //->required()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->label('Email')
               // ->required()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('password')
                ->label('Senha')
               // ->required()
                ->rules(['required', 'min:6']),
            ImportColumn::make('id_empresa')
                ->label('ID Empresa')
                ->numeric()
               // ->required()
                ->rules(['required', 'exists:empresas,id']),
            ImportColumn::make('status')
                ->label('Status')
                ->boolean()
                ->rules(['boolean']),
        ];
    }

    public function resolveRecord(): ?User
    {
        $user = User::where('email', strtolower($this->data['email']))->first();
        
        return $user ?? new User();
    }

    public function beforeSave(): void
    {
        $this->record->fill([
            'name' => $this->data['name'],
            'email' => strtolower($this->data['email']),
            'password' => Hash::make($this->data['password']),
            'id_empresa' => (int) $this->data['id_empresa'],
            'status' => filter_var($this->data['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_master' => false,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importação de usuários concluída. ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas com sucesso.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam.';
        }

        return $body;
    }
}