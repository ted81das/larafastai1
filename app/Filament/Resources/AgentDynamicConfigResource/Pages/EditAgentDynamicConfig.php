<?php

namespace App\Filament\Resources\AgentDynamicConfigResource\Pages;

use App\Filament\Resources\AgentDynamicConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentDynamicConfig extends EditRecord
{
    protected static string $resource = AgentDynamicConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
