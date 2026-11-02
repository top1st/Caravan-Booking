<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(Booking::STATUSES)],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ]);

        $bookings = Booking::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return response()->json($bookings);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        return response()->json($this->bookings->create($request->validated()), 201);
    }

    public function show(Booking $booking): Booking
    {
        return $booking;
    }

    public function update(UpdateBookingRequest $request, Booking $booking): Booking
    {
        return $this->bookings->update($booking->getKey(), $request->validated());
    }

    public function destroy(Booking $booking): Response
    {
        $booking->delete();

        return response()->noContent();
    }
}
