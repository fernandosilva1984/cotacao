<?php

namespace App\Filament\Imports;

use App\Models\CotacaoItem;
use App\Models\Produto;
use App\Models\Marca;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ItensCotacaoImporter extends Importer
{
    protected static ?string $model = CotacaoItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('descricao_produto')
                ->label('Descrição do Produto')
                ->required()
                ->rules(['required', 'string', 'max:255']),
            
            ImportColumn::make('quantidade')
                ->label('Quantidade')
                ->required()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            
            ImportColumn::make('id_marca')
                ->label('Marca')
                ->relationship(
                    resolveUsing: function ($state) {
                        if (empty($state)) return null;
                        
                        // Tenta encontrar a marca pelo nome
                        $marca = Marca::where('nome', 'like', '%' . trim($state) . '%')->first();
                        
                        if (!$marca) {
                            // Se não encontrar, cria uma nova marca
                            $marca = Marca::create([
                                'nome' => trim($state),
                                'id_empresa' => auth()->user()->id_empresa,
                            ]);
                        }
                        
                        return $marca->id;
                    }
                )
                ->rules(['sometimes', 'string']),
            
            ImportColumn::make('id_produto')
                ->label('Produto')
                ->relationship(
                    resolveUsing: function ($state) {
                        if (empty($state)) return null;
                        
                        // Tenta encontrar o produto pela descrição
                        $produto = Produto::where('descricao', 'like', '%' . trim($state) . '%')->first();
                        
                        if (!$produto) {
                            // Se não encontrar, cria um novo produto
                            $produto = Produto::create([
                                'descricao' => trim($state),
                                'id_empresa' => auth()->user()->id_empresa,
                                'id_marca' => null, // Pode ser preenchido depois
                            ]);
                        }
                        
                        return $produto->id;
                    }
                )
                ->rules(['sometimes', 'string']),
            
            ImportColumn::make('observacao')
                ->label('Observação')
                ->rules(['sometimes', 'string', 'max:200']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'A importação de itens da cotação foi concluída e ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam.';
        }

        return $body;
    }

    protected function beforeSave(): void
    {
        // Define a empresa do usuário logado
        $this->record->id_empresa = auth()->user()->id_empresa;
        
        // Se não encontrou um produto, usa a descrição fornecida
        if (empty($this->record->id_produto) && !empty($this->data['descricao_produto'])) {
            $this->record->descricao_produto = $this->data['descricao_produto'];
        }
    }

    public function resolveRecord(): ?CotacaoItem
    {
        // Cria um novo item de cotação
        return new CotacaoItem();
    }
}