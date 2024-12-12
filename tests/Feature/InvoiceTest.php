<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\User;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_invoice(): void
    {
        // Create test data using our TestDatabaseSeeder
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);

        // Get the test customer
        $customer = Customer::where('name', 'Test Customer')->first();

        // Create invoice for January 2024
        $response = $this->postJson('/api/invoices', [
            'customer_id' => $customer->id,
            'start' => '2024-01-01',
            'end' => '2024-01-31'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'total_amount',
                    'items' => [
                        '*' => [
                            'user_id',
                            'event_type',
                            'amount'
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data');
        $items = collect($data['items']);

        // User 1: Registration only (50 SAR)
        $this->assertTrue($items->contains(function ($item) {
            return $item['event_type'] === 'registration' && $item['amount'] === '50.00';
        }));

        // User 2: Activation (100 SAR - 50 SAR previous = 50 SAR)
        $this->assertTrue($items->contains(function ($item) {
            return $item['event_type'] === 'activation' && $item['amount'] === '50.00';
        }));

        // User 3: Appointment (200 SAR - 50 SAR previous = 150 SAR)
        $this->assertTrue($items->contains(function ($item) {
            return $item['event_type'] === 'appointment' && $item['amount'] === '150.00';
        }));

        // Total amount should be 250 SAR (50 + 50 + 150)
        $this->assertEquals('250.00', $data['total_amount']);
    }

    public function test_can_view_invoice(): void
    {
        // Create test data
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
        $customer = Customer::where('name', 'Test Customer')->first();

        // Create an invoice first
        $createResponse = $this->postJson('/api/invoices', [
            'customer_id' => $customer->id,
            'start' => '2024-01-01',
            'end' => '2024-01-31'
        ]);

        $invoiceId = $createResponse->json('data.id');

        // Get the invoice
        $response = $this->getJson("/api/invoices/{$invoiceId}");

        // $invoice = Invoice::with('items')->find($invoiceId);
        // \Log::debug('Invoice data for assertion in can view invoice', [
        //     'invoice' => $invoice->toArray()
        // ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'total_amount',
                    'items' => [
                        '*' => [
                            'user_id',
                            'event_type',
                            'amount'
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(250.00, $data['total_amount']);
    }

    public function test_events_are_charged_only_once(): void
    {
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
        $customer = Customer::where('name', 'Test Customer')->first();

        // Create first invoice
        $response1 = $this->postJson('/api/invoices', [
            'customer_id' => $customer->id,
            'start' => '2024-01-01',
            'end' => '2024-01-31'
        ]);

        // Create second invoice for next month
        $response2 = $this->postJson('/api/invoices', [
            'customer_id' => $customer->id,
            'start' => '2024-02-01',
            'end' => '2024-02-28'
        ]);

        $data2 = $response2->json('data');
        
        // Second invoice should not charge for the same events
        $this->assertEquals('0.00', $data2['total_amount']);
        $this->assertEmpty($data2['items']);
    }
}
