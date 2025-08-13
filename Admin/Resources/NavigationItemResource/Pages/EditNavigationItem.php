<?php

namespace Paymenter\Extensions\Others\NavigationManager\Admin\Resources\NavigationItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Others\NavigationManager\Admin\Resources\NavigationItemResource;

class EditNavigationItem extends EditRecord
{
    protected static string $resource = NavigationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
