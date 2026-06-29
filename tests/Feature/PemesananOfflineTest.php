<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pelanggan;
use App\Models\Meja;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PemesananOfflineTest extends TestCase
{
    use RefreshDatabase;


    #[Test]
    public function pelanggan_dapat_melihat_daftar_meja()
    {
        $meja = Meja::factory()->count(3)->create();

        $response = $this->get('/daftarmeja');
        $response->assertStatus(200);
        $response->assertSee($meja[0]->tipe_meja);
    }

    #[Test]
    public function pelanggan_dapat_melihat_detail_meja()
    {
        $meja = Meja::factory()->create();

        $response = $this->get('/daftarmeja/' . $meja->id);
        $response->assertStatus(200);
        $response->assertSee($meja->deskripsi);
    }

    #[Test]
    public function pelanggan_dapat_melakukan_pemesanan_offline_satu_slot()
    {
        /** @var \App\Models\Pelanggan $pelanggan */
        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $this->actingAs($pelanggan, 'pelanggan');

        $response = $this->post('/pemesanan/konfirmasi', [
            'id_meja' => $meja->id,
            'tanggal' => now()->addDay()->toDateString(),
            'slots' => [
                json_encode(['jam_mulai' => '10:00', 'jam_akhir' => '11:00', 'harga' => 30000]),
            ],
            'metode_pembayaran' => 'offline',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pemesanan', [
            'id_meja' => $meja->id,
            'metode_pembayaran' => 'offline',
            'status_pembayaran' => 'belum_dibayar',
        ]);
    }

    #[Test]
    public function pemesanan_offline_dengan_lebih_dari_satu_slot_ditolak()
    {
        /** @var \App\Models\Pelanggan $pelanggan */
        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $this->actingAs($pelanggan, 'pelanggan');

        $response = $this->post('/pemesanan/konfirmasi', [
            'id_meja' => $meja->id,
            'tanggal' => now()->addDay()->toDateString(),
            'slots' => [
                json_encode(['jam_mulai' => '10:00', 'jam_akhir' => '11:00', 'harga' => 30000]),
                json_encode(['jam_mulai' => '11:00', 'jam_akhir' => '12:00', 'harga' => 30000]),
            ],
            'metode_pembayaran' => 'offline',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('pemesanan', [
            'id_meja' => $meja->id,
        ]);
    }

    #[Test]
    public function admin_dapat_melihat_dan_mengonfirmasi_pemesanan()
    {
        /** @var \App\Models\Pengguna $admin */
        $admin = \App\Models\Pengguna::factory()->create(['jenis_pengguna' => 'admin']);
        $this->actingAs($admin, 'penggunas');

        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $pemesanan = Pemesanan::create([
            'tanggal' => now()->addDay()->toDateString(),
            'total_harga' => 30000,
            'status_pembayaran' => 'belum_dibayar',
            'id_pelanggan' => $pelanggan->id,
            'id_meja' => $meja->id,
            'metode_pembayaran' => 'offline',
            'order_id' => 'abc123',
            'proses_pemesanan' => 'pending',
        ]);

        $responseIndex = $this->get('/pemesanan');
        $responseIndex->assertStatus(200);

        $responseProses = $this->post("/pemesanan/{$pemesanan->id}/proses");
        $responseProses->assertRedirect();

        $this->assertDatabaseHas('pemesanan', [
            'id' => $pemesanan->id,
            'status_pembayaran' => 'sudah_dibayar',
            'proses_pemesanan' => 'selesai',
        ]);
    }
}
