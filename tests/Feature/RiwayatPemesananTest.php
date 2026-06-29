<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pemesanan;
use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class RiwayatPemesananTest extends TestCase
{
    use RefreshDatabase;

    protected Pelanggan $pelanggan;
    protected Meja $meja;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat pelanggan dan meja
        $this->pelanggan = Pelanggan::factory()->create();
        $this->meja = Meja::factory()->create();
    }

    #[Test]
    public function halaman_riwayat_menampilkan_daftar_pemesanan()
    {
        Pemesanan::factory()->count(3)->create([
            'id_pelanggan' => $this->pelanggan->id,
            'id_meja' => $this->meja->id,
        ]);

        $response = $this->actingAs($this->pelanggan, 'pelanggan')
            ->get('/riwayat-pemesanan');

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pemesanan Anda');
        $this->assertCount(3, Pemesanan::where('id_pelanggan', $this->pelanggan->id)->get());
    }

    #[Test]
    public function menampilkan_pesan_jika_tidak_ada_pemesanan()
    {
        $response = $this->actingAs($this->pelanggan, 'pelanggan')
            ->get('/riwayat-pemesanan');

        $response->assertStatus(200);
        $response->assertSee('Anda belum memiliki pemesanan.');
    }

    #[Test]
    public function urutan_pemesanan_terurut_dari_terbaru()
    {
        $old = Pemesanan::factory()->create([
            'id_pelanggan' => $this->pelanggan->id,
            'id_meja' => $this->meja->id,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $new = Pemesanan::factory()->create([
            'id_pelanggan' => $this->pelanggan->id,
            'id_meja' => $this->meja->id,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->pelanggan, 'pelanggan')
            ->get('/riwayat-pemesanan');

        $response->assertSeeInOrder([$new->order_id, $old->order_id]);
    }
}
