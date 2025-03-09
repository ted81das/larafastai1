<?php

namespace App\Filament\Resources\CustomOpenAIEndpointResource\Pages;

use App\Filament\Resources\CustomOpenAIEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomOpenAIEndpoints extends ListRecords
{
    protected static string $resource = CustomOpenAIEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
