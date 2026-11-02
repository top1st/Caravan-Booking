<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_write_locks', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('version')->default(0);
        });

        DB::table('booking_write_locks')->insert(['id' => 1, 'version' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_write_locks');
    }
};
