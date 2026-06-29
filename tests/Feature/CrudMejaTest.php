<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pengguna;
use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class CrudMejaTest extends TestCase
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
    public function it_crudmeja_01_list_meja()
    {
        Meja::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('meja.index'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Meja Billiard'); // tombol tambah
        $response->assertSee('Detail');       // Detail
        $mejas = Meja::all();
        foreach ($mejas as $meja) {
            $response->assertSee($meja->id);
            $response->assertSee($meja->tipe_meja);
        }
    }

    #[Test]
    public function it_crudmeja_02_tambah_meja()
    {
        Storage::fake('public');

        $data = [
            'id' => 101,
            'tipe_meja' => 'VIP',
            'harga_sewa' => 50000,
            'deskripsi' => 'Meja VIP nyaman',
            'foto' => UploadedFile::fake()->image('meja.jpg')
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('meja.store'), $data);

        $response->assertRedirect(route('meja.index'));
        $this->assertDatabaseHas('meja', [
            'id' => 101,
            'tipe_meja' => 'VIP',
            'harga_sewa' => 50000
        ]);
    }

    #[Test]
    public function it_crudmeja_03_edit_meja()
    {
        $meja = Meja::factory()->create([
            'tipe_meja' => 'Reguler',
            'harga_sewa' => 30000,
            'status' => 'aktif'
        ]);

        $updateData = [
            'tipe_meja' => 'VIP',
            'harga_sewa' => 60000,
            'deskripsi' => 'Meja VIP updated',
            'status' => 'aktif'
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('meja.update', $meja->id), $updateData);

        $response->assertRedirect(route('meja.index'));
        $this->assertDatabaseHas('meja', [
            'id' => $meja->id,
            'tipe_meja' => 'VIP',
            'harga_sewa' => 60000
        ]);
    }

    #[Test]
    public function it_crudmeja_04_integrasi_pemesanan()
    {
        $pelanggan = \App\Models\Pelanggan::factory()->create();


        $meja = \App\Models\Meja::factory()->create(['status' => 'aktif']);


        $pemesanan = $meja->pemesanan()->create([
            'id_pelanggan' => $pelanggan->id,
            'order_id' => 'ORDER-' . uniqid(),
            'status_pembayaran' => 'sudah_dibayar', // sesuai enum
            'tanggal' => now(),
            'total_harga' => $meja->harga_sewa ?? 50000,
            'proses_pemesanan' => 'pending',
            'metode_pembayaran' => 'online',
        ]);


        $meja->update(['status' => 'nonaktif']);


        $this->assertEquals('nonaktif', $meja->fresh()->status);

        $this->assertDatabaseHas('pemesanan', [
            'id_meja' => $meja->id,
            'id_pelanggan' => $pelanggan->id,
            'status_pembayaran' => 'sudah_dibayar',
        ]);
    }

    #[Test]
    public function it_crudmeja_05_integrasi_laporan_keuangan()
    {

        $pelanggan = \App\Models\Pelanggan::factory()->create();


        $meja = \App\Models\Meja::factory()->create(['harga_sewa' => 50000]);

        $pemesanan = $meja->pemesanan()->create([
            'id_pelanggan' => $pelanggan->id,
            'order_id' => 'ORDER-' . uniqid(),
            'status_pembayaran' => 'sudah_dibayar',
            'tanggal' => now(),
            'total_harga' => 50000,
            'proses_pemesanan' => 'selesai',
            'metode_pembayaran' => 'online',
        ]);


        $this->assertDatabaseHas('pemesanan', [
            'id_meja' => $meja->id,
            'id_pelanggan' => $pelanggan->id,
            'total_harga' => 50000,
            'status_pembayaran' => 'sudah_dibayar',
        ]);
    }
}