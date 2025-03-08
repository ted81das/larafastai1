<?php

namespace App\Filament\Resources\AgentDynamicConfigResource\Pages;

use App\Filament\Resources\AgentDynamicConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgentDynamicConfig extends ViewRecord
{
    protected static string $resource = AgentDynamicConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
