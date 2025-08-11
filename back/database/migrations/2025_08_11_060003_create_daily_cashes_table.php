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
        Schema::create('daily_cashes', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();                // día
            $table->decimal('opening_amount',12,2)->default(0); // caja inicial
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note')->nullable();
            $table->boolean('closed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_cashes');
    }
};
