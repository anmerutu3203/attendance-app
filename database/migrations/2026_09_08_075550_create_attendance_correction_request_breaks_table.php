<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_request_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_correction_request_id');
            $table->foreign('attendance_correction_request_id', 'acr_breaks_request_id_foreign')
                ->references('id')->on('attendance_correction_requests')
                ->cascadeOnDelete();

            $table->foreignId('break_id')->nullable();
            $table->foreign('break_id', 'acr_breaks_break_id_foreign')
                ->references('id')->on('breaks')
                ->nullOnDelete();

            $table->time('requested_break_in')->nullable();
            $table->time('requested_break_out')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_request_breaks');
    }
};