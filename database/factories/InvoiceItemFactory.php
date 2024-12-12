<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $eventTypes = ['registration', 'activation', 'appointment'];
        $prices = [50, 100, 200];
        
        $eventIndex = $this->faker->numberBetween(0, 2);

        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'event_type' => $eventTypes[$eventIndex],
            'amount' => $prices[$eventIndex],
        ];
    }
} 