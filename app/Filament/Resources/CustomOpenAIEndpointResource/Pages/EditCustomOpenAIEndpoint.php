<?php

namespace App\Filament\Resources\CustomOpenAIEndpointResource\Pages;

use App\Filament\Resources\CustomOpenAIEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomOpenAIEndpoint extends EditRecord
{
    protected static string $resource = CustomOpenAIEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
