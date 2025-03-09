<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomOpenAIEndpointResource\Pages;
use App\Models\CustomOpenAIEndpoint;
use App\Services\LLM\CustomEndpointService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CustomOpenAIEndpointResource extends Resource
{
    protected static ?string $model = CustomOpenAIEndpoint::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'AI Configuration';
    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return 'OpenAI Endpoints';
    }

    public static function getPluralLabel(): string
    {
        return 'OpenAI Compatible Endpoints';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Endpoint Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan('full'),
                        
                        Forms\Components\TextInput::make('base_url')
                            ->label('Base URL')
                            ->required()
                            ->url()
                            ->placeholder('https://api.example.com')
                            ->helperText('Base URL for the OpenAI-compatible API')
                            ->columnSpan('full'),
                        
                        Forms\Components\TextInput::make('api_key')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText('API key will be encrypted when stored')
                            ->columnSpan('full'),
                        
                        Forms\Components\TextInput::make('models_endpoint')
                            ->default('v1/models')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The endpoint path for listing available models'),
                    ]),
                
                Forms\Components\Section::make('Model Configuration')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('context_window')
                                    ->numeric()
                                    ->default(4096)
                                    ->required(),
                                
                                Forms\Components\TextInput::make('max_tokens')
                                    ->numeric()
                                    ->default(2048)
                                    ->required(),
                            ]),
                        
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('prompt_price_per_1k_tokens')
                                    ->label('Prompt Price (per 1K tokens)')
                                    ->numeric()
                                    ->default(0.001)
                                    ->required()
                                    ->step(0.0001),
                                
                                Forms\Components\TextInput::make('prompt_price_per_1k_tokens')
                                    ->label('Completion Price (per 1K tokens)')
                                    ->numeric()
                                    ->default(0.002)
                                    ->required()
                                    ->step(0.0001),
                            ]),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this endpoint'),
                    ]),
                
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('base_url')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('context_window')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Endpoints')
                    ->trueLabel('Active Endpoints')
                    ->falseLabel('Inactive Endpoints'),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (CustomOpenAIEndpoint $record) {
                        $service = app(CustomEndpointService::class);
                        $result = $service->testEndpointConnection($record);
                        
                        if ($result['success']) {
                            return Forms\Components\Actions\Action::make('test_success')
                                ->label('Connection Successful')
                                ->modalHeading('Connection Test Results')
                                ->modalDescription("Successfully connected to endpoint. Found {$result['models_found']} models.")
                                ->modalContent(view('filament.resources.custom-open-a-i-endpoint.test-results', [
                                    'models' => $result['models'],
                                    'success' => true,
                                ]))
                                ->button();
                        } else {
                            return Forms\Components\Actions\Action::make('test_failed')
                                ->label('Connection Failed')
                                ->color('danger')
                                ->modalHeading('Connection Test Results')
                                ->modalDescription($result['message'])
                                ->button();
                        }
                    }),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCustomOpenAIEndpoints::route('/'),
            'create' => Pages\CreateCustomOpenAIEndpoint::route('/create'),
            'edit' => Pages\EditCustomOpenAIEndpoint::route('/{record}/edit'),
            'view' => Pages\ViewCustomOpenAIEndpoint::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        // Only show endpoints belonging to the current user
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }
}
