<?php

namespace App\Filament\Resources\AgentDynamicConfigResource\Pages;

use App\Filament\Resources\AgentDynamicConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgentDynamicConfigs extends ListRecords
{
    protected static string $resource = AgentDynamicConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
