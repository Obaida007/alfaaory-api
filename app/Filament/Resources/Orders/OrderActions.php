<?php

namespace App\Filament\Resources\Orders;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/**
 * Order processing actions, shared by the orders table and the order view page.
 */
class OrderActions
{
    public static function complete(): Action
    {
        return Action::make('complete')
            ->label('تنفيذ')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Order $record) => $record->isOpen() && Gate::allows('process', $record))
            ->modalHeading(fn (Order $record) => "تنفيذ الطلب #{$record->id}")
            ->schema([
                Textarea::make('delivered_code')->label('الكود / بيانات التسليم')->rows(2)
                    ->helperText('للمنتجات من نوع «كود» — يظهر للمستخدم بعد التنفيذ'),
                Textarea::make('note')->label('ملاحظة للمستخدم')->rows(2),
            ])
            ->action(fn (Order $record, array $data) => static::run(
                $record,
                fn () => app(OrderService::class)->complete($record, auth()->user(), $data['note'] ?: null, $data['delivered_code'] ?: null),
                'تم تنفيذ الطلب',
            ));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض وإعادة المبلغ')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->visible(fn (Order $record) => $record->isOpen() && Gate::allows('process', $record))
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record) => "رفض الطلب #{$record->id}")
            ->modalDescription(fn (Order $record) => "سيُعاد مبلغ {$record->total}$ إلى رصيد المستخدم.")
            ->schema([
                Textarea::make('note')->label('سبب الرفض')->required()->rows(2),
            ])
            ->action(fn (Order $record, array $data) => static::run(
                $record,
                fn () => app(OrderService::class)->reject($record, auth()->user(), $data['note']),
                'تم رفض الطلب وإعادة المبلغ',
            ));
    }

    public static function note(): Action
    {
        return Action::make('note')
            ->label('ملاحظة')
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->color('gray')
            ->visible(fn (Order $record) => Gate::allows('process', $record))
            ->fillForm(fn (Order $record) => ['admin_note' => $record->admin_note])
            ->schema([
                Textarea::make('admin_note')->label('ملاحظة الإدارة')->rows(3),
            ])
            ->action(function (Order $record, array $data) {
                $record->update(['admin_note' => $data['admin_note']]);
                Notification::make()->title('تم حفظ الملاحظة')->success()->send();
            });
    }

    /** @return array<int, Action> */
    public static function all(): array
    {
        return [static::complete(), static::reject(), static::note()];
    }

    protected static function run(Order $record, callable $callback, string $success): void
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
