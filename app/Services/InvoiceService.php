<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Notifications\InvoiceIssuedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Generate an invoice from a set of booking IDs for a given partner.
     *
     * @param  Partner  $partner
     * @param  int[]    $bookingIds
     * @param  array    $meta  ['notes' => '', 'tax_rate' => 0]
     */
    public function generate(Partner $partner, array $bookingIds, array $meta = []): Invoice
    {
        $bookings = Booking::whereIn('id', $bookingIds)
                           ->where('partner_id', $partner->id)
                           ->whereNull('invoiced_at')
                           ->get();

        if ($bookings->isEmpty()) {
            throw new \RuntimeException('No uninvoiced bookings found for the given selection.');
        }

        // Compute totals
        $subtotal = $bookings->sum('final_amount');
        $taxRate  = (float) ($meta['tax_rate'] ?? 0);
        $taxAmt   = round($subtotal * $taxRate / 100, 2);
        $total    = round($subtotal + $taxAmt, 2);

        // Period from min/max flight_date
        $periodFrom = $bookings->min('flight_date');
        $periodTo   = $bookings->max('flight_date');

        // Create invoice
        $invoice = Invoice::create([
            'invoice_ref'       => Invoice::generateRef(),
            'partner_id'        => $partner->id,
            'period_from'       => $periodFrom,
            'period_to'         => $periodTo,
            'subtotal'          => $subtotal,
            'tax_rate'          => $taxRate,
            'tax_amount'        => $taxAmt,
            'total_amount'      => $total,
            'status'            => 'draft',
            'notes'             => $meta['notes'] ?? null,
            'created_by'        => Auth::id(),
        ]);

        // Create line items + mark bookings as invoiced
        foreach ($bookings as $booking) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'booking_id'  => $booking->id,
                'description' => $booking->product?->name ?? 'Hot Air Balloon Experience',
                'flight_date' => $booking->flight_date,
                'adult_pax'   => $booking->adult_pax,
                'child_pax'   => $booking->child_pax,
                'unit_price'  => $booking->base_adult_price,
                'line_total'  => $booking->final_amount,
            ]);

            $booking->update([
                'invoice_id'  => $invoice->id,
                'invoiced_at' => now(),
            ]);
        }

        $invoice = $invoice->fresh(['partner', 'items.booking', 'items.booking.product']);

        // ── Email invoice to partner ───────────────────────────────────────
        if ($invoice->partner?->email) {
            try {
                $pdfContent = $this->generatePdf($invoice);
                $invoice->partner->notify(
                    new InvoiceIssuedNotification($invoice, $pdfContent)
                );
            } catch (\Exception $e) {
                Log::error("InvoiceService: failed to email partner [{$invoice->invoice_ref}]: " . $e->getMessage());
            }
        } else {
            Log::warning("InvoiceService: partner has no email, skipping notification [{$invoice->invoice_ref}]");
        }

        return $invoice;
    }

    /**
     * Generate a PDF for the invoice and return the binary content.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $invoice->loadMissing(['partner', 'items.booking.product', 'createdBy']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'items'   => $invoice->items,
            'partner' => $invoice->partner,
        ])->setPaper('a4');

        return $pdf->output();
    }

    /**
     * Mark invoice as paid.
     */
    public function markPaid(Invoice $invoice, string $reference): void
    {
        $invoice->update([
            'status'            => 'paid',
            'paid_at'           => now(),
            'payment_reference' => $reference,
        ]);
    }

    /**
     * Mark invoice as sent.
     */
    public function markSent(Invoice $invoice): void
    {
        $invoice->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        // ── Re-send invoice email to partner when manually marked as sent ───
        $invoice->loadMissing(['partner', 'items.booking.product', 'createdBy']);

        if ($invoice->partner?->email) {
            try {
                $pdfContent = $this->generatePdf($invoice);
                $invoice->partner->notify(
                    new InvoiceIssuedNotification($invoice, $pdfContent, isResend: true)
                );
            } catch (\Exception $e) {
                Log::error("InvoiceService: failed to re-send invoice email [{$invoice->invoice_ref}]: " . $e->getMessage());
            }
        }
    }
}
