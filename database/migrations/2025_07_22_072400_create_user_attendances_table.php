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
        Schema::create('user_attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('user_timework_schedule_id')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('type_in', ['qrcode', 'face-device', 'face-geolocation'])->nullable();
            $table->enum('type_out', ['qrcode', 'face-device', 'face-geolocation'])->nullable();
            $table->string('lat_in', 100)->nullable();
            $table->string('lat_out', 100)->nullable();
            $table->string('long_in', 100)->nullable();
            $table->string('long_out', 100)->nullable();
            $table->string('image_in')->nullable();
            $table->string('image_out')->nullable();
            $table->enum('status_in', ['late', 'unlate', 'normal'])->default('normal');
            $table->enum('status_out', ['late', 'unlate', 'normal'])->default('normal');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_attendances');
    }
};
