<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Pelanggan, Pemesanan, PemesananSlot, Meja, Pengguna};
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;


class TambahTransaksiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // login sebagai admin (karena route pakai middleware auth.adminowner)
        $this->admin = Pengguna::factory()->create([
            'jenis_pengguna' => 'admin'
        ]);

        // gunakan guard yang sesuai dengan middleware auth.adminowner
        $this->actingAs($this->admin, 'penggunas');
    }


    #[Test]
    public function tc_trx_01_menambahkan_transaksi_dengan_input_valid()
    {
        $meja = Meja::factory()->create();

        $payload = [
            'nama_pelanggan' => 'Budi Santoso',
            'nomor' => '081234567890',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([
                ['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]
            ]),
        ];

        $res = $this->post('/transaksi/store', $payload);
        $res->assertRedirect();
        $this->assertDatabaseHas('pemesanan', ['tanggal' => '2025-10-21']);
    }

    #[Test]
    public function tc_trx_02_tidak_mengisi_nama_pelanggan()
    {
        $meja = Meja::factory()->create();

        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => '',
            'nomor' => '081234567891',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);

        $res->assertSessionHasErrors('nama_pelanggan');
    }

    #[Test]
    public function tc_trx_03_nama_pelanggan_satu_karakter()
    {
        $meja = Meja::factory()->create();
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'A',
            'nomor' => '081234567892',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('nama_pelanggan');
    }

    #[Test]
    public function tc_trx_04_nama_pelanggan_100_karakter_valid()
    {
        $meja = Meja::factory()->create();
        $nama = str_repeat('A', 100);
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => $nama,
            'nomor' => '081234567893',
            'id_meja' => $meja->id,//'
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('pemesanan', ['tanggal' => '2025-10-21']);
    }

    #[Test]
    public function tc_trx_05_nama_pelanggan_lebih_dari_100_karakter()
    {
        $meja = Meja::factory()->create();
        $nama = str_repeat('B', 101);
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => $nama,
            'nomor' => '081234567894',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('nama_pelanggan');
    }

    #[Test]
    public function tc_trx_06_tidak_mengisi_nomor()
    {
        $meja = Meja::factory()->create();
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Budi',
            'nomor' => '',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('nomor');
    }

    #[Test]
    public function tc_trx_07_nomor_harus_angka()
    {
        $meja = Meja::factory()->create();
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Adi',
            'nomor' => '08xxabcd99',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('nomor');
    }

    #[Test]
    public function tc_trx_08_nomor_sudah_terdaftar()
    {
        Pelanggan::factory()->create(['nomor_telepon' => '081234567890']);
        $meja = Meja::factory()->create();

        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Budi',
            'nomor' => '081234567890',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('nomor');
    }

    #[Test]
    public function tc_trx_09_tidak_memilih_meja()
    {
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Budi Santoso',
            'nomor' => '081234567895',
            'id_meja' => '',
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('id_meja');
    }

    #[Test]
    public function tc_trx_10_tidak_mengisi_tanggal()
    {
        $meja = Meja::factory()->create();
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Budi',
            'nomor' => '081234567897',
            'id_meja' => $meja->id,
            'tanggal' => '',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHasErrors('tanggal');
    }


    #[Test]
    public function tc_trx_11_tidak_memilih_slot()
    {
        $meja = Meja::factory()->create();
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Budi',
            'nomor' => '081234567900',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([])
        ]);
        $res->assertSessionHas('error', 'Slot waktu harus dipilih.');
    }

    #[Test]
    public function tc_trx_12_slot_sudah_dibooking()
    {
        $meja = Meja::factory()->create();
        $pelanggan = Pelanggan::factory()->create();
        $pemesanan = Pemesanan::factory()->create([
            'id_meja' => $meja->id,
            'id_pelanggan' => $pelanggan->id,
            'tanggal' => '2025-10-21',
            'status_pembayaran' => 'sudah_dibayar'
        ]);
        PemesananSlot::factory()->create([
            'pemesanan_id' => $pemesanan->id,
            'jam_mulai' => '09:00',
            'jam_akhir' => '10:00',
        ]);

        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Danu',
            'nomor' => '081299999999',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000]])
        ]);
        $res->assertSessionHas('error', 'Slot 09:00 sudah dibooking pengguna lain!');
    }

    #[Test]
    public function tc_trx_13_menambahkan_transaksi_dengan_dua_slot_valid()
    {
        $meja = Meja::factory()->create();

        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => 'Budi',
            'nomor' => '081233344466',
            'id_meja' => $meja->id,
            'tanggal' => '2025-10-21',
            'slots' => json_encode([
                ['jam_mulai' => '09:00', 'jam_akhir' => '10:00', 'harga' => 20000],
                ['jam_mulai' => '10:00', 'jam_akhir' => '11:00', 'harga' => 20000],
            ]),
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('pemesanan', [
            'tanggal' => '2025-10-21',
            'total_harga' => 40000, // total dua slot
        ]);
    }

    #[Test]
    public function tc_trx_14_semua_field_kosong_menampilkan_semua_error()
    {
        $res = $this->post('/transaksi/store', [
            'nama_pelanggan' => '',
            'nomor' => '',
            'id_meja' => '',
            'tanggal' => '',
            'slots' => '',
        ]);

        $res->assertSessionHasErrors([
            'nama_pelanggan',
            'nomor',
            'id_meja',
            'tanggal',
            'slots',
        ]);
    }
}
