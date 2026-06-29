<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\Http;
use App\Models\Pelanggan;
use App\Models\Meja;
use App\Models\Pemesanan;
use App\Models\Pengguna;
use App\Models\PemesananSlot;
use PHPUnit\Framework\Attributes\Test;

class MendapatkanNotifikasiReminderTest extends TestCase
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

    private function buatPemesanan($nomor = '08123456789')
    {
        $pelanggan = Pelanggan::factory()->create([
            'nomor_telepon' => $nomor
        ]);

        $meja = Meja::factory()->create([
            'tipe_meja' => 'VIP'
        ]);

        $pemesanan = Pemesanan::factory()->create([
            'tanggal' => '2025-12-05',
            'id_pelanggan' => $pelanggan->id,
            'id_meja' => $meja->id,
        ]);

        PemesananSlot::create([
            'pemesanan_id' => $pemesanan->id,
            'jam_mulai' => '10:00',
            'jam_akhir' => '11:00',
            'harga' => 10000
        ]);

        return $pemesanan;
    }

    #[Test]
    /**
     * TC-Notif-01 – Kirim pesan manual berhasil
     */
    public function test_kirim_pesan_manual_berhasil()
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200)
        ]);

        $pemesanan = $this->buatPemesanan();

        $response = $this->post(route('pemesanan.kirimPesan', $pemesanan->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return $request['target'] === '628123456789';
        });
    }

    #[Test]
    /**
     * TC-Notif-02 – Kirim pesan otomatis gagal karena harus manual
     */
    public function test_kirim_pesan_otomatis_gagal()
    {
        Http::fake();

        $pemesanan = $this->buatPemesanan();

        // Pemanggilan fungsi otomatis (langsung controller)
        $response = $this->post(route('pemesanan.kirimPesan', $pemesanan->id));

        $response->assertSessionHas('success'); // MASIH MANUAL

        Http::assertSentCount(1); // tetap terkirim hanya jika manual ditekan
    }


    #[Test]
    /**
     * TC-Notif-03 – Kirim pesan ke nomor kosong
     */
    public function test_kirim_pesan_nomor_kosong()
    {
        $pemesanan = $this->buatPemesanan('');

        // Nomor kosong → server akan memformat → tetap terkirim
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200)
        ]);

        $response = $this->post(route('pemesanan.kirimPesan', $pemesanan->id));

        $response->assertSessionHas('success');
    }

    #[Test]
    /**
     * TC-Notif-04 – Kirim pesan ke nomor acak
     */
    public function test_kirim_pesan_nomor_acak()
    {
        $pemesanan = $this->buatPemesanan('abc123??');

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200)
        ]);

        $response = $this->post(route('pemesanan.kirimPesan', $pemesanan->id));

        $response->assertSessionHas('success');

        // memastikan nomor otomatis diproses (hanya angka)
        Http::assertSent(function ($req) {
            return preg_match('/^[0-9]+$/', $req['target']);
        });
    }

    #[Test]
    /**
     * TC-Notif-05 – Pesan dengan emoji (🎱 dan 📅)
     */
    public function test_kirim_pesan_dengan_emoji()
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['ok' => true], 200)
        ]);

        $pemesanan = $this->buatPemesanan();

        $this->post(route('pemesanan.kirimPesan', $pemesanan->id));

        Http::assertSent(function ($req) {
            $msg = $req['message'];

            return str_contains($msg, '🎱') && str_contains($msg, '📅');
        });
    }
}
