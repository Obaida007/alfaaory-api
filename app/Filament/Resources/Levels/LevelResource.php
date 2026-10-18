<?php

namespace App\Filament\Resources\Levels;

use App\Filament\Resources\Levels\Pages\CreateLevel;
use App\Filament\Resources\Levels\Pages\EditLevel;
use App\Filament\Resources\Levels\Pages\ListLevels;
use App\Models\Level;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LevelResource extends Resource
{
    protected static ?string $model = Level::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'المستخدمون';

    protected static ?string $modelLabel = 'مستوى';

    protected static ?string $pluralModelLabel = 'المستويات';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('الاسم')->required()->maxLength(100),
            TextInput::make('min_total_purchases')->label('الحد الأدنى لإجمالي المشتريات ($)')->numeric()->minValue(0)->required()->default(0),
            TextInput::make('discount_percent')->label('نسبة الخصم %')->numeric()->minValue(0)->maxValue(100)->required()->default(0),
            ColorPicker::make('color')->label('اللون'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_total_purchases')
            ->columns([
                ColorColumn::make('color')->label('اللون'),
                TextColumn::make('name')->label('الاسم'),
                TextColumn::make('min_total_purchases')->label('يبدأ من')->money('USD', locale: 'en'),
                TextColumn::make('discount_percent')->label('الخصم')->suffix('%'),
                TextColumn::make('users_count')->label('المستخدمون')->counts('users'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLevels::route('/'),
            'create' => CreateLevel::route('/create'),
            'edit' => EditLevel::route('/{record}/edit'),
        ];
    }
}
