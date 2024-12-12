<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 month');

        return [
            'customer_id' => Customer::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => $this->faker->randomFloat(2, 50, 1000),
        ];
    }
} 