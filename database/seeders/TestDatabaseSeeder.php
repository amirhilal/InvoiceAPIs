<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Session;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Static test customer
        $customer = Customer::factory()->create([
            'name' => 'Test Customer'
        ]);

        // Create a previous invoice for December 2023
        $previousInvoice = Invoice::create([
            'customer_id' => $customer->id,
            'start_date' => '2023-12-01',
            'end_date' => '2023-12-31',
            'total_amount' => 150.00
        ]);

        // Three users with different scenarios
        $user1 = User::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'User 1 (Registration Only)',
            'created_at' => '2024-01-15'
        ]);

        $user2 = User::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'User 2 (Registration + Activation)',
            'created_at' => '2024-01-15'
        ]);
        Session::factory()->create([
            'user_id' => $user2->id,
            'activated_at' => '2024-01-20',
            'appointment_at' => null
        ]);
        // Previous registration charge for User 2
        InvoiceItem::create([
            'invoice_id' => $previousInvoice->id,
            'user_id' => $user2->id,
            'event_type' => 'registration',
            'amount' => 50.00
        ]);

        $user3 = User::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'User 3 (All Events)',
            'created_at' => '2024-01-15'
        ]);
        Session::factory()->create([
            'user_id' => $user3->id,
            'activated_at' => '2024-01-20',
            'appointment_at' => '2024-01-25'
        ]);
        // Previous registration charge for User 3
        InvoiceItem::create([
            'invoice_id' => $previousInvoice->id,
            'user_id' => $user3->id,
            'event_type' => 'registration',
            'amount' => 50.00
        ]);
    }
} 