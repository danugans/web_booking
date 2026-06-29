<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Pemesanan;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Meja;
use App\Models\PemesananSlot;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class KelolaPesananFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $pelanggan;
    protected $meja;
    protected $pemesanan;
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

        // Dummy pelanggan
        $this->pelanggan = Pelanggan::factory()->create([
            'nama' => 'Niluh',
            'nomor_telepon' => '081234567890',
        ]);

        // Dummy meja
        $this->meja = Meja::factory()->create([
            'tipe_meja' => 'Reguler',
            'status' => 'aktif',
        ]);

        // Dummy pemesanan
        $this->pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $this->pelanggan->id,
            'id_meja' => $this->meja->id,
            'tanggal' => Carbon::today(),
            'order_id' => 'ORD12345',
            'proses_pemesanan' => 'pending',
            'status_pembayaran' => 'belum_dibayar',
        ]);

        // Dummy slot
        PemesananSlot::factory()->create([
            'pemesanan_id' => $this->pemesanan->id,
            'jam_mulai' => '10:00',
            'jam_akhir' => '11:00',
        ]);
    }

    #[Test]
    public function test_01_menampilkan_daftar_pemesanan()
    {
        $response = $this->get('/pemesanan');

        $response->assertStatus(200);
        $response->assertViewIs('admin.pemesanan.index');
        $response->assertViewHas('pemesanan');
    }

    #[Test]
    public function test_02_mengonfirmasi_pemesanan()
    {
        // Sesuai route: POST /pemesanan/{id}/proses
        $response = $this->post("/pemesanan/{$this->pemesanan->id}/proses");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pemesanan berhasil dikonfirmasi.');

        $this->assertDatabaseHas('pemesanan', [
            'id' => $this->pemesanan->id,
            'proses_pemesanan' => 'selesai',
            'status_pembayaran' => 'sudah_dibayar',
        ]);
    }

    #[Test]
    public function test_03_mengirim_pesan_whatsapp_ke_pelanggan()
    {
        // Mock HTTP agar tidak memanggil API asli
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        // Route: POST /pemesanan/{id}/kirim-pesan
        $response = $this->post("/pemesanan/{$this->pemesanan->id}/kirim-pesan");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan request terkirim ke Fonnte
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && str_contains($request['message'], 'Halo Niluh');
        });
    }

    #[Test]
    public function test_04_mengirim_pengingat_otomatis_ke_pelanggan_pending()
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        Log::spy();

        $this->pemesanan->update(['proses_pemesanan' => 'pending']);

        // Karena belum ada route, kita panggil langsung controllernya
        $controller = new \App\Http\Controllers\PemesananController();
        $controller->kirimPengingatOtomatis();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && str_contains($request['message'], 'masih berstatus *Pending*');
        });

        Log::shouldHaveReceived('info')->once();
    }

    #[Test]
    public function test_05_mencari_pemesanan_berdasarkan_order_id()
    {
        $response = $this->get('/pemesanan?search=ORD12345');

        $response->assertStatus(200);
        $response->assertViewIs('admin.pemesanan.index');
        $response->assertViewHas('pemesanan', function ($pemesanan) {
            return $pemesanan->first()->order_id === 'ORD12345';
        });
    }
}
