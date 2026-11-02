<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BookingService
{
    public function create(array $attributes): Booking
    {
        return $this->save($attributes);
    }

    public function update(int $id, array $attributes): Booking
    {
        return $this->save($attributes, $id);
    }

    private function save(array $attributes, ?int $id = null): Booking
    {
        foreach (['starts_at', 'ends_at'] as $field) {
            if (isset($attributes[$field])) {
                // Eloquent would keep the wall clock of a non-UTC offset, so convert first.
                $attributes[$field] = CarbonImmutable::parse($attributes[$field])->utc();
            }
        }

        // 3 attempts: retries "database is locked" after busy_timeout expires; other errors are not retried.
        return DB::transaction(function () use ($attributes, $id) {
            $this->acquireWriteLock();

            // Re-read under the lock so a partial update merges with current values, not stale ones.
            $booking = $id === null ? new Booking : Booking::findOrFail($id);
            $booking->fill($attributes);

            if ($booking->ends_at->lte($booking->starts_at)) {
                throw ValidationException::withMessages(['ends_at' => 'The end time must be after the start time.']);
            }

            if ($booking->status !== 'cancelled' && $this->overlapExists($booking)) {
                $message = 'This service bay is already booked for this time range.';

                throw ValidationException::withMessages(['starts_at' => $message, 'service_bay' => $message]);
            }

            $booking->save();

            return $booking->refresh();
        }, 3);
    }

    /**
     * Must be the first statement in the transaction: taking SQLite's write lock before
     * the availability read is what stops two requests both passing the overlap check.
     */
    private function acquireWriteLock(): void
    {
        $locked = DB::table('booking_write_locks')->where('id', 1)->increment('version');

        // Zero rows updated means no write lock was taken, so the overlap check would be unsafe.
        if ($locked !== 1) {
            throw new RuntimeException('The booking_write_locks row is missing; run the migrations.');
        }
    }

    private function overlapExists(Booking $booking): bool
    {
        return Booking::query()
            ->where('service_bay', $booking->service_bay)
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', $booking->ends_at)
            ->where('ends_at', '>', $booking->starts_at)
            ->when($booking->exists, fn ($query) => $query->whereKeyNot($booking->getKey()))
            ->exists();
    }
}
