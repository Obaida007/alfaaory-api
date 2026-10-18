<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Category tree: section → game/service → type. The list shows one level at a time
 * (roots by default); "الأقسام الفرعية" drills into a node, and rows are drag-sortable within it.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'الكتالوج';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('parent_id')
                ->label('القسم الأب')
                ->options(fn (?Category $record) => Category::pathOptions($record?->id))
                ->default(fn () => request()->integer('parent') ?: null)
                ->searchable()
                ->placeholder('— قسم رئيسي —'),
            TextInput::make('name')->label('الاسم')->required()->maxLength(255),
            TextInput::make('slug')->label('المعرّف في الرابط (slug)')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->alphaDash()
                ->helperText('يُولَّد تلقائيًا من الاسم إن تُرك فارغًا'),
            Toggle::make('is_active')->label('مفعّل')->default(true)->inline(false),
            FileUpload::make('image')->label('الصورة')->image()->disk('public')->directory('categories')->imageEditor()->columnSpanFull(),
            Textarea::make('description')->label('الوصف')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent')->withCount(['children', 'products']))
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->square(),
                TextColumn::make('name')->label('الاسم')->searchable()
                    ->description(fn (Category $record) => $record->parent?->name),
                TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('children_count')->label('الفروع'),
                TextColumn::make('products_count')->label('المنتجات'),
                ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('القسم الأب')
                    ->options(fn () => ['root' => '— الأقسام الرئيسية —'] + Category::pathOptions())
                    ->default('root')
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        null, '' => $query,
                        'root' => $query->whereNull('parent_id'),
                        default => $query->where('parent_id', $data['value']),
                    }),
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->recordActions([
                Action::make('children')
                    ->label('الأقسام الفرعية')
                    ->icon(Heroicon::OutlinedChevronDoubleLeft)
                    ->color('gray')
                    ->url(fn (Category $record) => static::getUrl('index', ['filters' => ['parent_id' => ['value' => $record->id]]])),
                Action::make('addChild')
                    ->label('إضافة فرع')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('gray')
                    ->visible(fn () => static::canCreate())
                    ->url(fn (Category $record) => static::getUrl('create', ['parent' => $record->id])),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    /** Categories with products cannot be deleted (orders reference products). */
    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->products()->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
