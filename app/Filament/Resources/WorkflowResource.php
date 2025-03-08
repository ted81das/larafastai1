<?php
namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Automation';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Select::make('trigger_type')
                            ->label('Trigger Type')
                            ->options([
                                'webhook' => 'Webhook',
                                'scheduled' => 'Scheduled',
                                'manual' => 'Manual',
                                'event' => 'Event',
                            ])
                            ->required(),
                        Forms\Components\Select::make('schedule_frequency')
                            ->label('Schedule Frequency')
                            ->options([
                                'minutely' => 'Every Minute',
                                'hourly' => 'Hourly',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'custom' => 'Custom',
                            ])
                            ->visible(fn (callable $get) => $get('trigger_type') === 'scheduled'),
                        Forms\Components\TextInput::make('cron_expression')
                            ->label('Cron Expression')
                            ->placeholder('e.g. * * * * *')
                            ->helperText('Leave empty to use selected frequency')
                            ->visible(fn (callable $get) => $get('trigger_type') === 'scheduled'),
                        Forms\Components\KeyValue::make('trigger_config')
                            ->label('Trigger Configuration')
                            ->keyLabel('Parameter')
                            ->valueLabel('Value')
                            ->addable()
                            ->deletable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('trigger_type')
                    ->colors([
                        'primary' => 'webhook',
                        'success' => 'scheduled',
                        'warning' => 'manual',
                        'danger' => 'event',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->options([
                        'webhook' => 'Webhook',
                        'scheduled' => 'Scheduled',
                        'manual' => 'Manual',
                        'event' => 'Event',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('build')
                        ->label('Open Builder')
                        ->color('primary')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->url(fn (Workflow $record) => route('filament.admin.resources.workflows.builder', $record)),
                    Tables\Actions\Action::make('run')
                        ->label('Execute')
                        ->color('success')
                        ->icon('heroicon-o-play')
                        ->action(fn (Workflow $record) => $record->execute()),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->color('warning')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(fn (Workflow $record) => $record->duplicate()),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->color('danger')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
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
//            'index' => Pages\ListWorkflows::route('/'),
  //          'create' => Pages\CreateWorkflow::route('/create'),
    //        'edit' => Pages\EditWorkflow::route('/{record}/edit'),
      //      'builder' => Pages\WorkflowBuilder::route('/{record}/builder'),
        ];
    }    
}
