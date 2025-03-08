<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowledgeDocumentResource\Pages;
use App\Models\KnowledgeDocument;
use App\Services\RAG\DocumentProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeDocumentResource extends Resource
{
    protected static ?string $model = KnowledgeDocument::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Knowledge Base';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),
                        
                        Forms\Components\Select::make('collection_id')
                            ->relationship('collection', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(1000),
                            ]),
                        
                        Forms\Components\FileUpload::make('file')
                            ->label('Document File')
                            ->disk('documents')
                            ->directory('uploads')
                            ->visibility('private')
                            ->acceptedFileTypes($this->getSupportedMimeTypes())
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Processing Options')
                    ->schema([
                        Forms\Components\Toggle::make('extract_metadata')
                            ->label('Extract Metadata')
                            ->default(true)
                            ->helperText('Extract metadata from document like author, creation date, etc.'),
                        
                        Forms\Components\Toggle::make('split_into_chunks')
                            ->label('Split Into Chunks')
                            ->default(true)
                            ->helperText('Split document into smaller chunks for better processing'),
                        
                        Forms\Components\TextInput::make('chunk_size')
                            ->label('Chunk Size')
                            ->default(1000)
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(10000)
                            ->helperText('Number of characters per chunk')
                            ->visible(fn (Forms\Get $get) => $get('split_into_chunks')),
                        
                        Forms\Components\Toggle::make('generate_embeddings')
                            ->label('Generate Embeddings')
                            ->default(true)
                            ->helperText('Generate vector embeddings for semantic search'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('collection.name')
                    ->label('Collection')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('filename')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('File Type')
                    ->formatStateUsing(fn (string $state) => Str::upper(Str::after($state, '/')))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (int $state) => $this->formatFileSize($state))
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'error',
                        'warning' => 'pending',
                        'warning' => 'processing',
                        'success' => 'processed',
                    ]),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collection')
                    ->relationship('collection', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'processed' => 'Processed',
                        'error' => 'Error',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('process')
                    ->label('Reprocess')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(fn (KnowledgeDocument $record) => $record->process())
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('process')
                        ->label('Reprocess Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (array $records) {
                            foreach ($records as $record) {
                                $record->process();
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
//            'index' => Pages\ListKnowledgeDocuments::route('/'),
 //           'create' => Pages\CreateKnowledgeDocument::route('/create'),
   

  //       'edit' => Pages\EditKnowledgeDocument::route('/{record}/edit')
//            'view' => Pages\ViewKnowledgeDocument::route('/{record}'),
        ];
    }
    
    protected function getSupportedMimeTypes(): array
    {
        $documentProcessor = app(DocumentProcessor::class);
        return $documentProcessor->getSupportedTypes();
    }
    
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
