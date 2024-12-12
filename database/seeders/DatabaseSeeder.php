<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Session;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 customers
        Customer::factory(5)->create()->each(function ($customer) {
            // Create 3-10 users for each customer
            User::factory(rand(3, 10))->create([
                'customer_id' => $customer->id
            ])->each(function ($user) {
                // Create 0-3 sessions for each user
                Session::factory(rand(0, 3))->create([
                    'user_id' => $user->id
                ]);
            });

            // Create 1-5 invoices for each customer
            Invoice::factory(rand(1, 5))->create([
                'customer_id' => $customer->id
            ])->each(function ($invoice) use ($customer) {
                // Get random users from this customer
                $users = $customer->users->random(rand(1, 3));
                
                // Create invoice items for each selected user
                foreach ($users as $user) {
                    InvoiceItem::factory()->create([
                        'invoice_id' => $invoice->id,
                        'user_id' => $user->id
                    ]);
                }

                // Update invoice total_amount based on items
                $invoice->update([
                    'total_amount' => $invoice->items->sum('amount')
                ]);
            });
        });
    }
}
