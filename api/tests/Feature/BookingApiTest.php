<?php

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

function payload(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'Sam Taylor',
        'customer_email' => 'sam@example.com',
        'rego' => 'ABC123',
        'service_bay' => 'Bay 1',
        'starts_at' => '2026-12-01T09:00:00Z',
        'ends_at' => '2026-12-01T10:00:00Z',
        'status' => 'confirmed',
        'notes' => null,
    ], $overrides);
}

function createBooking(array $overrides = []): Booking
{
    return Booking::create(array_merge(payload(), [
        'starts_at' => '2026-12-01 09:00:00',
        'ends_at' => '2026-12-01 10:00:00',
    ], $overrides));
}

describe('CRUD', function () {
    it('creates a booking and returns 201', function () {
        $this->postJson('/api/bookings', payload(['notes' => 'Check brakes']))
            ->assertCreated()
            ->assertJsonPath('customer_name', 'Sam Taylor')
            ->assertJsonPath('notes', 'Check brakes')
            ->assertJsonPath('starts_at', '2026-12-01T09:00:00Z')
            ->assertJsonStructure(['id', 'created_at', 'updated_at']);

        expect(Booking::count())->toBe(1);
    });

    it('returns null notes when notes are omitted', function () {
        $body = payload();
        unset($body['notes']);

        $this->postJson('/api/bookings', $body)->assertCreated()->assertJsonPath('notes', null);
    });

    it('shows a booking', function () {
        $booking = createBooking();

        $this->getJson("/api/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('id', $booking->id)
            ->assertJsonPath('ends_at', '2026-12-01T10:00:00Z');
    });

    it('updates a booking with PUT', function () {
        $booking = createBooking();

        $this->putJson("/api/bookings/{$booking->id}", payload(['customer_name' => 'Alex Doe']))
            ->assertOk()
            ->assertJsonPath('customer_name', 'Alex Doe');
    });

    it('requires every field on PUT', function () {
        $booking = createBooking();

        $this->putJson("/api/bookings/{$booking->id}", ['customer_name' => 'Alex Doe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_email', 'starts_at', 'status']);
    });

    it('applies a partial update with PATCH', function () {
        $booking = createBooking();

        $this->patchJson("/api/bookings/{$booking->id}", ['status' => 'pending'])
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('customer_name', 'Sam Taylor');
    });

    it('deletes a booking and returns 204', function () {
        $booking = createBooking();

        $this->deleteJson("/api/bookings/{$booking->id}")->assertNoContent();

        expect(Booking::count())->toBe(0);
    });

    it('returns a JSON 404 for a missing booking on every verb', function (string $method) {
        $this->json($method, '/api/bookings/999', payload())
            ->assertNotFound()
            ->assertExactJson(['message' => 'Booking not found.']);
    })->with(['GET', 'PUT', 'PATCH', 'DELETE']);

    it('returns JSON errors without an Accept header', function () {
        $this->post('/api/bookings', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name']);
    });
});

describe('validation', function () {
    it('rejects bad input with a field-scoped 422', function (array $overrides, string $field) {
        $this->postJson('/api/bookings', payload($overrides))
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => [$field]])
            ->assertOnlyJsonValidationErrors([$field]);

        expect(Booking::count())->toBe(0);
    })->with([
        'missing name' => [['customer_name' => null], 'customer_name'],
        'invalid email' => [['customer_email' => 'nope'], 'customer_email'],
        'missing rego' => [['rego' => ''], 'rego'],
        'missing bay' => [['service_bay' => ''], 'service_bay'],
        'missing start' => [['starts_at' => null], 'starts_at'],
        'unknown status' => [['status' => 'done'], 'status'],
        'date without offset' => [['starts_at' => '2026-12-01T09:00:00'], 'starts_at'],
        'impossible date' => [['starts_at' => '2026-02-30T09:00:00Z'], 'starts_at'],
        'not a date' => [['ends_at' => 'tomorrow'], 'ends_at'],
        'end before start' => [['ends_at' => '2026-12-01T08:00:00Z'], 'ends_at'],
        'end equal to start' => [['ends_at' => '2026-12-01T09:00:00Z'], 'ends_at'],
        'array as name' => [['customer_name' => ['x']], 'customer_name'],
    ]);

    it('ignores a client supplied id', function () {
        $this->postJson('/api/bookings', payload(['id' => 500]))
            ->assertCreated()
            ->assertJsonPath('id', 1);
    });

    it('rejects a PATCH that moves the end before the stored start', function () {
        $booking = createBooking();

        $this->patchJson("/api/bookings/{$booking->id}", ['ends_at' => '2026-12-01T08:00:00Z'])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['ends_at']);
    });
});

describe('timestamps', function () {
    it('normalizes offsets to UTC', function () {
        $this->postJson('/api/bookings', payload([
            'starts_at' => '2026-12-01T19:00:00+10:00',
            'ends_at' => '2026-12-01T20:00:00.000+10:00',
        ]))
            ->assertCreated()
            ->assertJsonPath('starts_at', '2026-12-01T09:00:00Z')
            ->assertJsonPath('ends_at', '2026-12-01T10:00:00Z');
    });

    it('detects overlap across different offsets', function () {
        createBooking();

        $this->postJson('/api/bookings', payload([
            'starts_at' => '2026-12-01T19:30:00+10:00',
            'ends_at' => '2026-12-01T20:30:00+10:00',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['starts_at']);
    });
});

describe('list', function () {
    it('paginates newest first with a deterministic order', function () {
        foreach (range(1, 16) as $i) {
            createBooking(['service_bay' => "Bay $i"]);
        }

        $first = $this->getJson('/api/bookings')->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('total', 16)
            ->assertJsonCount(15, 'data');

        // All rows share a created_at second, so this order comes from the id tiebreaker.
        expect($first->json('data.0.id'))->toBe(16);

        $this->getJson('/api/bookings?page=2')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 1);
    });

    it('filters by status and keeps the filter in page links', function () {
        foreach (range(1, 16) as $i) {
            createBooking(['service_bay' => "Bay $i"]);
        }
        createBooking(['service_bay' => 'Bay 99', 'status' => 'pending']);

        $response = $this->getJson('/api/bookings?status=confirmed&page=2')->assertOk()
            ->assertJsonPath('total', 16)
            ->assertJsonCount(1, 'data');

        expect($response->json('first_page_url'))->toContain('status=confirmed');

        $this->getJson('/api/bookings?status=pending')->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.status', 'pending');
    });

    it('rejects invalid filters instead of returning misleading results', function (string $query, string $field) {
        $this->getJson("/api/bookings?$query")
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([$field]);
    })->with([
        ['status=done', 'status'],
        ['status[]=confirmed', 'status'],
        ['page=0', 'page'],
        ['page=abc', 'page'],
    ]);
});

describe('overlap', function () {
    it('rejects overlapping ranges in the same bay', function (string $start, string $end) {
        createBooking();

        $this->postJson('/api/bookings', payload(['starts_at' => $start, 'ends_at' => $end]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.starts_at.0', 'This service bay is already booked for this time range.')
            ->assertJsonValidationErrors(['service_bay']);

        expect(Booking::count())->toBe(1);
    })->with([
        'identical' => ['2026-12-01T09:00:00Z', '2026-12-01T10:00:00Z'],
        'overlaps the start' => ['2026-12-01T08:30:00Z', '2026-12-01T09:30:00Z'],
        'overlaps the end' => ['2026-12-01T09:30:00Z', '2026-12-01T10:30:00Z'],
        'inside' => ['2026-12-01T09:15:00Z', '2026-12-01T09:45:00Z'],
        'encloses' => ['2026-12-01T08:00:00Z', '2026-12-01T11:00:00Z'],
    ]);

    it('accepts adjacent slots on both sides', function (string $start, string $end) {
        createBooking();

        $this->postJson('/api/bookings', payload(['starts_at' => $start, 'ends_at' => $end]))->assertCreated();
    })->with([
        'ends at the existing start' => ['2026-12-01T08:00:00Z', '2026-12-01T09:00:00Z'],
        'starts at the existing end' => ['2026-12-01T10:00:00Z', '2026-12-01T11:00:00Z'],
    ]);

    it('allows the same time in a different bay', function () {
        createBooking();

        $this->postJson('/api/bookings', payload(['service_bay' => 'Bay 2']))->assertCreated();
    });

    it('treats bay names case-insensitively and trims them', function () {
        createBooking();

        $this->postJson('/api/bookings', payload(['service_bay' => ' bay 1 ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    });

    it('treats a pending booking as blocking', function () {
        createBooking(['status' => 'pending']);

        $this->postJson('/api/bookings', payload())->assertUnprocessable();
    });

    it('does not let a cancelled booking block', function () {
        createBooking(['status' => 'cancelled']);

        $this->postJson('/api/bookings', payload())->assertCreated();
    });

    it('allows creating a cancelled booking over an active one', function () {
        createBooking();

        $this->postJson('/api/bookings', payload(['status' => 'cancelled']))->assertCreated();
    });

    it('excludes the edited booking from its own overlap check', function () {
        $booking = createBooking();

        $this->putJson("/api/bookings/{$booking->id}", payload(['ends_at' => '2026-12-01T10:30:00Z']))
            ->assertOk()
            ->assertJsonPath('ends_at', '2026-12-01T10:30:00Z');
    });

    it('rejects an update that moves into another booking', function () {
        createBooking();
        $other = createBooking(['starts_at' => '2026-12-01 11:00:00', 'ends_at' => '2026-12-01 12:00:00']);

        $this->patchJson("/api/bookings/{$other->id}", ['starts_at' => '2026-12-01T09:30:00Z'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);

        expect($other->refresh()->starts_at->toDateTimeString())->toBe('2026-12-01 11:00:00');
    });

    it('rejects reactivating a cancelled booking into a taken slot', function () {
        $cancelled = createBooking(['status' => 'cancelled']);
        createBooking();

        $this->patchJson("/api/bookings/{$cancelled->id}", ['status' => 'confirmed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);

        expect($cancelled->refresh()->status)->toBe('cancelled');
    });

    it('allows reactivating a cancelled booking into a free slot', function () {
        $cancelled = createBooking(['status' => 'cancelled']);

        $this->patchJson("/api/bookings/{$cancelled->id}", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('status', 'confirmed');
    });
});

describe('write lock', function () {
    it('bumps the lock version on every create and update', function () {
        $version = fn () => DB::table('booking_write_locks')->where('id', 1)->value('version');
        expect($version())->toBe(0);

        $id = $this->postJson('/api/bookings', payload())->assertCreated()->json('id');
        $this->patchJson("/api/bookings/$id", ['status' => 'pending'])->assertOk();

        expect($version())->toBe(2);
    });

    it('refuses to write when the lock row is missing', function () {
        DB::table('booking_write_locks')->delete();
        $this->withoutExceptionHandling();

        expect(fn () => $this->postJson('/api/bookings', payload()))->toThrow(RuntimeException::class);
        expect(Booking::count())->toBe(0);
    });
});
