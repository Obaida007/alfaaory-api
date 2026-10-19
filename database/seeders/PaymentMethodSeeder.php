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
        // [name, subtitle, currency, units per 1 USD]
        $methods = [
            'wallet' => [
                ['شام كاش', 'دولار', 'USD', 1],
                ['شركة الهرم', 'حوالة', 'USD', 1],
                ['دوفيز للحوالات', 'حوالة', 'USD', 1],
                ['تسليم باليد', 'نقدًا', 'USD', 1],
                ['فودافون كاش', 'محفظة', 'EGP', 50],
                ['ويش موني', 'محفظة', 'USD', 1],
                ['أورانج الأردن', 'محفظة', 'JOD', 0.71],
                ['MTN كاش', 'محفظة', 'SYP', 11000],
                ['سيريتل كاش', 'محفظة', 'SYP', 11000],
                ['زين كاش العراق', 'محفظة', 'IQD', 1310],
                ['InstaPay', 'تحويل فوري', 'EGP', 50],
            ],
            'crypto' => [
                ['USDT', 'يدوي', 'USD', 1],
                ['USDT TRC20', 'أوتوماتيك', 'USD', 1],
                ['USDT BEP20', 'أوتوماتيك', 'USD', 1],
            ],
            'bank' => [
                ['البنوك التركية', 'أقل من 2000 ليرة', 'TRY', 41],
                ['البنوك الأوروبية', 'حوالة بنكية', 'EUR', 0.92],
                ['البنوك الأردنية', 'حوالة بنكية', 'JOD', 0.71],
                ['CIH BANK', 'المغرب', 'MAD', 9.9],
                ['بريدي موب', 'الجزائر', 'DZD', 130],
                ['ماستر كارد', 'العراق', 'IQD', 1310],
            ],
            'agency_salary' => [
                ['POTA', 'رواتب بوتا', 'USD', 1], ['YOHO', 'رواتب يوهو', 'USD', 1],
                ['KARAK', 'رواتب كاراك', 'USD', 1], ['YAAHLAN', 'يا أهلًا', 'USD', 1],
                ['DIKA', 'رواتب ديكا', 'USD', 1], ['AMAR', 'رواتب عمار', 'USD', 1],
                ['POPPO', 'رواتب بوبو', 'USD', 1], ['BIGO', 'رواتب بيغو', 'USD', 1],
            ],
        ];

        $sort = 0;
        foreach ($methods as $group => $items) {
            foreach ($items as [$name, $subtitle, $currency, $rate]) {
                PaymentMethod::updateOrCreate(['name' => $name], [
                    'subtitle' => $subtitle,
                    'group' => $group,
                    'currency' => $currency,
                    'exchange_rate' => $rate,
                    'instructions' => $this->instructions($group, $name),
                    'account_details' => [],
                    'min_amount' => 1,
                    'max_amount' => null,
                    'fee_percent' => 0,
                    'requires_kyc' => $group === 'bank',
                    'is_auto' => $subtitle === 'أوتوماتيك',
                    'is_active' => true,
                    'sort' => $sort++,
                ]);
            }
        }

        PaymentMethod::updateOrCreate(['name' => 'كود شحن الفاعوري'], [
            'subtitle' => 'Credit Card',
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
