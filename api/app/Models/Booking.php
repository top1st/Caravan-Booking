<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'cancelled'];

    protected $fillable = [
        'customer_name',
        'customer_email',
        'rego',
        'service_bay',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s\Z');
    }
}
