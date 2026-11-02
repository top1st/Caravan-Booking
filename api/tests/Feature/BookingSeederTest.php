<?php

use App\Models\Booking;
use Database\Seeders\BookingSeeder;

it('seeds 10 bookings covering every status', function () {
    $this->seed(BookingSeeder::class);

    expect(Booking::count())->toBe(10);
    expect(Booking::distinct()->pluck('status')->sort()->values()->all())->toBe(['cancelled', 'confirmed', 'pending']);
});

it('adds nothing when bookings already exist', function () {
    $this->seed(BookingSeeder::class);
    $this->seed(BookingSeeder::class);

    expect(Booking::count())->toBe(10);
});
