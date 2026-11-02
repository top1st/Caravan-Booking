<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::apiResource('bookings', BookingController::class);
