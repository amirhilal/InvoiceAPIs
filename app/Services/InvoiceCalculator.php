<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * TODO: I think this class should be refactored to use a queued job to handle large invoices
 */
class InvoiceCalculator
{
    /**
     * Defines the base prices for different event types
     */
    private const PRICES = [
        'registration' => 50,
        'activation' => 100,
        'appointment' => 200
    ];

    private string $currentInvoiceStartDate;

    /**
     * Calculates the highest charge based on the events array
     * Returns the price of the most expensive event type present
     * 
     * @param array $events Array of events with their timestamps
     * @return float The highest charge amount
     */
    public function calculateHighestCharge(array $events): float
    {
        if (isset($events['appointment'])) {
            return self::PRICES['appointment'];
        }
        
        if (isset($events['activation'])) {
            return self::PRICES['activation'];
        }
        
        if (isset($events['registration'])) {
            return self::PRICES['registration'];
        }

        return 0;
    }

    /**
     * Gets all previous charges for a user and checks if event type was already charged
     * 
     * @param mixed $user The user to get previous charges for
     * @param string $eventType The current event type being checked
     * @return bool Whether this event type was previously charged
     */
    private function wasEventPreviouslyCharged($user, string $eventType): bool
    {
        return InvoiceItem::query()
            ->where('user_id', $user->id)
            ->where('event_type', $eventType)
            ->exists();
    }

    /**
     * Creates a new invoice for a customer within the specified date range
     * Calculates charges for each user, considering previous charges
     * 
     * @param Customer $customer The customer to create invoice for
     * @param string $startDate Start date of the invoice period
     * @param string $endDate End date of the invoice period
     * @return Invoice The created invoice with its items
     */
    public function createInvoice(Customer $customer, string $startDate, string $endDate): Invoice
    {
        $this->currentInvoiceStartDate = $startDate;

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 0
        ]);

        $totalAmount = 0;

        foreach ($customer->users as $user) {
            $events = $this->getUserEvents($user, $startDate, $endDate);
            if (empty($events)) {
                continue;
            }

            $eventType = $this->determineEventType($events);
            
            // Skip if this event type was already charged before
            if ($this->wasEventPreviouslyCharged($user, $eventType)) {
                continue;
            }

            $charge = self::PRICES[$eventType];
            $previousCharge = $this->getPreviousCharge($user);
            $finalCharge = max(0, $charge - $previousCharge);

            if ($finalCharge > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'event_type' => $eventType,
                    'amount' => $finalCharge
                ]);
                $totalAmount += $finalCharge;
            }
        }

        $invoice->update(['total_amount' => $totalAmount]);
        return $invoice->load('items');
    }

    /**
     * Gets all relevant events for a user within the specified date range
     * 
     * @param mixed $user The user to get events for
     * @param string $startDate Start of the date range
     * @param string $endDate End of the date range
     * @return array Array of events with their timestamps
     */
    private function getUserEvents($user, $startDate, $endDate): array
    {
        $events = [];
        
        if ($user->created_at && $user->created_at->between($startDate, $endDate)) {
            $events['registration'] = $user->created_at;
        }

        $session = $user->sessions()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('activated_at', [$startDate, $endDate])
                    ->orWhereBetween('appointment_at', [$startDate, $endDate]);
            })
            ->first();

        if ($session) {
            if ($session->activated_at) {
                $events['activation'] = $session->activated_at;
            }
            if ($session->appointment_at) {
                $events['appointment'] = $session->appointment_at;
            }
        }

        return $events;
    }

    /**
     * Gets the highest previous charge for a user from past invoices
     * 
     * @param mixed $user The user to get previous charge for
     * @return float The amount of the previous charge or 0 if none exists
     */
    private function getPreviousCharge($user): float
    {
        $previousCharge = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.user_id', $user->id)
            ->where('invoices.end_date', '<', $this->currentInvoiceStartDate)
            ->orderByDesc('invoices.end_date')
            ->value('invoice_items.amount');

        return (float) ($previousCharge ?? 0);
    }

    /**
     * Determines the highest-priority event type from the events array
     * 
     * @param array $events Array of events with their timestamps
     * @return string The event type (appointment, activation, or registration)
     */
    private function determineEventType(array $events): string
    {
        if (isset($events['appointment'])) return 'appointment';
        if (isset($events['activation'])) return 'activation';
        return 'registration';
    }
} 