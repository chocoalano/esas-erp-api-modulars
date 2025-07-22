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
        Schema::create('user_families', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('fullname', 100);
            $table->enum('relationship', ['wife', 'husband', 'mother', 'father', 'brother', 'sister', 'child'])->default('wife');
            $table->date('birthdate');
            $table->enum('marital_status', ['single', 'married', 'widow', 'widower'])->nullable();
            $table->string('job', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_families');
    }
};
