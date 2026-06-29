<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoginFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_01_halaman_login_bisa_diakses()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    #[Test]
    public function test_02_user_bisa_login_dengan_data_valid()
    {
        // Buat user di database
        $pelanggan = Pelanggan::factory()->create([
            'username' => 'user123',
            'password' => Hash::make('password123'),
        ]);

        // Kirim form login
        $response = $this->post('/login/submit', [
            'username' => 'user123',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/daftarmeja');
        $this->assertAuthenticatedAs($pelanggan, 'pelanggan');
    }

    #[Test]
    public function test_03_user_tidak_bisa_login_jika_password_salah()
    {
        $pelanggan = Pelanggan::factory()->create([
            'username' => 'user123',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login/submit', [
            'username' => 'user123',
            'password' => 'salahbang',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest('pelanggan');
    }

    #[Test]
    public function test_04_validasi_tidak_boleh_kosong()
    {
        $response = $this->post('/login/submit', []);

        $response->assertSessionHasErrors(['username', 'password']);
        $this->assertGuest('pelanggan');
    }
}
