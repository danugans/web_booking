<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Meja;
use App\Models\Pengguna;
use PHPUnit\Framework\Attributes\Test;

class MejaTest extends TestCase
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

    /** @test TC-MEJA-01 */

    #[Test]
    public function it_can_add_meja_with_valid_input()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('meja.jpg');

        $payload = [
            'id' => 1,
            'tipe_meja' => 'VIP',
            'harga_sewa' => 10000,
            'deskripsi' => 'Meja VIP nyaman',
            'foto' => $file,
        ];

        $response = $this->post(route('meja.store'), $payload);

        $response->assertRedirect(route('meja.index'));
        $response->assertSessionHas('success', 'Meja berhasil ditambahkan!');
        $this->assertDatabaseHas('meja', ['id' => 1, 'tipe_meja' => 'VIP']);
    }

    #[Test]
    /** @test TC-MEJA-02 */
    public function it_should_fail_when_nomor_meja_is_empty()
    {
        $payload = [
            'id' => '',
            'tipe_meja' => 'Reguler',
            'harga_sewa' => 10000,
            'deskripsi' => 'Meja Reguler',
        ];

        $response = $this->post(route('meja.store'), $payload);
        $response->assertSessionHasErrors('id');
    }

    #[Test]
    /** @test TC-MEJA-03 */
    public function it_should_fail_when_nomor_meja_not_numeric()
    {
        $payload = [
            'id' => 'e',
            'tipe_meja' => 'Reguler',
            'harga_sewa' => 10000,
            'deskripsi' => 'Meja Reguler',
        ];

        $response = $this->post(route('meja.store'), $payload);
        $response->assertSessionHasErrors('id');
    }

    #[Test]
    /** @test TC-MEJA-04 */
    public function it_should_fail_when_nomor_meja_already_exists()
    {
        Meja::factory()->create(['id' => 1]);

        $payload = [
            'id' => 1,
            'tipe_meja' => 'Reguler',
            'harga_sewa' => 5000,
            'deskripsi' => 'Duplicate meja',
        ];

        $response = $this->post(route('meja.store'), $payload);
        $response->assertSessionHasErrors('id');
    }

    #[Test]
    /** @test TC-MEJA-05 */
    public function it_should_fail_when_harga_sewa_is_negative()
    {
        $payload = [
            'id' => 2,
            'tipe_meja' => 'VIP',
            'harga_sewa' => -500,
            'deskripsi' => 'Meja salah harga',
        ];

        $response = $this->post(route('meja.store'), $payload);
        $response->assertSessionHasErrors('harga_sewa');
    }

    #[Test]
    /** @test TC-MEJA-06 */
    public function it_should_fail_when_harga_sewa_is_empty()
    {
        $payload = [
            'id' => 3,
            'tipe_meja' => 'Reguler',
            'harga_sewa' => '',
            'deskripsi' => 'Tanpa harga',
        ];

        $response = $this->post(route('meja.store'), $payload);
        $response->assertSessionHasErrors('harga_sewa');
    }

    #[Test]
    /** @test TC-MEJA-07 */
    public function it_can_add_meja_without_deskripsi()
    {
        $payload = [
            'id' => 4,
            'tipe_meja' => 'Reguler',
            'harga_sewa' => 10000,
            'deskripsi' => null,
        ];

        $response = $this->post(route('meja.store'), $payload);

        $response->assertRedirect(route('meja.index'));
        $response->assertSessionHas('success', 'Meja berhasil ditambahkan!');
        $this->assertDatabaseHas('meja', ['id' => 4]);
    }

    #[Test]
    /** @test TC-MEJA-09 */
    public function it_should_fail_to_update_when_harga_sewa_negative()
    {
        $meja = Meja::factory()->create([
            'id' => 6,
            'tipe_meja' => 'Reguler',
            'harga_sewa' => 5000,
            'deskripsi' => 'Normal',
        ]);

        $payload = [
            'tipe_meja' => 'Reguler',
            'harga_sewa' => -1,
            'deskripsi' => 'Negatif test',
        ];

        $response = $this->put(route('meja.update', $meja->id), $payload);
        $response->assertSessionHasErrors('harga_sewa');
    }
}
