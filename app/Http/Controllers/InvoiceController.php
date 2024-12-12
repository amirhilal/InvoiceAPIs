<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceController extends Controller
{
    /**
     * Creates a new invoice for a customer
     * Validates input and uses InvoiceCalculator service to generate the invoice
     * 
     * @param Request $request The HTTP request containing customer_id and date range
     * @param InvoiceCalculator $calculator The invoice calculation service
     * @return JsonResponse The created invoice data or error response
     */
    public function store(Request $request, InvoiceCalculator $calculator): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start'
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);

            //TODO: Change this to a queued job to handle large invoices
            $invoice = $calculator->createInvoice(
                $customer,
                $validated['start'],
                $validated['end']
            );

            $invoice->load('items');

            return response()->json([
                'data' => [
                    'id' => $invoice->id,
                    'total_amount' => number_format($invoice->total_amount, 2),
                    'items' => $invoice->items->map(fn($item) => [
                        'user_id' => $item->user_id,
                        'event_type' => $item->event_type,
                        'amount' => number_format($item->amount, 2)
                    ])
                ]
            ], 201);
        } catch (ModelNotFoundException $e) {
            Log::error('InvoiceController@store: Customer not found', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to create invoice',
            ], 500);
        }
    }

    /**
     * Retrieves an existing invoice with its items
     * 
     * @param Invoice $invoice The invoice model (automatically resolved by Laravel)
     * @return JsonResponse The invoice data
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load('items');

        return response()->json([
            'data' => [
                'id' => $invoice->id,
                'total_amount' => number_format($invoice->total_amount, 2),
                'items' => $invoice->items->map(fn($item) => [
                    'user_id' => $item->user_id,
                    'event_type' => $item->event_type,
                    'amount' => number_format($item->amount, 2)
                ])
            ]
        ]);
    }
} 