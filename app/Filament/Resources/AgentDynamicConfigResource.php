<?php

namespace App\Filament\Resources;

use App\Models\AgentDynamicConfig;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class AgentDynamicConfigResource extends Resource
{
    protected static ?string $model = AgentDynamicConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'AI Agents';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Basic Agent Configuration
            Forms\Components\Card::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000),
                    
                Forms\Components\Textarea::make('instruction')
                    ->required()
                    ->label('Agent Instructions')
                    ->helperText('The base instructions for the agent')
                    ->rows(3),

                // Model Selection
                Forms\Components\Select::make('model')
                    ->required()
                    ->options([
                        'gpt-4' => 'GPT-4',
                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                        // Add other models
                    ])
                    ->default('gpt-3.5-turbo'),

                // Temperature Setting
                Forms\Components\Slider::make('temperature')
                    ->default(0.7)
                    ->min(0)
                    ->max(2)
                    ->step(0.1),

                // Response Schema (Optional)
                Forms\Components\KeyValue::make('response_schema')
                    ->label('Structured Output Schema')
                    ->helperText('Define the expected response structure')
                    ->reorderable(),
            ])->columnSpan(2),

            // Tools Configuration
            Forms\Components\Card::make()->schema([
                Forms\Components\Repeater::make('tools')
                    ->label('Agent Tools')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'function' => 'Function Call',
                                'retrieval' => 'RAG Retrieval',
                            ])
                            ->reactive(),

                        // Function Tool Configuration
                        Forms\Components\KeyValue::make('function.parameters')
                            ->visible(fn ($get) => $get('type') === 'function')
                            ->label('Function Parameters'),

                        Forms\Components\TextInput::make('function.name')
                            ->visible(fn ($get) => $get('type') === 'function')
                            ->label('Function Name'),

                        Forms\Components\Textarea::make('function.description')
                            ->visible(fn ($get) => $get('type') === 'function')
                            ->label('Function Description'),
                    ]),

                // RAG Configuration
                Forms\Components\Toggle::make('rag_enabled')
                    ->label('Enable RAG')
                    ->reactive(),

                Forms\Components\KeyValue::make('rag_config')
                    ->visible(fn ($get) => $get('rag_enabled'))
                    ->label('RAG Configuration')
                    ->default([
                        'collection' => 'default',
                        'limit' => 5
                    ]),
            ])->columnSpan(1),
            Select::make('provider')
            ->options([
                'openai' => 'OpenAI',
                'prism' => 'Prism'
            ])
            ->reactive(),
        
        TextInput::make('api_key')
            ->visible(fn ($get) => $get('provider') === 'prism')
            ->placeholder('Enter Prism API Key'),
            
        Select::make('model')
            ->options(function ($get) {
                if ($get('provider') === 'prism') {
                    return config('laragent.providers.prism.models');
                }
                return ['gpt-4' => 'GPT-4', 'gpt-3.5-turbo' => 'GPT-3.5'];
            })->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('model')
                    ->badge(),
                    
                Tables\Columns\IconColumn::make('rag_enabled')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model')
                    ->options([
                        'gpt-4' => 'GPT-4',
                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('rag_enabled')
                    ->label('RAG Enabled'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // Test Agent Action
                Tables\Actions\Action::make('test')
                    ->action(function (AgentDynamicConfig $record) {
                        // Test the agent with a sample prompt
                        $response = $record->executeCompletion([
                            ['role' => 'user', 'content' => 'Test message']
                        ]);
                        
                        // Show response in notification
                        Notification::make()
                            ->title('Agent Response')
                            ->body($response['content'])
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add any relations here
        ];
    }

    public static function getPages(): array
    {
        return [
          //  'index' => Pages\ListAgentDynamicConfigs::route('/'),
          //  'create' => Pages\CreateAgentDynamicConfig::route('/create'),
          //  'edit' => Pages\EditAgentDynamicConfig::route('/{record}/edit'),
        ];
    }
}