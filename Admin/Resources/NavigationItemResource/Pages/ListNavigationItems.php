<?php

namespace Paymenter\Extensions\Others\NavigationManager\Admin\Resources\NavigationItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Others\NavigationManager\Admin\Resources\NavigationItemResource;

class ListNavigationItems extends ListRecords
{
    protected static string $resource = NavigationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
