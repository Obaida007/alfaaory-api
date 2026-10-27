<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Enums\PaymentGroup;
use App\Filament\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'المالية';

    protected static ?string $modelLabel = 'طريقة دفع';

    protected static ?string $pluralModelLabel = 'طرق الدفع';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('البيانات الأساسية')->columns(2)->schema([
                TextInput::make('name')->label('الاسم')->required()->maxLength(255),
                Select::make('group')->label('المجموعة')->options(PaymentGroup::class)->required(),
                TextInput::make('subtitle')->label('الوصف المختصر')->maxLength(100)->helperText('يظهر تحت الاسم، مثل: محفظة، حوالة، أوتوماتيك'),
                FileUpload::make('image')->label('الصورة')->image()->disk('public')->directory('payment-methods')->columnSpanFull(),
                Textarea::make('instructions')->label('التعليمات')->rows(4)->columnSpanFull(),
                KeyValue::make('account_details')->label('بيانات الحساب')
                    ->keyLabel('الحقل')->valueLabel('القيمة')
                    ->helperText('مثل: رقم المحفظة، اسم المستلم، عنوان USDT…')
                    ->columnSpanFull(),
            ]),
            Section::make('المبالغ والعملة')->columns(3)->schema([
                TextInput::make('currency')->label('العملة')->required()->maxLength(10)->default('USD'),
                TextInput::make('exchange_rate')->label('سعر الصرف (وحدة لكل 1$)')->numeric()->minValue(0.0001)->required()->default(1),
                TextInput::make('fee_percent')->label('العمولة %')->numeric()->minValue(0)->maxValue(100)->default(0),
                TextInput::make('min_amount')->label('الحد الأدنى')->numeric()->minValue(0)->default(1),
                TextInput::make('max_amount')->label('الحد الأقصى')->numeric()->minValue(0)->helperText('اتركه فارغًا لعدم التحديد'),
            ]),
            Section::make('الخيارات')->columns(3)->schema([
                Toggle::make('requires_kyc')->label('يتطلب توثيق الهوية'),
                Toggle::make('is_auto')->label('تلقائي'),
                Toggle::make('is_active')->label('مفعّل')->default(true),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->circular(),
                TextColumn::make('name')->label('الاسم')->searchable()->description(fn (PaymentMethod $record) => $record->subtitle),
                TextColumn::make('group')->label('المجموعة')->badge(),
                TextColumn::make('currency')->label('العملة'),
                TextColumn::make('exchange_rate')->label('سعر الصرف'),
                TextColumn::make('fee_percent')->label('العمولة')->suffix('%'),
                IconColumn::make('requires_kyc')->label('KYC')->boolean(),
                ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->filters([SelectFilter::make('group')->label('المجموعة')->options(PaymentGroup::class)])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
