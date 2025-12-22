<?php

namespace App\Filament\Admin\Resources\Cotacaos;

use App\Filament\Admin\Resources\Cotacaos\Pages\ManageCotacaos;
use App\Models\Cotacao;
use App\Models\Produto;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Forms\Components\FileUpload;
//use Filament\Forms\Components\Actions;
//use Filament\Forms\Components\Actions\Action;
//use Filament\Forms\Components\Actions; // ✅ CORRIGIDO: Import correto
//use Filament\Forms\Components\Actions\Action; // ✅ CORRIGIDO: Import correto
use Filament\Schemas\Get;
use Filament\Schemas\Set;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Exception;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\CotacaoItem;
use App\Models\Marca;
use Filament\Actions\Imports\Importer;
use App\Filament\Imports\ItensCotacaoImporter;
use App\Services\EmailService;
use Exception as GlobalException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;
use Illuminate\Http\UploadedFile;

class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-document-text';

    protected static ?string $recordTitleAttribute = 'Cotações';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'cotacoes';

    protected static ?string $navigationLabel = 'Cotações';

    protected static string | UnitEnum | null $navigationGroup = 'Operacional';

    protected static ?string $pluralModelLabel = 'Cotações';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações da Cotação')
                    ->schema([
                        Hidden::make('id_empresa')
                            ->default(fn () => auth()->user()->id_empresa),
                        Hidden::make('id_usuario')
                            ->default(fn () => auth()->user()->id),
                        Select::make('fornecedores')
                            ->relationship(
                                name: 'fornecedores',
                                titleAttribute: 'nome',
                                modifyQueryUsing: fn (Builder $query) => 
                                    auth()->user()->is_master 
                                        ? $query // Se for master, mostra todos
                                        : $query->where('id_empresa', auth()->user()->id_empresa) // Se não, filtra por empresa
                            )
                            ->label('Fornecedor(es)')
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->required(),
                        DatePicker::make('data')
                            ->required()
                            ->default(now()),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
               // ---------------------- SEÇÃO 2 ----------------------
                Section::make('Item(ns) da Cotação')
                    ->schema([
                        //---------------------- UOLOAD ITENS VIA CSV-----------------
                        Section::make('Importar itens em lote')
                            //->description('Faça upload de um arquivo CSV e clique em "Importar Itens" para carregar os dados automaticamente.')
                            ->schema([
                                FileUpload::make('csv_import')
                                    ->label('Selecione o arquivo com a lista de itens')
                                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                                    ->maxSize(1024)
                                    ->directory('temp-csv-imports')
                                    ->preserveFilenames()
                                    ->helperText('Formato: Descrição, Quantidade, Marca (opcional), Observação (opcional)')
                                    ->multiple(false)
                                    ->storeFiles(false)
                                    ->visibility('private'),
                            
                            //    ->actions([
                                    Action::make('importar')
                                        ->label('Importar Itens')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->button()
                                        ->color('primary')
                                        ->requiresConfirmation()
                                        ->modalHeading('Importar Itens do CSV')
                                        ->modalDescription('Tem certeza que deseja importar os itens do arquivo CSV? Os itens atuais serão mantidos.')
                                        ->action(function ( $set, $get) {
                                            Log::info('=== INÍCIO DA IMPORTACAO CSV ===');
                                            
                                            $csvFiles = $get('csv_import');
                                            Log::info('Dados do csv_import:', ['csvFiles' => $csvFiles, 'type' => gettype($csvFiles)]);
                                            
                                            // Verifica se há arquivo
                                            if (empty($csvFiles)) {
                                                Log::warning('Nenhum arquivo CSV selecionado');
                                                Notification::make()
                                                    ->title('Nenhum arquivo selecionado')
                                                    ->body('Por favor, selecione um arquivo CSV antes de importar.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            // O FileUpload retorna uma estrutura específica
                                            // Precisamos extrair o caminho do arquivo
                                            $filePath = self::getFilePathFromUpload($csvFiles);
                                            Log::info('Caminho do arquivo extraído:', ['filePath' => $filePath]);
                                            
                                            if (!$filePath) {
                                                Log::error('Não foi possível extrair o caminho do arquivo do upload');
                                                Notification::make()
                                                    ->title('Erro no arquivo')
                                                    ->body('Não foi possível acessar o arquivo CSV.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            Log::info('Iniciando processamento do CSV...');
                                            $importedItems = self::processarCSV($filePath);
                                            Log::info('Resultado do processamento:', ['count' => count($importedItems), 'items' => $importedItems]);

                                            if (empty($importedItems)) {
                                                Log::warning('Nenhum item foi importado do CSV');
                                                Notification::make()
                                                    ->title('Nenhum item importado')
                                                    ->body('Verifique se o arquivo CSV contém dados válidos no formato correto.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('items') ?? [];
                                            Log::info('Itens atuais no repeater:', ['count' => count($currentItems)]);
                                            
                                            $set('items', array_merge($currentItems, $importedItems));
                                            Log::info('Itens após merge:', ['total' => count($currentItems) + count($importedItems)]);

                                            Notification::make()
                                                ->title('Importação concluída')
                                                ->body(count($importedItems) . ' itens adicionados com sucesso.')
                                                ->success()
                                                ->send();

                                            // Limpa o campo de upload
                                            $set('csv_import', null);
                                            Log::info('Campo csv_import limpo');
                                            Log::info('=== FIM DA IMPORTACAO CSV ===');
                                        }),
                                
                            ])
                            ->collapsible()
                            ->collapsed(),
                        
                         
                        //---------------------- REPEATER ITENS ----------------------
                        Repeater::make('items')
                            ->label('Item(ns)')
                            ->relationship('items')
                            ->schema([
                                Select::make('id_produto')
                                    ->label('Produto')
                                    ->relationship(name: 'produto',
                                        titleAttribute: 'descricao',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('id_empresa', auth()->user()->id_empresa)
                                            ->where('status', true)
                                        )
                                    ->searchable()
                                    ->columnSpan(2)
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) { // ✅ REMOVIDA a tipagem do Set
                                        if ($state) {
                                            $produto = Produto::find($state);
                                            if ($produto) {
                                                $set('descricao_produto', $produto->descricao);
                                                $set('id_marca', $produto->id_marca);
                                                $set('descricao_marca', $produto->marca->nome);
                                            }
                                        }
                                    }),

                                TextInput::make('descricao_produto')
                                    ->label('Descrição do Produto')
                                    ->columnSpan(4)
                                    ->maxLength(255),
                                TextInput::make('quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->columnSpan(1),

                                Select::make('id_marca')
                                    ->label('Marca')
                                    ->relationship(name: 'marca',
                                        titleAttribute: 'nome',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('id_empresa', auth()->user()->id_empresa)
                                            ->where('status', true)
                                        )
                                    ->searchable()
                                    ->columnSpan(2)
                                    ->preload()
                                    ->afterStateUpdated(function ($state, $set) { // ✅ REMOVIDA a tipagem do Set
                                        if ($state) {
                                            $marca = Marca::find($state);
                                            if ($marca) {
                                                $set('descricao_marca', $marca->nome);
                                               // $set('id_marca', $marca->id);
                                            }
                                        }
                                    }),
                                    TextInput::make('descricao_marca')
                                    ->label('Descrição da Marca')
                                    
                                    ->columnSpan(2),

                                

                                Textarea::make('observacao')
                                    ->label('Observação')
                                    ->columnSpan(3)
                                    ->rows(1)
                                    ->maxLength(200),
                            ])
                            ->columns(7)
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar Item'),
                    ]),

            ])
            ->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Cotação')
                    ->schema([
                        TextEntry::make('empresa.nome_fantasia')
                            ->label('Empresa'),
                        TextEntry::make('numero')
                            ->label('Número'),
                        // 🔹 TODOS OS FORNECEDORES
                        TextEntry::make('fornecedores')
                            ->label('Fornecedor(es)')
                            ->getStateUsing(fn ($record) =>
                                $record->fornecedores
                                    ->pluck('nome')
                                    ->join(', ')
                            )
                            ->badge(),

                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime(format: 'd/m/Y H:i:s'),
                        TextEntry::make('items_count')
                            ->label('Qtd Itens')
                            ->counts('items'),
                        TextEntry::make('status')
                            ->color(fn (string $state): string => match ($state) {
                                'pendente' => 'gray',
                                'enviada' => 'warning',
                                'respondida' => 'success',
                                'finalizada' => 'primary',
                                'cancelada' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('observacao')
                            ->label('Observação'),
                    ])
                    ->columns(2),
                     // =======================
            // 🔁 ITENS DA COTAÇÃO
            // =======================
            Section::make('Itens da Cotação')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('descricao_produto')
                                ->label('Produto')
                                ->columnSpan(3),

                            TextEntry::make('descricao_marca')
                                ->label('Marca')
                                ->columnSpan(2),

                            TextEntry::make('quantidade')
                                ->label('Quant.')
                                ->columnSpan(1),

                            TextEntry::make('observacao')
                                ->label('Observação')
                                ->columnSpan(4),
                        ])
                        ->columns(10),
                ])
                ->columnSpanFull(),
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Cotações')
            ->columns([
                TextColumn::make('numero')
                    ->sortable()
                    ->searchable()
                    ->label('Número'),
                TextColumn::make('fornecedores.nome')
                    ->label('Fornecedor(es)')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('data')
                    ->date(format: 'd/m/Y')
                    ->sortable(),
                //TextColumn::make('valor_total')->money('BRL')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'gray',
                        'enviada' => 'warning',
                        'respondida' => 'success',
                        'finalizada' => 'primary',
                        'cancelada' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('items_count')
                    ->label('Qtd Itens')
                    ->counts('items')
                    ->sortable()
                    ->alignCenter(true),
                TextColumn::make('empresa.nome_fantasia')
                    ->label('Empresa')
                    ->visible(fn () => auth()->user()->is_master)
                    ->searchable()
                    ->sortable(),
        
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('processar_respostas')
                    ->label('')
                    ->tooltip('Processar Respostas')
                    ->icon('heroicon-o-inbox')
                    ->color('warning')
                    ->action(function () {
                        try {
                            $emailService = new EmailService();
                            $resultados = $emailService->processarRespostasFornecedores();
                            
                            $mensagens = [];
                            foreach ($resultados as $resultado) {
                                $status = $resultado['success'] ? '✅' : '❌';
                                $mensagens[] = "{$status} {$resultado['message']}";
                            }
                            
                            $mensagemFinal = "Processamento de respostas:\n" . implode("\n", $mensagens);
                            
                            Notification::make()
                                ->title('Processamento Concluído')
                                ->body($mensagemFinal)
                                ->success()
                                ->send();
                                
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Erro no Processamento')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('visualizar_respostas')
                    ->label('')
                    ->tooltip('Visualizar Respostas')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modal()
                    ->modalContent(fn (Cotacao $record) => view('filament.resources.cotacao-resource.pages.respostas-fornecedores', [
                        'cotacao' => $record,
                    ]))
                    ->visible(fn (Cotacao $record) => $record->fornecedores()->wherePivot('status', 'respondida')->exists()),
                                Action::make('enviar_todos')
                                    ->label('')
                                    ->tooltip('Enviar Todos')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->color('success')
                                    ->action(function (Cotacao $record) {
                                        $emailService = new EmailService();
                                        $resultados = [];
                                        
                                        foreach ($record->fornecedores as $fornecedor) {
                                            $resultado = $emailService->enviarCotacaoParaFornecedor($record, $fornecedor->id);
                                            $resultados[] = "{$fornecedor->nome}: " . ($resultado['success'] ? '✅' : '❌ ' . $resultado['message']);
                                        }

                                        // Mostrar resultados
                                        $mensagem = "Resultados do envio:\n" . implode("\n", $resultados);
                                        
                                        Notification::make()
                                            ->title('Envio de Cotações')
                                            ->body($mensagem)
                                            ->success()
                                            ->send();
                                    })
                                    ->visible(fn (Cotacao $record) => $record->status === 'pendente'),

                Action::make('enviar_individual')
                    ->label('')
                    ->tooltip('Enviar p/ Fornecedor')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->action(function (Cotacao $record, array $data) {
                        $emailService = new EmailService();
                        $resultado = $emailService->enviarCotacaoParaFornecedor($record, $data['fornecedor_id']);
                        
                        if ($resultado['success']) {
                            Notification::make()
                                ->title('Sucesso')
                                ->body('Cotação enviada com sucesso!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erro no Envio')
                                ->body($resultado['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                            Select::make('fornecedor_id')
                            ->label('Fornecedor')
                            ->options(fn (Cotacao $record) => $record->fornecedores->pluck('nome', 'id'))
                            ->required(),
                    ])
                    ->visible(fn (Cotacao $record) => $record->status === 'pendente'),

                
                 ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Cotação'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Cotação')
                    ->modalHeading('Editar Cotação'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Cotação')
                    ->modalHeading('Deseja Excluir essa cotação?')
                    ->modalDescription('Essa ação não pode ser desfeita.')
                    ->modalButton('Excluir')
                    ->modalWidth('md') // ✅ Correção: Usando o enum corretamente
                    ->requiresConfirmation(),
            
            ])
            ->toolbarActions([
               
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCotacaos::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->is_master) {
            return $query->where('id_empresa', auth()->user()->id_empresa);
        }

        return $query;
    }

private static function getFilePathFromUpload($uploadData): ?string
{
    \Log::info('getFilePathFromUpload - Input:', ['uploadData' => $uploadData, 'type' => gettype($uploadData)]);
    
    // Se já é uma string (caminho direto)
    if (is_string($uploadData)) {
        \Log::info('getFilePathFromUpload - Retornando string:', ['path' => $uploadData]);
        return $uploadData;
    }

    // Se é um array, procura pelo caminho do arquivo
    if (is_array($uploadData)) {
        \Log::info('getFilePathFromUpload - Processando array:', ['count' => count($uploadData), 'keys' => array_keys($uploadData)]);
        
        // Nova estrutura: [uuid => [TemporaryUploadedFile => path]]
        foreach ($uploadData as $uuid => $fileData) {
            \Log::info('getFilePathFromUpload - Analisando UUID:', ['uuid' => $uuid, 'fileData' => $fileData]);
            
            // Se fileData é um array que contém o caminho
            if (is_array($fileData)) {
                \Log::info('getFilePathFromUpload - Analisando array interno:', ['keys' => array_keys($fileData)]);
                
                foreach ($fileData as $key => $value) {
                    \Log::info('getFilePathFromUpload - Analisando item:', ['key' => $key, 'value' => $value, 'type' => gettype($value)]);
                    
                    // Se o valor é diretamente o caminho (caso atual)
                    if (is_string($value) && file_exists($value)) {
                        \Log::info('getFilePathFromUpload - Caminho encontrado no array:', ['path' => $value]);
                        return $value;
                    }
                    
                    // Se é um TemporaryUploadedFile
                    if ($value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $path = $value->getRealPath();
                        \Log::info('getFilePathFromUpload - TemporaryUploadedFile encontrado:', ['path' => $path]);
                        return $path;
                    }
                }
            }
            
            // Se fileData é diretamente o caminho (fallback)
            if (is_string($fileData) && file_exists($fileData)) {
                \Log::info('getFilePathFromUpload - Caminho direto no fileData:', ['path' => $fileData]);
                return $fileData;
            }
            
            // Se fileData é um TemporaryUploadedFile
            if ($fileData instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $path = $fileData->getRealPath();
                \Log::info('getFilePathFromUpload - TemporaryUploadedFile encontrado:', ['path' => $path]);
                return $path;
            }
        }
    }

    // Se chegou até aqui, tenta uma abordagem mais genérica para extrair caminhos
    if (is_array($uploadData)) {
        $path = self::extractPathFromArray($uploadData);
        if ($path) {
            return $path;
        }
    }

    \Log::warning('getFilePathFromUpload - Não foi possível extrair caminho do arquivo');
    \Log::warning('getFilePathFromUpload - Estrutura completa:', ['structure' => $uploadData]);
    return null;
}

/**
 * Função auxiliar para extrair caminho de arquivo de array complexo
 */
private static function extractPathFromArray(array $data): ?string
{
    foreach ($data as $key => $value) {
        if (is_string($value) && file_exists($value)) {
            \Log::info('extractPathFromArray - Caminho encontrado:', ['path' => $value]);
            return $value;
        }
        
        if (is_array($value)) {
            $path = self::extractPathFromArray($value);
            if ($path) {
                return $path;
            }
        }
        
        if ($value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $path = $value->getRealPath();
            \Log::info('extractPathFromArray - TemporaryUploadedFile encontrado:', ['path' => $path]);
            return $path;
        }
    }
    
    return null;
}
    // ---------------------- PROCESSAMENTO CSV ----------------------
    private static function processarCSV(string $filePath): array
    {
        \Log::info('=== INÍCIO PROCESSARCSV ===');
        \Log::info('processarCSV - FilePath recebido:', ['filePath' => $filePath]);
        
        try {
            // Verifica se é um caminho absoluto do sistema de arquivos
            if (file_exists($filePath)) {
                $fullPath = $filePath;
                \Log::info('processarCSV - Usando caminho absoluto:', ['fullPath' => $fullPath]);
            } else {
                // Tenta como caminho do Storage
                $fullPath = Storage::path($filePath);
                \Log::info('processarCSV - Convertendo para Storage path:', ['fullPath' => $fullPath]);
            }
            
            if (!file_exists($fullPath)) {
                \Log::error("processarCSV - Arquivo não encontrado: {$fullPath}");
                return [];
            }

            // Verifica se o arquivo não está vazio
            $fileSize = filesize($fullPath);
            \Log::info('processarCSV - Tamanho do arquivo:', ['size' => $fileSize]);
            
            if ($fileSize === 0) {
                \Log::warning("processarCSV - Arquivo CSV vazio: {$fullPath}");
                return [];
            }

            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setDelimiter(',');
            
            // Tenta detectar automaticamente o cabeçalho
            $csv->setHeaderOffset(0);
            
            $header = $csv->getHeader();
            \Log::info('processarCSV - Cabeçalhos detectados:', ['headers' => $header]);
            
            $records = $csv->getRecords();
            $importedItems = [];
            $linhaNumero = 1;

            foreach ($records as $record) {
                $linhaNumero++;
                \Log::info("processarCSV - Processando linha {$linhaNumero}:", ['record' => $record]);
                
                // Tenta diferentes nomes de colunas possíveis
                $descricao = trim(
                    $record['descricao'] ?? 
                    $record['Descrição'] ?? 
                    $record['Descricao'] ??
                    $record['produto'] ?? 
                    $record['Produto'] ?? 
                    $record['item'] ?? 
                    $record['Item'] ?? 
                    $record['nome'] ?? 
                    $record['Nome'] ?? 
                    ''
                );

                $quantidade = trim(
                    $record['quantidade'] ?? 
                    $record['Quantidade'] ?? 
                    $record['qtd'] ?? 
                    $record['Qtd'] ?? 
                    $record['qtd.'] ?? 
                    '1'
                );

                $marca = trim(
                    $record['marca'] ?? 
                    $record['Marca'] ?? 
                    $record['brand'] ?? 
                    $record['Brand'] ?? 
                    ''
                );

                $observacao = trim(
                    $record['observacao'] ?? 
                    $record['Observação'] ?? 
                    $record['Observacao'] ?? 
                    $record['obs'] ?? 
                    $record['Obs'] ?? 
                    $record['nota'] ?? 
                    $record['Nota'] ?? 
                    $record['comentario'] ??
                    ''
                );

                \Log::info("processarCSV - Dados extraídos linha {$linhaNumero}:", [
                    'descricao' => $descricao,
                    'quantidade' => $quantidade,
                    'marca' => $marca,
                    'observacao' => $observacao
                ]);

                // Pula linhas vazias
                if (empty($descricao)) {
                    \Log::warning("processarCSV - Linha {$linhaNumero} ignorada (descrição vazia)");
                    continue;
                }

                // Converte quantidade para float
                $quantidade = floatval(str_replace(',', '.', str_replace('.', '', $quantidade)));
                if ($quantidade <= 0) {
                    $quantidade = 1;
                }
                
                \Log::info("processarCSV - Quantidade convertida:", ['original' => $record['quantidade'] ?? '', 'convertida' => $quantidade]);

                // Busca marca e produto
                $user = auth()->user();
                $idMarca = null;
                $idProduto = null;

                if (!empty($marca)) {
                    \Log::info("processarCSV - Buscando marca:", ['marca' => $marca]);
                    $idMarca = \App\Models\Marca::where('nome', 'like', '%' . $marca . '%')
                        ->where('id_empresa', $user->id_empresa)
                        ->value('id');
                    \Log::info("processarCSV - Resultado busca marca:", ['id_marca' => $idMarca]);
                }

                if (!empty($descricao)) {
                    \Log::info("processarCSV - Buscando produto:", ['descricao' => $descricao]);
                    $idProduto = \App\Models\Produto::where('descricao', 'like', '%' . $descricao . '%')
                        ->where('id_empresa', $user->id_empresa)
                        ->value('id');
                    \Log::info("processarCSV - Resultado busca produto:", ['id_produto' => $idProduto]);
                }

                $item = [
                    'id_produto' => $idProduto,
                    'descricao_produto' => $descricao,
                    'id_marca' => $idMarca,
                    'quantidade' => $quantidade,
                    'observacao' => $observacao,
                ];
                
                $importedItems[] = $item;
                \Log::info("processarCSV - Item adicionado:", $item);
            }

            \Log::info('processarCSV - Importação concluída:', [
                'arquivo' => $filePath,
                'itens_importados' => count($importedItems)
            ]);

            \Log::info('=== FIM PROCESSARCSV ===');
            return $importedItems;

        } catch (\Throwable $e) {
            \Log::error('processarCSV - Erro ao processar CSV: ' . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            \Log::info('=== FIM PROCESSARCSV COM ERRO ===');
            return [];
        }
    }

}