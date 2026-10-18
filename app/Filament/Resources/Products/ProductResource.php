<?php

namespace App\Filament\Resources\Products;

use App\Enums\DeliveryType;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'الكتالوج';

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'المنتجات';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('المنتج')->columns(2)->schema([
                Select::make('category_id')->label('القسم')->options(fn () => Category::pathOptions())->searchable()->required(),
                TextInput::make('name')->label('الاسم')->required()->maxLength(255)->placeholder('مثال: 660 UC'),
                TextInput::make('price')->label('سعر البيع ($)')->numeric()->minValue(0)->required(),
                TextInput::make('cost')->label('التكلفة ($)')->numeric()->minValue(0)->default(0),
                TextInput::make('stock')->label('المخزون')->numeric()->minValue(0)->integer()->helperText('اتركه فارغًا لمخزون غير محدود'),
                Toggle::make('is_active')->label('مفعّل')->default(true)->inline(false),
            ]),
            Section::make('التسليم')->columns(2)->schema([
                Select::make('delivery_type')->label('نوع التسليم')->options(DeliveryType::class)->required()->default(DeliveryType::Manual),
                Toggle::make('requires_player_id')->label('يتطلب معرّف اللاعب')->default(true)->inline(false),
                Toggle::make('supports_name_check')->label('يدعم التحقق من اسم اللاعب')->inline(false),
                TextInput::make('provider')->label('المزوّد')->placeholder('fake'),
                TextInput::make('provider_product_id')->label('رقم المنتج لدى المزوّد'),
            ]),
            Section::make('حقول إضافية في صفحة الشراء')->collapsed()->schema([
                Repeater::make('extra_fields')->label('')
                    ->schema([
                        TextInput::make('name')->label('المفتاح')->required()->alphaDash(),
                        TextInput::make('label')->label('العنوان')->required(),
                        Toggle::make('required')->label('إلزامي')->inline(false),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addActionLabel('إضافة حقل'),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->columns([
                TextColumn::make('name')->label('المنتج')->searchable()
                    ->description(fn (Product $record) => $record->category?->name),
                TextInputColumn::make('price')->label('السعر')->type('number')->rules(['required', 'numeric', 'min:0'])->width('8rem'),
                TextColumn::make('cost')->label('التكلفة')->money('USD', locale: 'en')->toggleable(),
                TextColumn::make('delivery_type')->label('التسليم')->badge(),
                TextColumn::make('stock')->label('المخزون')->placeholder('∞'),
                TextColumn::make('orders_count')->label('الطلبات')->counts('orders')->sortable(),
                ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('القسم')->options(fn () => Category::pathOptions())->searchable(),
                SelectFilter::make('delivery_type')->label('التسليم')->options(DeliveryType::class),
                TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::bulkPriceAction(),
                    BulkAction::make('activate')->label('تفعيل')->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn (Collection $records) => Product::whereKey($records->modelKeys())->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')->label('إيقاف')->icon(Heroicon::OutlinedNoSymbol)->color('warning')
                        ->action(fn (Collection $records) => Product::whereKey($records->modelKeys())->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Raises or lowers the price of the selected products by a percentage or a fixed amount.
     * Past orders keep their own price snapshot, so this only affects new purchases.
     */
    protected static function bulkPriceAction(): BulkAction
    {
        return BulkAction::make('adjustPrices')
            ->label('تعديل الأسعار')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->schema([
                Radio::make('mode')->label('طريقة التعديل')->options([
                    'percent' => 'نسبة مئوية %',
                    'amount' => 'مبلغ ثابت $',
                ])->default('percent')->required()->inline(),
                TextInput::make('value')->label('القيمة')->numeric()->required()
                    ->helperText('موجبة للزيادة وسالبة للتخفيض، مثل 5 أو -2.5'),
                Select::make('field')->label('الحقل')->options(['price' => 'سعر البيع', 'cost' => 'التكلفة'])->default('price')->required(),
            ])
            ->action(function (Collection $records, array $data) {
                $field = $data['field'] === 'cost' ? 'cost' : 'price';

                DB::transaction(function () use ($records, $data, $field) {
                    foreach ($records as $product) {
                        $current = $product->{$field};
                        $delta = $data['mode'] === 'percent' ? Money::percent($current, $data['value']) : Money::round($data['value']);
                        $new = Money::add($current, $delta);
                        $product->update([$field => Money::cmp($new, 0) < 0 ? '0.00' : $new]);
                    }
                });

                Notification::make()->title('تم تعديل أسعار '.$records->count().' منتج')->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
