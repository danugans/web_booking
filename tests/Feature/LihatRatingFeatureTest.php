<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Review;
use App\Models\Pemesanan;
use App\Models\Pelanggan;
use App\Models\Pengguna;  // ADMIN
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class LihatRatingFeatureTest extends TestCase
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
    public function test_01_menampilkan_halaman_review()
    {
        $response = $this->get(route('review.index'));

        $response->assertStatus(200);
        $response->assertViewIs('review.index');
        $response->assertViewHas('reviews');   // dari controller
        $response->assertViewHas('avgRating');
    }

    #[Test]
    public function test_02_review_dan_rating_muncul()
    {
        $pelanggan = Pelanggan::factory()->create();

        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id
        ]);

        Review::factory()->create([
            'pemesanan_id' => $pemesanan->id,
            'nilai_rating' => 5,
            'komentar'     => 'Mantap sekali!',
        ]);

        $response = $this->get(route('review.index'));

        $response->assertStatus(200);
        $response->assertSee('Mantap sekali!');
        $response->assertSee('5');
    }

    #[Test]
    public function test_03_tidak_ada_review()
    {
        $response = $this->get(route('review.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada review.');
    }
}
