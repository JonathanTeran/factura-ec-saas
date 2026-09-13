<?php

namespace App\Filament\Resources\FefRefereeResource\Pages;

use App\Filament\Resources\FefRefereeResource;
use Filament\Resources\Pages\ListRecords;

class ListFefReferees extends ListRecords
{
    protected static string $resource = FefRefereeResource::class;

    protected ?string $subheading = 'Árbitros que aparecen en los partidos publicados por la FEF (terna y cuarto árbitro). "Con cuenta" = su nombre coincide con una cuenta de árbitro en Facturón.';
}
