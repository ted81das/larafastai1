<?php

namespace App\Filament\Resources\CustomOpenAIEndpointResource\Pages;

use App\Filament\Resources\CustomOpenAIEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomOpenAIEndpoint extends CreateRecord
{
    protected static string $resource = CustomOpenAIEndpointResource::class;
}
