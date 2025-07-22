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
        Schema::create('qr_presences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', ['in', 'out'])->default('in');
            $table->unsignedBigInteger('departement_id');
            $table->unsignedBigInteger('timework_id');
            $table->string('token');
            $table->dateTime('for_presence');
            $table->timestamp('expires_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_presences');
    }
};
