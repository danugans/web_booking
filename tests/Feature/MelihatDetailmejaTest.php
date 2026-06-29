<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Pelanggan;
use App\Models\Meja;
use PHPUnit\Framework\Attributes\Test;

class MelihatDetailMejaTest extends TestCase
{
    use RefreshDatabase;

    protected Pelanggan $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();

        // Membuat akun pelanggan
        $this->pelanggan = Pelanggan::factory()->create([
            'nama' => 'Susilo',
            'username' => 'susilo',
            'email' => 'susilo@example.com',
            'password' => bcrypt('$Susilo123'),
            'nomor_telepon' => '08123456789'
        ]);
    }

    #[Test]
    /**
     * TC-Detail-01 – Melihat detail salah satu meja
     */
    public function test_melihat_detail_salah_satu_meja()
    {
        // Login sebagai pelanggan
        $this->actingAs($this->pelanggan, 'pelanggan');

        // Membuat data meja
        $meja = Meja::factory()->create([
            'tipe_meja' => 'VIP',
            'harga_sewa' => 20000,
            'deskripsi' => 'Meja VIP dengan kualitas premium',
            'status' => 'aktif'
        ]);

        // Mengakses halaman detail meja
        $response = $this->get(route('detailmeja.show', $meja->id));

        // ASSERT
        $response->assertStatus(200);

        // Memastikan data tampil di halaman
        $response->assertSee($meja->tipe_meja);
        $response->assertSee($meja->deskripsi);
        $response->assertSee((string) $meja->harga_sewa);
    }
}
