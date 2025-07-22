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
        Schema::create('permits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('permit_numbers', 100);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permit_type_id');
            $table->unsignedBigInteger('user_timework_schedule_id');
            $table->time('timein_adjust')->nullable();
            $table->time('timeout_adjust')->nullable();
            $table->unsignedBigInteger('current_shift_id')->nullable();
            $table->unsignedBigInteger('adjust_shift_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->longText('notes')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permits');
    }
};
