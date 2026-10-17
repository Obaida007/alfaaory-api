<?php

namespace App\Notifications;

use App\Models\Deposit;
use App\Models\KycRequest;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database notification sent to a user when one of their orders, deposits or KYC requests changes status.
 */
class StatusNotification extends Notification
{
    use Queueable;

    /**
     * @param  'order'|'deposit'|'kyc'  $subject
     */
    public function __construct(
        public string $subject,
        public int $subjectId,
        public string $status,
        public string $title,
        public string $body,
    ) {}

    public static function forOrder(Order $order): self
    {
        return new self('order', $order->id, $order->status->value,
            __('store.notify.order_title', ['id' => $order->id]),
            __('store.notify.order_body', ['product' => $order->product->name, 'status' => $order->status->getLabel()])
                .($order->admin_note ? ' — '.$order->admin_note : ''));
    }

    public static function forDeposit(Deposit $deposit): self
    {
        return new self('deposit', $deposit->id, $deposit->status->value,
            __('store.notify.deposit_title', ['id' => $deposit->id]),
            __('store.notify.deposit_body', ['amount' => $deposit->amount_usd, 'status' => $deposit->status->getLabel()])
                .($deposit->admin_note ? ' — '.$deposit->admin_note : ''));
    }

    public static function forKyc(KycRequest $kyc): self
    {
        return new self('kyc', $kyc->id, $kyc->status->value,
            __('store.notify.kyc_title'),
            __('store.notify.kyc_body', ['status' => $kyc->status->getLabel()])
                .($kyc->admin_note ? ' — '.$kyc->admin_note : ''));
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'subject_id' => $this->subjectId,
            'status' => $this->status,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
