<?php

namespace App\Http\Requests;

class UpdateBookingRequest extends StoreBookingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // PUT replaces every field; PATCH validates only the fields it sends.
        return $this->isMethod('PATCH')
            ? array_map(fn (array $fieldRules) => ['sometimes', ...$fieldRules], $rules)
            : $rules;
    }
}
