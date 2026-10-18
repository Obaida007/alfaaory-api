<?php

namespace App\Filament\Resources\Users;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class UserActions
{
    /** Manual balance change through WalletService, with a mandatory reason recorded in the ledger. */
    public static function adjustBalance(): Action
    {
        return Action::make('adjustBalance')
            ->label('تعديل الرصيد')
            ->icon(Heroicon::OutlinedScale)
            ->color('warning')
            ->visible(fn (User $record) => Gate::allows('adjustBalance', $record))
            ->modalHeading(fn (User $record) => "تعديل رصيد {$record->username}")
            ->modalDescription(fn (User $record) => "الرصيد الحالي: {$record->balance}$")
            ->schema([
                TextInput::make('amount')->label('المبلغ ($)')->numeric()->required()->notIn(['0'])
                    ->helperText('موجب للإضافة، سالب للخصم. مثال: 10 أو -5.50'),
                Textarea::make('reason')->label('السبب')->required()->maxLength(200)->rows(2),
            ])
            ->action(function (User $record, array $data) {
                try {
                    $tx = app(WalletService::class)->adjust($record, (string) $data['amount'], $data['reason'], auth()->user());
                    Notification::make()->title("تم التعديل — الرصيد الجديد {$tx->balance_after}$")->success()->send();
                } catch (BusinessException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
                $record->refresh();
            });
    }

    public static function toggleBan(): Action
    {
        return Action::make('toggleBan')
            ->label(fn (User $record) => $record->is_banned ? 'إلغاء الحظر' : 'حظر')
            ->icon(fn (User $record) => $record->is_banned ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedNoSymbol)
            ->color(fn (User $record) => $record->is_banned ? 'success' : 'danger')
            ->visible(fn (User $record) => Gate::allows('update', $record) && ! $record->is(auth()->user()))
            ->requiresConfirmation()
            ->action(function (User $record) {
                $record->update(['is_banned' => ! $record->is_banned]);
                if ($record->is_banned) {
                    $record->tokens()->delete();
                }
                Notification::make()->title($record->is_banned ? 'تم حظر المستخدم' : 'تم إلغاء الحظر')->success()->send();
            });
    }
}
