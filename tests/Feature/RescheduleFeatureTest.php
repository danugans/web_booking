<?php

namespace Tests\Feature;

use App\Models\Pemesanan;
use App\Models\PemesananSlot;
use App\Models\Pelanggan;
use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class RescheduleFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** WAJIB DIDEKLARASI */
    protected $pelanggan;
    protected $pemesanan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemesanan = Pemesanan::factory()->create();

        // BUAT PELANGGAN
        $this->pelanggan = Pelanggan::factory()->create();

        // BUAT MEJA
        $meja = Meja::factory()->create();

        // BUAT PEMESANAN
        $this->pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $this->pelanggan->id,
            'id_meja'      => $meja->id,
            'tanggal'      => Carbon::now()->addDay()->format('Y-m-d'),
            'reschedule_count' => 0,
        ]);

        // SLOT AWAL
        PemesananSlot::factory()->create([
            'pemesanan_id' => $this->pemesanan->id,
            'jam_mulai'    => '14:00',
            'jam_akhir'    => '15:00',
            'harga'        => 20000,
        ]);

        
    }

    #[Test]
    public function test_01_menampilkan_form_reschedule()
    {
        // LOGIN SESUAI GUARD YANG DIGUNAKAN DI CONTROLLER
        $this->actingAs($this->pelanggan, 'pelanggan');

        $response = $this->get(route('pemesanan.reschedule.form', $this->pemesanan->id));

        $response->assertStatus(200);
        $response->assertViewIs('landingpage.reschedule_form');
        $response->assertViewHas('pemesanan');
    }


    #[Test]
    public function test_02_mengecek_slot_ketersediaan()
    {
    $this->actingAs($this->pelanggan, 'pelanggan');

    $response = $this->get(route('reschedule.cek', [
        'tanggal' => $this->pemesanan->tanggal,
        'id_meja' => $this->pemesanan->id_meja,
        'exclude_id' => $this->pemesanan->id,
    ]));

    $response->assertStatus(200);
    $response->assertJsonMissing(['14:00']); // artinya slot '14:00' tidak dianggap bentrok
    }

    #[Test]
    public function test_03_berhasil_reschedule()
    {
        $this->actingAs($this->pelanggan, 'pelanggan');

        $payload = [
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'slots' => [
                [
                    'jam_mulai' => '14:00',
                    'jam_akhir' => '15:00',
                    'harga' => 20000
                ]
            ]
        ];

        $response = $this->post(route('pemesanan.reschedule.submit', $this->pemesanan->id), $payload);

        $response->assertRedirect(route('riwayat.pemesanan')); // sesuai controller
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pemesanan_slots', [
            'pemesanan_id' => $this->pemesanan->id,
            'jam_mulai'    => '14:00',
            'jam_akhir'    => '15:00',
        ]);
    }

    #[Test]
    public function test_04_reschedule_gagal_jika_slot_sudah_digunakan()
    {
        $this->withoutMiddleware();

        //Buat meja terlebih dahulu agar FK tidak error
        $meja = Meja::factory()->create();

        //Buat pelanggan dan login
        $pelanggan = Pelanggan::factory()->create();
        $this->actingAs($pelanggan, 'pelanggan');

        //Buat pemesanan utama yang akan di-reschedule
        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja'      => $meja->id,
            'tanggal'      => now()->format('Y-m-d'),
        ]);

        //Buat slot asli (pretend booking sebelumnya punya slot ini)
        PemesananSlot::factory()->create([
            'pemesanan_id' => $pemesanan->id,
            'jam_mulai'    => '15:00',
            'jam_akhir'    => '16:00',
        ]);

        //Buat pemesanan lain dengan slot yang bentrok
        $pemesananLain = Pemesanan::factory()->create([
            'id_meja'      => $meja->id,
            'tanggal'      => now()->format('Y-m-d'),
        ]);

        PemesananSlot::factory()->create([
            'pemesanan_id' => $pemesananLain->id,
            'jam_mulai'    => '16:00',
            'jam_akhir'    => '17:00',
        ]);

        //Coba reschedule ke slot yang bentrok → HARUS GAGAL
        $response = $this->post(route('pemesanan.reschedule.submit', $pemesanan->id), [
            'tanggal' => now()->format('Y-m-d'),
            'slots'   => [
                ['jam_mulai' => '16:00', 'jam_akhir' => '17:00']
            ]
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Slot 16:00 sudah dipesan orang lain. Silakan pilih jam lain.');
    }

}
