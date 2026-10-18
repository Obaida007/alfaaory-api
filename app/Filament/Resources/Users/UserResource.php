<?php

namespace App\Filament\Resources\Users;

use App\Enums\KycStatus;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Users\RelationManagers\WalletTransactionsRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'المستخدمون';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'email', 'phone', 'first_name', 'last_name'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الحساب')->columns(2)->schema([
                TextInput::make('first_name')->label('الاسم الأول')->required()->maxLength(60),
                TextInput::make('last_name')->label('اسم العائلة')->maxLength(60),
                TextInput::make('username')->label('اسم المستخدم')->required()->alphaDash()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('الهاتف')->tel()->maxLength(30),
                TextInput::make('password')->label('كلمة المرور')->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->minLength(8)
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : null),
            ]),
            Section::make('المحفظة والمستوى')->columns(3)->schema([
                TextEntry::make('balance')->label('الرصيد الحالي')->money('USD', locale: 'en')
                    ->helperText('يُعدَّل فقط عبر إجراء «تعديل الرصيد»'),
                TextInput::make('debit_balance')->label('الرصيد المدين ($)')->numeric()->minValue(0)->default(0),
                Select::make('level_id')->label('المستوى')->relationship('level', 'name')->preload(),
            ])->visibleOn('edit'),
            Section::make('الحالة')->columns(3)->schema([
                Select::make('kyc_status')->label('توثيق الهوية')->options(KycStatus::class)->default(KycStatus::None)->required(),
                Toggle::make('two_factor_enabled')->label('التحقق بخطوتين')->inline(false),
                Toggle::make('is_banned')->label('محظور')->inline(false),
            ]),
            Section::make('صلاحيات لوحة التحكم')
                ->visible(fn () => Gate::allows('manageRoles', User::class))
                ->schema([
                    Select::make('roles')->label('الأدوار')->relationship('roles', 'name')->multiple()->preload()
                        ->helperText('super_admin: كل شيء — admin: كل شيء عدا المشرفين — support: الطلبات والإيداعات فقط'),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['level', 'roles']))
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('username')->label('اسم المستخدم')->searchable()
                    ->description(fn (User $record) => $record->full_name),
                TextColumn::make('email')->label('البريد')->searchable()->toggleable(),
                TextColumn::make('phone')->label('الهاتف')->searchable()->toggleable(),
                TextColumn::make('balance')->label('الرصيد')->money('USD', locale: 'en')->sortable(),
                TextColumn::make('level.name')->label('المستوى')->badge()->color('gray'),
                TextColumn::make('kyc_status')->label('KYC')->badge(),
                TextColumn::make('roles.name')->label('الدور')->badge()->color('primary')->placeholder('—'),
                IconColumn::make('is_banned')->label('محظور')->boolean()
                    ->trueIcon(Heroicon::OutlinedNoSymbol)->falseIcon(Heroicon::OutlinedCheck)
                    ->trueColor('danger')->falseColor('success'),
                TextColumn::make('last_login_at')->label('آخر دخول')->since()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('التسجيل')->date()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_banned')->label('الحظر'),
                SelectFilter::make('kyc_status')->label('KYC')->options(KycStatus::class),
                SelectFilter::make('level_id')->label('المستوى')->relationship('level', 'name'),
                TernaryFilter::make('staff')->label('المشرفون')
                    ->queries(
                        true: fn (Builder $q) => $q->role(User::ADMIN_ROLES),
                        false: fn (Builder $q) => $q->withoutRole(User::ADMIN_ROLES),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([UserActions::adjustBalance(), UserActions::toggleBan()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            WalletTransactionsRelationManager::class,
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
