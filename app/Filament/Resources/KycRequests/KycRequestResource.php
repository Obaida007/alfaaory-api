<?php

namespace App\Filament\Resources\KycRequests;

use App\Enums\DepositStatus;
use App\Exceptions\BusinessException;
use App\Filament\Resources\KycRequests\Pages\ListKycRequests;
use App\Filament\Resources\KycRequests\Pages\ViewKycRequest;
use App\Filament\Support\DateRangeFilter;
use App\Models\KycRequest;
use App\Services\KycService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Identity documents live on the private "local" disk and are shown through short-lived signed URLs.
 */
class KycRequestResource extends Resource
{
    protected static ?string $model = KycRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $modelLabel = 'طلب توثيق';

    protected static ?string $pluralModelLabel = 'توثيق الهوية (KYC)';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = KycRequest::query()->where('status', DepositStatus::Pending)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الطلب')->columns(3)->schema([
                TextEntry::make('user.username')->label('المستخدم')
                    ->url(fn (KycRequest $record) => route('filament.admin.resources.users.edit', $record->user_id)),
                TextEntry::make('full_name')->label('الاسم الكامل'),
                TextEntry::make('national_id')->label('رقم الهوية')->copyable(),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('created_at')->label('تاريخ الطلب')->dateTime(),
                TextEntry::make('reviewer.full_name')->label('راجعه')->placeholder('—'),
                TextEntry::make('details')->label('تفاصيل')->placeholder('—')->columnSpanFull(),
                TextEntry::make('admin_note')->label('ملاحظة الإدارة')->placeholder('—')->columnSpanFull(),
            ]),
            Section::make('المستندات')->columns(2)->schema([
                ImageEntry::make('id_image')->label('صورة الهوية')->disk('local')->visibility('private')->imageHeight(360),
                ImageEntry::make('selfie_image')->label('صورة شخصية مع الهوية')->disk('local')->visibility('private')->imageHeight(360),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.username')->label('المستخدم')->searchable(),
                TextColumn::make('full_name')->label('الاسم الكامل')->searchable(),
                TextColumn::make('national_id')->label('رقم الهوية')->searchable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(DepositStatus::class)->default(DepositStatus::Pending->value),
                DateRangeFilter::make(),
            ])
            ->recordActions([ViewAction::make(), ...static::reviewActions()]);
    }

    /** @return array<int, Action> */
    public static function reviewActions(): array
    {
        $review = fn (string $name, string $label, string $color, Heroicon $icon, bool $noteRequired) => Action::make($name)
            ->label($label)
            ->color($color)
            ->icon($icon)
            ->visible(fn (KycRequest $record) => $record->status === DepositStatus::Pending && Gate::allows('process', $record))
            ->requiresConfirmation()
            ->schema([Textarea::make('note')->label($noteRequired ? 'سبب الرفض' : 'ملاحظة')->required($noteRequired)->rows(2)])
            ->action(function (KycRequest $record, array $data) use ($name) {
                try {
                    $service = app(KycService::class);
                    $name === 'approve'
                        ? $service->approve($record, auth()->user(), $data['note'] ?: null)
                        : $service->reject($record, auth()->user(), $data['note']);
                    Notification::make()->title($name === 'approve' ? 'تم توثيق الحساب' : 'تم رفض الطلب')->success()->send();
                } catch (BusinessException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
                $record->refresh();
            });

        return [
            $review('approve', 'قبول', 'success', Heroicon::OutlinedCheckBadge, false),
            $review('reject', 'رفض', 'danger', Heroicon::OutlinedXCircle, true),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKycRequests::route('/'),
            'view' => ViewKycRequest::route('/{record}'),
        ];
    }
}
