<?php

namespace App\Filament\Resources\RechargeCodes\Pages;

use App\Filament\Resources\RechargeCodes\RechargeCodeResource;
use App\Models\RechargeCode;
use App\Services\RechargeCodeService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListRechargeCodes extends ListRecords
{
    protected static string $resource = RechargeCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('توليد دفعة أكواد')
                ->icon(Heroicon::OutlinedSparkles)
                ->visible(fn () => RechargeCodeResource::canCreate())
                ->modalDescription('تُعرض الأكواد مرة واحدة فقط في ملف CSV يُنزَّل الآن — لا تُخزَّن إلا مشفّرة.')
                ->schema([
                    TextInput::make('amount')->label('مبلغ الكود ($)')->numeric()->minValue(0.01)->required(),
                    TextInput::make('count')->label('عدد الأكواد')->integer()->minValue(1)->maxValue(5000)->default(10)->required(),
                    TextInput::make('batch')->label('اسم الدفعة')->maxLength(100)->helperText('اختياري — يُولَّد تلقائيًا'),
                    DateTimePicker::make('expires_at')->label('تاريخ الانتهاء')->after('now'),
                ])
                ->action(function (array $data): StreamedResponse {
                    $result = app(RechargeCodeService::class)->generate(
                        (string) $data['amount'],
                        (int) $data['count'],
                        $data['batch'] ?: null,
                        $data['expires_at'] ? now()->parse($data['expires_at']) : null,
                        auth()->user(),
                    );

                    Notification::make()->title('تم توليد '.count($result['codes']).' كود')->success()->send();

                    return response()->streamDownload(function () use ($result, $data) {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF");
                        fputcsv($out, ['code', 'amount_usd', 'batch', 'expires_at']);
                        foreach ($result['codes'] as $code) {
                            fputcsv($out, [$code, $data['amount'], $result['batch'], $data['expires_at'] ?? '']);
                        }
                        fclose($out);
                    }, "recharge-codes-{$result['batch']}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
            Action::make('export')
                ->label('تصدير السجل CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function () {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, ['id', 'last4', 'amount_usd', 'batch', 'used_by', 'used_at', 'expires_at', 'created_at']);
                    RechargeCode::query()->with('user')->orderBy('id')->chunk(500, function ($codes) use ($out) {
                        foreach ($codes as $code) {
                            fputcsv($out, [$code->id, $code->code_last4, $code->amount, $code->batch, $code->user?->username,
                                $code->used_at, $code->expires_at, $code->created_at]);
                        }
                    });
                    fclose($out);
                }, 'recharge-codes-log-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
        ];
    }
}
