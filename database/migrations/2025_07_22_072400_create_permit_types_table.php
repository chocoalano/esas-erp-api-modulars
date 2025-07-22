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
        Schema::create('permit_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 100);
            $table->boolean('is_payed')->default(true);
            $table->boolean('approve_line')->default(true);
            $table->boolean('approve_manager')->default(true);
            $table->boolean('approve_hr')->default(true);
            $table->boolean('with_file')->default(false);
            $table->boolean('show_mobile')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permit_types');
    }
};
