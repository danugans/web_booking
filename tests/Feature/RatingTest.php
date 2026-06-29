<?php

namespace Tests\Feature;

use App\Models\Pemesanan;
use App\Models\Pelanggan;
use App\Models\Review;
use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RatingTest extends TestCase
{
    use RefreshDatabase;


    
    private function login()
    {
        $user = Pelanggan::factory()->create();
        $this->actingAs($user);
    }

    #[Test]
    public function form_rating_bisa_diakses_jika_pemesanan_selesai_dan_belum_ada_review()
    {
        $this->login();

        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja'      => $meja->id,
            'proses_pemesanan' => 'selesai'
        ]);

        $response = $this->get(route('rating.form', $pemesanan->id));

        $response->assertStatus(200)
                 ->assertSee("Beri Rating");
    }


    #[Test]
    public function tidak_bisa_akses_form_jika_rating_sudah_ada()
    {
        $this->login();

        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja'      => $meja->id,
            'proses_pemesanan' => 'selesai'
        ]);

        Review::factory()->create([
            'pemesanan_id' => $pemesanan->id,
        ]);

        $response = $this->get(route('rating.form', $pemesanan->id));

        $response->assertRedirect(route('riwayat.pemesanan'))
                 ->assertSessionHas('error');
    }

    #[Test]
    public function berhasil_menyimpan_rating()
    {
        $this->login();

        $pelanggan = Pelanggan::factory()->create();
        $meja = Meja::factory()->create();

        $pemesanan = Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan->id,
            'id_meja'      => $meja->id,
            'proses_pemesanan' => 'selesai'
        ]);

        $response = $this->post(route('rating.store'), [
            'pemesanan_id' => $pemesanan->id,
            'nilai_rating' => 5,
            'komentar'     => 'Mantap!'
        ]);

        $response->assertRedirect(route('riwayat.pemesanan'))
                 ->assertSessionHas('success');

        $this->assertDatabaseHas('review', [
            'pemesanan_id' => $pemesanan->id,
            'nilai_rating' => 5,
            'komentar'     => 'Mantap!'
        ]);
    }

    #[Test]
    public function gagal_menyimpan_rating_karena_validasi()
    {
        $this->login();

        $response = $this->post(route('rating.store'), [
            'pemesanan_id' => null,
            'nilai_rating' => 10, // invalid
            'komentar'     => ''
        ]);

        $response->assertSessionHasErrors(['pemesanan_id', 'nilai_rating']);
    }
}
