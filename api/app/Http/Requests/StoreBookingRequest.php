<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    // ISO-8601 with a required offset, so a timestamp is never ambiguous about its timezone.
    private const ISO_8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d{1,6})?)?(Z|[+-]\d{2}:\d{2})$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
            'rego' => ['required', 'string', 'max:20'],
            'service_bay' => ['required', 'string', 'max:50'],
            'starts_at' => ['required', 'string', 'regex:'.self::ISO_8601, 'date'],
            'ends_at' => ['required', 'string', 'regex:'.self::ISO_8601, 'date'],
            'status' => ['required', 'string', Rule::in(Booking::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.regex' => 'The start time must be ISO-8601 with a timezone offset, e.g. 2026-11-02T09:00:00Z.',
            'ends_at.regex' => 'The end time must be ISO-8601 with a timezone offset, e.g. 2026-11-02T10:00:00Z.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                return;
            }

            // A partial update may send only one side, so fall back to the stored value.
            $booking = $this->route('booking');
            $start = $this->input('starts_at', $booking?->starts_at);
            $end = $this->input('ends_at', $booking?->ends_at);

            if ($start && $end && CarbonImmutable::parse($end)->lte(CarbonImmutable::parse($start))) {
                $validator->errors()->add('ends_at', 'The end time must be after the start time.');
            }
        }];
    }
}
