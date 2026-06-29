<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Information;
use PHPUnit\Framework\Attributes\Test;

class MelihatEventTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function halaman_event_menampilkan_daftar_informasi()
    {
        // Arrange: buat data
        $info = Information::factory()->create([
            'title'        => 'Turnamen Besar',
            'content'      => 'Kompetisi seru untuk semua pemain',
            'published_at' => now(),
            'image'        => 'dummy.jpg'
        ]);

        // Act
        $response = $this->get(route('event'));

        // Assert
        $response->assertStatus(200)
                 ->assertSee('Turnamen Besar')
                 ->assertSee('Kompetisi seru');
    }

    #[Test]
    public function halaman_detail_event_bisa_diakses()
    {
        $info = Information::factory()->create([
            'title'        => 'Promo Akhir Tahun',
            'content'      => 'Diskon besar-besaran!',
            'published_at' => now(),
            'image'        => 'promo.jpg'
        ]);

        $response = $this->get(route('event.show', $info->id));

        $response->assertStatus(200)
                 ->assertSee('Promo Akhir Tahun')
                 ->assertSee('Diskon besar-besaran!');
    }

    #[Test]
    public function halaman_detail_event_akan_404_jika_id_tidak_ada()
    {
        $response = $this->get(route('event.show', 9999));

        $response->assertStatus(404);
    }
}
