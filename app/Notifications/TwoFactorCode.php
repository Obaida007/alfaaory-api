<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رمز التحقق — متجر الفاعوري')
            ->line('رمز تسجيل الدخول الخاص بك هو:')
            ->line("**{$this->code}**")
            ->line('صالح لمدة 10 دقائق. إن لم تحاول تسجيل الدخول فتجاهل هذه الرسالة وغيّر كلمة المرور.');
    }
}
