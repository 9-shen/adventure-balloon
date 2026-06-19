<?php

namespace App\Notifications;

use App\Models\TransportBill;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransportBillIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TransportBill $bill,
        public readonly string        $pdfContent,
        public readonly bool          $isResend = false,
    ) {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bill       = $this->bill;
        $company    = $bill->transportCompany;
        $appName    = app(AppSettings::class)->company_name;
        $appEmail   = app(AppSettings::class)->company_email;

        $periodFrom = $bill->period_from
            ? Carbon::parse($bill->period_from)->format('d/m/Y')
            : '—';
        $periodTo   = $bill->period_to
            ? Carbon::parse($bill->period_to)->format('d/m/Y')
            : '—';

        $subtotal   = number_format((float) $bill->subtotal, 2);
        $total      = number_format((float) $bill->total_amount, 2);
        $taxRate    = $bill->tax_rate;
        $itemCount  = $bill->items?->count() ?? 0;

        $currency   = app(AppSettings::class)->getIsoCurrency();

        $subject = $this->isResend
            ? "Transport Bill {$bill->bill_ref} — Copy Requested | {$appName}"
            : "Transport Bill {$bill->bill_ref} from {$appName}";

        $intro = $this->isResend
            ? "Please find attached a copy of transport bill {$bill->bill_ref} for your records."
            : "Thank you for your partnership. Please find attached transport bill {$bill->bill_ref} for the recent dispatches.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Dear {$company?->name},")
            ->line($intro)
            ->line('')
            ->line('---')
            ->line("**📋 BILL DETAILS**")
            ->line("**Bill Ref       :** {$bill->bill_ref}")
            ->line("**Period       :** {$periodFrom} → {$periodTo}")
            ->line("**Dispatches   :** {$itemCount} items")
            ->line('')
            ->line("**💰 FINANCIALS**")
            ->line("**Subtotal     :** {$subtotal} {$currency}")
            ->when($taxRate > 0, fn ($m) => $m->line("**Tax ({$taxRate}%)  :** " . number_format((float) $bill->tax_amount, 2) . " {$currency}"))
            ->line("**TOTAL DUE    :** {$total} {$currency}")
            ->line('')
            ->when($bill->notes, fn ($m) => $m->line("**Notes:** {$bill->notes}")->line(''))
            ->line('---')
            ->line("If you have any questions about this bill, please contact us at {$appEmail}.")
            ->salutation("— {$appName} Finance Team")
            ->attachData(
                $this->pdfContent,
                "bill-{$bill->bill_ref}.pdf",
                ['mime' => 'application/pdf']
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'bill_ref'     => $this->bill->bill_ref,
            'company'      => $this->bill->transportCompany?->name,
            'total_amount' => $this->bill->total_amount,
            'is_resend'    => $this->isResend,
        ];
    }
}
