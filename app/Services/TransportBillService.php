<?php

namespace App\Services;

use App\Models\TransportBill;
use App\Models\TransportBillItem;
use App\Models\TransportCompany;
use App\Models\Dispatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransportBillService
{
    /**
     * Generate a transport bill for the given dispatches belonging to a transport company.
     */
    public function generate(TransportCompany $company, array $dispatchIds, array $meta = []): TransportBill
    {
        return DB::transaction(function () use ($company, $dispatchIds, $meta) {
            $dispatches = Dispatch::whereIn('id', $dispatchIds)
                ->where('transport_company_id', $company->id)
                ->whereNull('billed_at')
                ->with('dispatchDriverRows.vehicle')
                ->get();

            if ($dispatches->isEmpty()) {
                throw new \InvalidArgumentException('No unbilled dispatches found for this transport company.');
            }

            // Determine bill period from dispatch dates
            $dates     = $dispatches->pluck('flight_date')->filter();
            $periodFrom = $dates->min();
            $periodTo   = $dates->max();

            $taxRate = (float) ($meta['tax_rate'] ?? 0);

            $bill = TransportBill::create([
                'bill_ref'             => TransportBill::generateRef(),
                'transport_company_id' => $company->id,
                'period_from'          => $periodFrom,
                'period_to'            => $periodTo,
                'subtotal'             => 0,
                'tax_rate'             => $taxRate,
                'tax_amount'           => 0,
                'total_amount'         => 0,
                'amount_paid'          => 0,
                'balance_due'          => 0,
                'status'               => 'draft',
                'notes'                => $meta['notes'] ?? null,
                'created_by'           => Auth::id(),
            ]);

            $subtotal = 0;

            foreach ($dispatches as $dispatch) {
                // Calculate cost from vehicles used
                $vehicleCost = $dispatch->transport_cost
                    ?? $dispatch->calculateCost();

                $vehiclesUsed = $dispatch->dispatchDriverRows->count();

                $description = "{$dispatch->dispatch_ref} — "
                    . ($dispatch->flight_date?->format('d/m/Y') ?? 'N/A')
                    . " — {$dispatch->total_pax} PAX";

                TransportBillItem::create([
                    'transport_bill_id' => $bill->id,
                    'dispatch_id'       => $dispatch->id,
                    'description'       => $description,
                    'vehicles_used'     => $vehiclesUsed,
                    'vehicle_cost'      => $vehicleCost,
                    'line_total'        => $vehicleCost,
                ]);

                $subtotal += $vehicleCost;

                // Stamp the dispatch as billed
                $dispatch->update([
                    'transport_bill_id' => $bill->id,
                    'billed_at'         => now(),
                ]);
            }

            $taxAmount   = round($subtotal * ($taxRate / 100), 2);
            $totalAmount = $subtotal + $taxAmount;

            $bill->update([
                'subtotal'     => $subtotal,
                'tax_amount'   => $taxAmount,
                'total_amount' => $totalAmount,
                'balance_due'  => $totalAmount,
            ]);

            return $bill->fresh();
        });
    }

    /**
     * Generate PDF for a transport bill.
     */
    public function generatePdf(TransportBill $bill): \Barryvdh\DomPDF\PDF
    {
        $bill->load(['transportCompany', 'items.dispatch', 'createdBy']);

        $appSettings = app(\App\Settings\AppSettings::class);

        return Pdf::loadView('pdf.transport-bill', [
            'bill'     => $bill,
            'company'  => $bill->transportCompany,
            'settings' => $appSettings,
        ])->setPaper('a4', 'portrait');
    }

    /**
     * Mark a transport bill as sent.
     */
    public function markSent(TransportBill $bill): TransportBill
    {
        $bill->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);

        return $bill->fresh();
    }

    /**
     * Mark a transport bill as paid.
     */
    public function markPaid(TransportBill $bill, ?string $paymentRef = null): TransportBill
    {
        $bill->update([
            'status'            => 'paid',
            'paid_at'           => now(),
            'payment_reference' => $paymentRef,
            'amount_paid'       => $bill->total_amount,
            'balance_due'       => 0,
        ]);

        return $bill->fresh();
    }
}
