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
        Schema::create('documentations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->comment('Judul dokumentasi');
            $table->string('subtitle')->nullable()->comment('Subjudul dokumentasi');
            $table->longText('text_docs')->comment('Isi dokumentasi dalam teks panjang');
            $table->boolean('status')->default(true)->comment('Status aktif (true) atau nonaktif (false)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentations');
    }
};
