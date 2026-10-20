<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique(); // A, B, C, ..., O
            $table->string('start_time', 10);    // 07:30
            $table->string('end_time', 10);      // 08:20
            $table->string('label', 50);         // 07:30 - 08:20
            $table->integer('order');            // 1 s.d. 15
            $table->integer('duration_minutes'); // 50 / 60
            $table->string('description');       // SKS Pagi 1, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_slots');
    }
};
