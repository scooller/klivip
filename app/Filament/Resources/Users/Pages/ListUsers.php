<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Exports\HasCsvExportAction;
use App\Filament\Exports\UsersExport;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use HasCsvExportAction;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->makeCsvExportAction(UsersExport::class, fn () => new UsersExport, 'usuarios'),
            CreateAction::make(),
        ];
    }
}
