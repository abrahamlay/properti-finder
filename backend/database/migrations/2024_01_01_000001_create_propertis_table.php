<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propertis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('tipe');
            $table->boolean('subsidi')->default(false);
            $table->decimal('harga', 15, 2);
            $table->string('lokasi');
            $table->string('kota');
            $table->string('developer');
            $table->integer('luas_tanah')->default(0);
            $table->integer('luas_bangunan')->default(0);
            $table->integer('kamar_tidur')->default(1);
            $table->integer('kamar_mandi')->default(1);
            $table->text('deskripsi')->nullable();
            $table->string('gambar')->nullable();
            $table->string('status')->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propertis');
    }
};