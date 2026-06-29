<?php

namespace Tests\Feature;

use App\Models\Pemesanan;
use App\Models\Pelanggan;
use App\Models\Meja;
use App\Models\PemesananSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BuktiPemesananTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tc_bukti_01_tampilkan_halaman_bukti_pemesanan_valid()
    {
        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create(['tipe_meja' => 'VIP']);

        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-20',
            'order_id' => 'ORDER123',
            'status_pembayaran' => 'sudah_dibayar',
            'total_harga' => 150000,
        ]);

        PemesananSlot::factory()->create([
            'pemesanan_id' => $pemesanan->id,
            'jam_mulai' => '10:00',
            'jam_akhir' => '11:00',
            'harga' => 50000,
        ]);

        $response = $this->withoutMiddleware()->get("/pemesanan/succes/{$pemesanan->id}");

        $response->assertSee('Bukti Pemesanan');
        $response->assertSee('ORDER123');
        $response->assertSee($pelanggan->nama);
        $response->assertSee('VIP');
    }

    #[Test]
    public function tc_bukti_02_pemesanan_tidak_ditemukan()
    {
        $response = $this->withoutMiddleware()->get('/pemesanan/succes/9999');

        $response->assertStatus(404);
    }

    #[Test]
    public function tc_bukti_03_validasi_relasi_data_lengkap()
    {
        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja' => $meja->id,
        ]);

        PemesananSlot::factory()->create(['pemesanan_id' => $pemesanan->id]);

        $this->withoutMiddleware()
            ->get("/pemesanan/succes/{$pemesanan->id}")
            ->assertSee($pelanggan->nama)
            ->assertSee($meja->tipe_meja);
    }

    #[Test]
    public function tc_bukti_04_format_tanggal_benar()
    {
        $pemesanan = Pemesanan::factory()->create([
            'tanggal' => '2025-10-20',
        ]);

        $this->withoutMiddleware()
            ->get("/pemesanan/succes/{$pemesanan->id}")
            ->assertSee('2025-10-20'); // view menampilkan tanggal mentah
    }

    #[Test]
    public function tc_bukti_05_status_pembayaran_lunas()
    {
        $pemesanan = Pemesanan::factory()->create([
            'status_pembayaran' => 'sudah_dibayar',
        ]);

        $this->withoutMiddleware()
            ->get("/pemesanan/succes/{$pemesanan->id}")
            ->assertSee('Lunas');
    }

    #[Test]
    public function tc_bukti_06_status_pembayaran_menunggu()
    {
        $pemesanan = Pemesanan::factory()->create([
            'status_pembayaran' => 'belum_dibayar',
        ]);

        $this->withoutMiddleware()
            ->get("/pemesanan/succes/{$pemesanan->id}")
            ->assertSee('Menunggu Pembayaran');
    }
}
