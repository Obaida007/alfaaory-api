<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'متجر الفاعوري',
            'admin_whatsapp' => '',
            'admin_email' => 'support@alfaore-store.com',
            'facebook_url' => '',
            'instagram_url' => '',
            'telegram_url' => '',
            'tiktok_url' => '',
            'youtube_url' => '',
            'about_text' => "متجر الفاعوري متجر رقمي لشحن الألعاب والاشتراكات والبطاقات الإلكترونية والأرصدة.\nنعمل عبر شبكة من الوكلاء في عدة دول لنوفر لك شحنًا سريعًا وآمنًا من رصيد محفظتك.",
            'privacy_policy' => "نحترم خصوصيتك. نجمع فقط البيانات اللازمة لتنفيذ طلباتك (الاسم، البريد، رقم الهاتف، ومعرّفات اللاعبين)، ولا نشاركها مع أي طرف ثالث إلا بالقدر اللازم لتنفيذ الطلب لدى المزوّد.\nتُحفظ وثائق التحقق من الهوية بشكل خاص ولا يطّلع عليها إلا فريق المراجعة.",
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
