<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Exchange rates are units of the method's currency per 1 USD and are PROVISIONAL —
 * update them from the admin panel before going live. Account details are left for the admin to fill.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            'wallet' => [
                ['شام كاش دولار', 'USD', 1],
                ['شركة الهرم', 'USD', 1],
                ['دوفيز للحوالات', 'USD', 1],
                ['تسليم باليد', 'USD', 1],
                ['فودافون كاش', 'EGP', 50],
                ['ويش موني', 'USD', 1],
                ['أورانج الأردن', 'JOD', 0.71],
                ['MTN كاش', 'SYP', 11000],
                ['سيريتل كاش', 'SYP', 11000],
                ['زين كاش العراق', 'IQD', 1310],
                ['InstaPay', 'EGP', 50],
            ],
            'crypto' => [
                ['USDT يدوي', 'USD', 1],
                ['USDT TRC20', 'USD', 1],
                ['USDT BEP20', 'USD', 1],
            ],
            'bank' => [
                ['البنوك التركية', 'TRY', 41],
                ['البنوك الأوروبية', 'EUR', 0.92],
                ['البنوك الأردنية', 'JOD', 0.71],
                ['CIH BANK المغرب', 'MAD', 9.9],
                ['بريدي موب الجزائر', 'DZD', 130],
                ['ماستر كارد العراق', 'IQD', 1310],
            ],
            'agency_salary' => [
                ['POTA', 'USD', 1], ['YOHO', 'USD', 1], ['KARAK', 'USD', 1], ['YAAHLAN', 'USD', 1],
                ['DIKA', 'USD', 1], ['AMAR', 'USD', 1], ['POPPO', 'USD', 1], ['BIGO', 'USD', 1],
            ],
        ];

        $sort = 0;
        foreach ($methods as $group => $items) {
            foreach ($items as [$name, $currency, $rate]) {
                PaymentMethod::updateOrCreate(['name' => $name], [
                    'group' => $group,
                    'currency' => $currency,
                    'exchange_rate' => $rate,
                    'instructions' => $this->instructions($group, $name),
                    'account_details' => [],
                    'min_amount' => 1,
                    'max_amount' => null,
                    'fee_percent' => 0,
                    'requires_kyc' => $group === 'bank',
                    'is_auto' => false,
                    'is_active' => true,
                    'sort' => $sort++,
                ]);
            }
        }

        PaymentMethod::updateOrCreate(['name' => 'كود شحن الفاعوري'], [
            'group' => 'code',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'instructions' => 'أدخل كود الشحن في صفحة إضافة رصيد ليُضاف المبلغ إلى رصيدك فورًا.',
            'is_auto' => true,
            'is_active' => true,
            'sort' => $sort,
        ]);
    }

    protected function instructions(string $group, string $name): string
    {
        return match ($group) {
            'crypto' => "أرسل المبلغ عبر {$name} إلى العنوان الموضح، ثم ارفع صورة الإيصال مع رقم العملية (TxID).",
            'agency_salary' => "حوّل راتب وكالة {$name} إلى الحساب الموضح، ثم ارفع صورة التحويل.",
            default => "حوّل المبلغ عبر {$name} إلى البيانات الموضحة، ثم ارفع صورة الإيصال واكتب اسم المرسل ورقم العملية.",
        };
    }
}
