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
        Schema::create('user_formal_educations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('institution', 100);
            $table->string('majors', 100);
            $table->decimal('score', 4)->default(0);
            $table->year('start')->nullable();
            $table->year('finish')->nullable();
            $table->enum('status', ['passed', 'not-passed', 'in-progress']);
            $table->boolean('certification')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_formal_educations');
    }
};
