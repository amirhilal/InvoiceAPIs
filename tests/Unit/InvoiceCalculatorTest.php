<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\InvoiceCalculator;
use Carbon\Carbon;

class InvoiceCalculatorTest extends TestCase
{
    public function test_calculates_highest_event_charge(): void
    {
        $calculator = new InvoiceCalculator();
        
        $events = [
            'registration' => '2024-01-15',
            'activation' => '2024-01-20',
            'appointment' => '2024-01-25'
        ];

        $this->assertEquals(200, $calculator->calculateHighestCharge($events));
    }
}
