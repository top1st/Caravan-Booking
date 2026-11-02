<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('rego');
            // NOCASE so "bay 1" and "Bay 1" are the same bay for the overlap check.
            $table->string('service_bay')->collation('nocase');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['service_bay', 'starts_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
