<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    private const BOOKINGS = [
        ['Olivia Nguyen', 'olivia.nguyen@example.com', 'CVN101', 'Bay 1', '2026-12-07T09:00:00Z', '2026-12-07T11:00:00Z', 'confirmed', 'Annual service and brake check.'],
        ['Liam Walker', 'liam.walker@example.com', 'CVN102', 'Bay 1', '2026-12-07T11:00:00Z', '2026-12-07T12:30:00Z', 'confirmed', 'Adjacent to the previous booking.'],
        ['Mia Thompson', 'mia.thompson@example.com', 'TRV203', 'Bay 2', '2026-12-07T09:00:00Z', '2026-12-07T10:00:00Z', 'pending', null],
        ['Noah Patel', 'noah.patel@example.com', 'TRV204', 'Bay 2', '2026-12-07T13:00:00Z', '2026-12-07T15:00:00Z', 'confirmed', 'Replace awning arm.'],
        ['Ava Wilson', 'ava.wilson@example.com', 'RVR305', 'Bay 3', '2026-12-08T08:30:00Z', '2026-12-08T10:30:00Z', 'confirmed', 'Gas compliance certificate.'],
        ['Ethan Kelly', 'ethan.kelly@example.com', 'RVR306', 'Bay 3', '2026-12-08T09:00:00Z', '2026-12-08T10:00:00Z', 'cancelled', 'Cancelled, so it does not block the booking above.'],
        ['Isla Martin', 'isla.martin@example.com', 'OFF407', 'Bay 1', '2026-12-08T14:00:00Z', '2026-12-08T16:00:00Z', 'pending', 'Solar panel quote.'],
        ['Jack Robinson', 'jack.robinson@example.com', 'OFF408', 'Bay 2', '2026-12-09T09:00:00Z', '2026-12-09T12:00:00Z', 'confirmed', 'Wheel bearings, all axles.'],
        ['Grace Lee', 'grace.lee@example.com', 'POP509', 'Bay 3', '2026-12-09T10:00:00Z', '2026-12-09T11:00:00Z', 'pending', null],
        ['Henry Davis', 'henry.davis@example.com', 'POP510', 'Bay 1', '2026-12-10T08:00:00Z', '2026-12-10T09:30:00Z', 'cancelled', 'Customer rescheduling.'],
    ];

    public function run(BookingService $bookings): void
    {
        // These fixed slots would collide on a re-run or with real data, so never add to an existing table.
        if (Booking::exists()) {
            $this->command?->warn('Bookings already exist; skipping example data. Use migrate:fresh --seed to start over.');

            return;
        }

        foreach (self::BOOKINGS as [$name, $email, $rego, $bay, $start, $end, $status, $notes]) {
            $bookings->create([
                'customer_name' => $name,
                'customer_email' => $email,
                'rego' => $rego,
                'service_bay' => $bay,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => $status,
                'notes' => $notes,
            ]);
        }
    }
}
