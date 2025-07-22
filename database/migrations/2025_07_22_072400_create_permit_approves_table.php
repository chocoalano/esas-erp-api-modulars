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
        Schema::create('permit_approves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('permit_id');
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->enum('user_approve', ['w', 'n', 'y'])->default('w');
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permit_approves');
    }
};
