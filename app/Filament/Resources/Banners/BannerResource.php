<?php

namespace App\Filament\Resources\Banners;

use App\Enums\BannerPlacement;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'المحتوى والإعدادات';

    protected static ?string $modelLabel = 'بانر';

    protected static ?string $pluralModelLabel = 'البانرات';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('العنوان')->required()->maxLength(255),
            Select::make('placement')->label('المكان')->options(BannerPlacement::class)->required()->default(BannerPlacement::HomeHero),
            Textarea::make('body')->label('النص')->rows(3)->columnSpanFull(),
            FileUpload::make('image')->label('الصورة')->image()->disk('public')->directory('banners')->imageEditor()->columnSpanFull(),
            TextInput::make('link')->label('الرابط')->maxLength(255)->helperText('مسار داخلي مثل /categories/pubg-mobile أو رابط كامل'),
            Toggle::make('is_active')->label('مفعّل')->default(true),
            DateTimePicker::make('starts_at')->label('يبدأ في'),
            DateTimePicker::make('ends_at')->label('ينتهي في')->after('starts_at'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('image')->label('الصورة')->disk('public'),
                TextColumn::make('title')->label('العنوان')->searchable(),
                TextColumn::make('placement')->label('المكان')->badge(),
                TextColumn::make('starts_at')->label('يبدأ')->dateTime()->placeholder('—'),
                TextColumn::make('ends_at')->label('ينتهي')->dateTime()->placeholder('—'),
                ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->filters([SelectFilter::make('placement')->label('المكان')->options(BannerPlacement::class)])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
