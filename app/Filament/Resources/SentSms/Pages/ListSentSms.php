<?php

namespace App\Filament\Resources\SentSms\Pages;

use App\Filament\Resources\SentSms\SentSmsResource;
use Filament\Resources\Pages\ListRecords;

class ListSentSms extends ListRecords
{
    protected static string $resource = SentSmsResource::class;
}
