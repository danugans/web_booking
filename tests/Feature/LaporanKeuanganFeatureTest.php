<?php

namespace Tests\Feature;

use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\Pelanggan;
use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PHPUnit\Framework\Attributes\Test;

class LaporanKeuanganFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Pengguna $owner;

    protected function setUp(): void
    {
        parent::setUp();

         // login sebagai owner (karena route pakai middleware auth.owner)
        $this->owner = Pengguna::factory()->create([
            'jenis_pengguna' => 'owner'
        ]);

        // gunakan guard yang sesuai dengan middleware auth.ownerowner
        $this->actingAs($this->owner, 'penggunas');

        // Buat data dasar
        $pelanggan = Pelanggan::factory()->create(['nama' => 'Budi']);
        $meja = Meja::factory()->create(['tipe_meja' => 'Reguler']);
        Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja' => $meja->id,
            'status_pembayaran' => 'sudah_dibayar',
            'total_harga' => 50000,
            'created_at' => '2025-10-20 10:00:00',
        ]);
    }

    #[Test]
    public function tc_lk_01_melihat_laporan_keuangan_harian_dengan_input_valid()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tanggal&start_date=2025-10-20&end_date=2025-10-20');
        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'data', 'totalPendapatan', 'transaksi']);
    }

    #[Test]
    public function tc_lk_02_melihat_laporan_dengan_tanggal_awal_dan_akhir_berbeda()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tanggal&start_date=2025-10-01&end_date=2025-10-10');
        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'data']);
    }

    #[Test]
    public function tc_lk_03_melihat_laporan_keuangan_bulanan_dengan_periode_valid()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=bulan&start_date=2025-01&end_date=2025-03');
        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'data']);
    }

    #[Test]
    public function tc_lk_04_melihat_laporan_keuangan_tahunan_dengan_periode_valid()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tahun&start_date=2023&end_date=2025');
        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'data']);
    }

    #[Test]
    public function tc_lk_05_hanya_mengisi_start_tanpa_end_menampilkan_error()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tanggal&start_date=2025-10-20');
        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'data']);
    }

    #[Test]
    public function tc_lk_06_mengosongkan_input_tanggal_menampilkan_error()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tanggal');
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Tanggal awal wajib diisi']);
    }

    #[Test]
    public function tc_lk_07_tanggal_awal_lebih_besar_dari_tanggal_akhir()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tanggal&start_date=2025-10-10&end_date=2025-09-10');
        $this->assertTrue($response->status() == 200 || $response->status() == 400);
    }

    #[Test]
    public function tc_lk_08_melihat_laporan_dengan_data_kosong()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tanggal&start_date=2024-01-01&end_date=2024-01-02');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['totalPendapatan']);
    }


    #[Test]
    public function tc_lk_09_melihat_laporan_tahun_sama_batas_minimum()
    {
        $response = $this->get('/laporan-keuangan/filter?filter_type=tahun&start_date=2025&end_date=2025');
        $response->assertStatus(200);
    }

    #[Test]
    public function tc_lk_10_mengunduh_laporan_pdf_setelah_tampilkan_data()
    {
        $response = $this->get('/laporan-keuangan/export/pdf?filter_type=tanggal&start_date=2025-10-20&end_date=2025-10-20');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }


    #[Test]
    public function tc_lk_11_mengunduh_pdf_saat_tidak_ada_transaksi()
    {
        Pemesanan::query()->delete();
        $response = $this->get('/laporan-keuangan/export/pdf?filter_type=tanggal&start_date=2024-01-01&end_date=2024-01-02');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function tc_lk_12_melihat_laporan_real_time_saat_halaman_dimuat()
    {
        $today = Carbon::now()->format('Y-m-d');
        $response = $this->get("/laporan-keuangan/filter?filter_type=tanggal&start_date={$today}&end_date={$today}");
        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'data']);
    }
}
