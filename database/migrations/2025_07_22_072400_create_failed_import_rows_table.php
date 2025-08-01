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
        Schema::create('failed_import_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('data');
            $table->unsignedBigInteger('import_id');
            $table->text('validation_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
    }
};
