<?php

namespace App\Filament\Resources\Deposits;

use App\Enums\DepositStatus;
use App\Exceptions\BusinessException;
use App\Models\Deposit;
use App\Services\DepositService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/**
 * Deposit review actions, shared by the deposits table and the deposit view page.
 */
class DepositActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('قبول')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Deposit $record) => $record->status === DepositStatus::Pending && Gate::allows('process', $record))
            ->modalHeading(fn (Deposit $record) => "قبول الإيداع #{$record->id}")
            ->modalDescription(fn (Deposit $record) => "المبلغ المرسل: {$record->amount} {$record->currency}")
            ->fillForm(fn (Deposit $record) => ['amount_usd' => $record->amount_usd])
            ->schema([
                TextInput::make('amount_usd')->label('المبلغ المضاف للرصيد ($)')->numeric()->minValue(0.01)->required()
                    ->helperText('محسوب تلقائيًا من سعر الصرف — عدّله إن وصل مبلغ مختلف'),
                Textarea::make('note')->label('ملاحظة')->rows(2),
            ])
            ->action(fn (Deposit $record, array $data) => static::run(
                $record,
                fn () => app(DepositService::class)->approve($record, auth()->user(), (string) $data['amount_usd'], $data['note'] ?: null),
                'تم قبول الإيداع وإضافة الرصيد',
            ));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Deposit $record) => $record->status === DepositStatus::Pending && Gate::allows('process', $record))
            ->requiresConfirmation()
            ->modalHeading(fn (Deposit $record) => "رفض الإيداع #{$record->id}")
            ->schema([
                Textarea::make('note')->label('سبب الرفض')->required()->rows(2),
            ])
            ->action(fn (Deposit $record, array $data) => static::run(
                $record,
                fn () => app(DepositService::class)->reject($record, auth()->user(), $data['note']),
                'تم رفض الإيداع',
            ));
    }

    /** @return array<int, Action> */
    public static function all(): array
    {
        return [static::approve(), static::reject()];
    }

    protected static function run(Deposit $record, callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (BusinessException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }

        $record->refresh();
    }
}
