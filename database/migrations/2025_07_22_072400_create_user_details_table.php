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
        Schema::create('user_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('phone', 50);
            $table->string('placebirth', 100);
            $table->date('datebirth');
            $table->enum('gender', ['m', 'w'])->default('m');
            $table->enum('blood', ['a', 'b', 'o', 'ab'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'widow', 'widower'])->nullable();
            $table->enum('religion', ['islam', 'protestan', 'khatolik', 'hindu', 'buddha', 'khonghucu'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
