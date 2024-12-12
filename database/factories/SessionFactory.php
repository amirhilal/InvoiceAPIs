<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-1 year', 'now');
        $activatedAt = $this->faker->boolean(80) ? 
            $this->faker->dateTimeBetween($createdAt, '+2 months') : 
            null;
        
        return [
            'user_id' => User::factory(),
            'activated_at' => $activatedAt,
            'appointment_at' => $activatedAt && $this->faker->boolean(60) ? 
                $this->faker->dateTimeBetween($activatedAt, '+2 months') : 
                null,
        ];
    }
} 