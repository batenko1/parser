<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('link')
                    ->required(),
                TextInput::make('speed_x')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('very_fast_value')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
