<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string  $pdfContent,
        public readonly bool    $isResend = false,
    ) {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice    = $this->invoice;
        $partner    = $invoice->partner;
        $appName    = app(AppSettings::class)->company_name;
        $appEmail   = app(AppSettings::class)->email;

        $periodFrom = $invoice->period_from
            ? Carbon::parse($invoice->period_from)->format('d/m/Y')
            : '—';
        $periodTo   = $invoice->period_to
            ? Carbon::parse($invoice->period_to)->format('d/m/Y')
            : '—';

        $subtotal   = number_format((float) $invoice->subtotal, 2);
        $total      = number_format((float) $invoice->total_amount, 2);
        $taxRate    = $invoice->tax_rate;
        $itemCount  = $invoice->items?->count() ?? 0;

        $subject = $this->isResend
            ? "Invoice {$invoice->invoice_ref} — Payment Requested | {$appName}"
            : "Invoice {$invoice->invoice_ref} from {$appName}";

        $intro = $this->isResend
            ? "Please find attached the invoice {$invoice->invoice_ref} for your records. Payment is now due."
            : "Thank you for your partnership. Please find attached invoice {$invoice->invoice_ref} for the services rendered.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Dear {$partner?->company_name},")
            ->line($intro)
            ->line('')
            ->line('---')
            ->line("**📋 INVOICE DETAILS**")
            ->line("**Invoice Ref  :** {$invoice->invoice_ref}")
            ->line("**Period       :** {$periodFrom} → {$periodTo}")
            ->line("**Bookings     :** {$itemCount} items")
            ->line('')
            ->line("**💰 FINANCIALS**")
            ->line("**Subtotal     :** {$subtotal} MAD")
            ->when($taxRate > 0, fn ($m) => $m->line("**Tax ({$taxRate}%)  :** " . number_format((float) $invoice->tax_amount, 2) . " MAD"))
            ->line("**TOTAL DUE    :** {$total} MAD")
            ->line('')
            ->when($invoice->notes, fn ($m) => $m->line("**Notes:** {$invoice->notes}")->line(''))
            ->line('---')
            ->line("If you have any questions about this invoice, please contact us at {$appEmail}.")
            ->salutation("— {$appName} Finance Team")
            ->attachData(
                $this->pdfContent,
                "invoice-{$invoice->invoice_ref}.pdf",
                ['mime' => 'application/pdf']
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_ref'  => $this->invoice->invoice_ref,
            'partner'      => $this->invoice->partner?->company_name,
            'total_amount' => $this->invoice->total_amount,
            'is_resend'    => $this->isResend,
        ];
    }
}
