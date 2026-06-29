<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->date('tanggal');
            $table->integer('total_harga')->default(0);
            $table->enum('status_pembayaran', ['belum_dibayar', 'sudah_dibayar'])->default('belum_dibayar');
            $table->enum('proses_pemesanan', ['pending', 'selesai'])->default('pending');
            $table->enum('metode_pembayaran', ['offline', 'online'])->default('offline');
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->string('snap_token')->nullable();
            $table->unsignedBigInteger('id_pelanggan');
            $table->unsignedBigInteger('id_meja');
            $table->timestamps();

            $table->foreign('id_pelanggan')->references('id')->on('pelanggan')->onDelete('cascade');
            $table->foreign('id_meja')->references('id')->on('meja')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemesanan');
    }
};
