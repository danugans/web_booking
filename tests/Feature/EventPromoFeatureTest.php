<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Information;
use App\Models\Pengguna;
use PHPUnit\Framework\Attributes\Test;

class EventPromoFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Pengguna::factory()->create([
            'jenis_pengguna' => 'admin'
        ]);

        $this->actingAs($this->admin, 'penggunas');
    }

    #[Test]
    public function tc_event_01_menambahkan_event_dengan_input_valid()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('promo1.jpg', 600, 600)->size(1024);

        $response = $this->post('/submit-event', [
            'title' => 'Promo Akhir Tahun',
            'content' => 'Diskon 50% untuk semua menu.',
            'image' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('information', ['title' => 'Promo Akhir Tahun']);
        $this->assertTrue(Storage::disk('public')->exists('information/' . $file->hashName()));
    }


    #[Test]
    public function tc_event_02_tanpa_mengisi_judul()
    {

        $response = $this->post('/submit-event', [
            'title' => '',
            'content' => 'Diskon 50% untuk semua menu.',
            'image' => UploadedFile::fake()->image('promo2.jpg'),
        ]);

        $response->assertSessionHasErrors('title');
    }

    #[Test]
    public function tc_event_03_judul_hanya_satu_karakter()
    {
        $response = $this->post('/submit-event', [
            'title' => 'A',
            'content' => 'Promo menarik di akhir tahun.',
            'image' => UploadedFile::fake()->image('promo3.png'),
        ]);

        $response->assertSessionHasErrors('title');
    }

    #[Test]
    public function tc_event_04_judul_255_karakter_valid()
    {
        $title = str_repeat('A', 255);

        $response = $this->post('/submit-event', [
            'title' => $title,
            'content' => 'Diskon 10% untuk pelanggan setia.',
            'image' => UploadedFile::fake()->image('promo4.webp'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('information', ['title' => $title]);
    }

    #[Test]
    public function tc_event_05_judul_melebihi_255_karakter()
    {
        $title = str_repeat('A', 256);

        $response = $this->post('/submit-event', [
            'title' => $title,
            'content' => 'Promo akhir bulan.',
            'image' => UploadedFile::fake()->image('promo5.png'),
        ]);

        $response->assertSessionHasErrors('title');
    }

    #[Test]
    public function tc_event_06_tanpa_mengisi_konten()
    {
        $response = $this->post('/submit-event', [
            'title' => 'Promo Akhir Tahun',
            'content' => '',
            'image' => UploadedFile::fake()->image('promo6.jpg'),
        ]);

        $response->assertSessionHasErrors('content');
    }

    #[Test]
    public function tc_event_08_tanpa_gambar_opsional_valid()
    {
        $response = $this->post('/submit-event', [
            'title' => 'Promo Tanpa Gambar',
            'content' => 'Promo diskon 25% untuk menu tertentu.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('information', ['title' => 'Promo Tanpa Gambar']);
    }

    #[Test]
    public function tc_event_09_file_non_gambar()
    {
        $file = UploadedFile::fake()->create('file.pdf', 100);

        $response = $this->post('/submit-event', [
            'title' => 'Promo Salah Format',
            'content' => 'Coba upload file salah.',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function tc_event_10_gambar_lebih_dari_3mb()
    {
        $file = UploadedFile::fake()->image('promo8.jpg')->size(4000);

        $response = $this->post('/submit-event', [
            'title' => 'Promo Gagal Upload',
            'content' => 'File melebihi batas.',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function tc_event_11_menampilkan_daftar_event()
    {
        Information::factory()->count(3)->create();

        $response = $this->get('/submit-event');
        $response->assertStatus(200);
        $response->assertSee('Event');
    }

    #[Test]
    public function tc_event_12_menampilkan_detail_event()
    {
        $info = Information::factory()->create([
            'title' => 'Event Detail',
            'content' => 'Detail event ini.',
        ]);

        $response = $this->get('/submit-event/' . $info->id);
        $response->assertStatus(200);
        $response->assertSee($info->title);
    }   

    #[Test]
    public function tc_event_13_mengedit_event_dengan_input_valid()
    {
        Storage::fake('public');
        $info = Information::factory()->create();

        $newFile = UploadedFile::fake()->image('promo-update.jpg');

        $response = $this->put("/submit-event/{$info->id}", [
            'title' => 'Promo Akhir Tahun Update',
            'content' => 'Diskon hingga 70%.',
            'image' => $newFile,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('information', ['title' => 'Promo Akhir Tahun Update']);
    }

    #[Test]
    public function tc_event_14_edit_tanpa_mengubah_gambar()
    {
        $info = Information::factory()->create([
            'title' => 'Promo Lama',
            'content' => 'Konten lama',
            'image' => 'information/promo-lama.jpg',
        ]);

        $response = $this->put("/submit-event/{$info->id}", [
            'title' => 'Promo Baru',
            'content' => 'Tanpa update gambar.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('information', ['title' => 'Promo Baru']);
    }

    #[Test]
    public function tc_event_15_edit_dengan_file_non_gambar()
    {
        $info = Information::factory()->create();
        $file = UploadedFile::fake()->create('dokumen.pdf', 50);

        $response = $this->put("/submit-event/{$info->id}", [
            'title' => 'Promo Baru',
            'content' => 'Update dengan salah file.',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function tc_event_16_menghapus_event_yang_valid()
    {
        Storage::fake('public');
        $info = Information::factory()->create(['image' => 'information/test.jpg']);

        $response = $this->delete("/submit-event/{$info->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('information', ['id' => $info->id]);
    }

    #[Test]
    public function tc_event_17_menghapus_event_dengan_id_tidak_ditemukan()
    {
        $response = $this->delete('/submit-event/9999');
        $response->assertNotFound();
    }
}
