<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // masuk / keluar
            $table->string('nomor_surat')->nullable();
            $table->string('kode_klasifikasi')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->date('tanggal_terima')->nullable();
            $table->string('asal_surat')->nullable();
            $table->string('tujuan_surat')->nullable();
            $table->string('pengirim')->nullable();
            $table->string('penerima')->nullable();
            $table->string('file')->nullable(); // file path
            $table->unsignedBigInteger('jenis_id'); // FK ke jenis arsip
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
