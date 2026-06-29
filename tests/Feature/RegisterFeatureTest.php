<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RegisterFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Pelanggan::factory()->create([
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh01',
            'email' => 'niluh123@gmail.com',
            'nomor_telepon' => '081234567890',
        ]);
    }

    #[Test]
    public function tc_reg_01_melakukan_pendaftaran_akun_dengan_semua_input_valid()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh77',
            'email' => 'niluh17@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000001',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('pelanggan', ['email' => 'niluh17@gmail.com']);
    }

    #[Test]
    public function tc_reg_02_tidak_mengisi_nama()
    {
        $response = $this->post('/register/submit', [
            'nama' => '',
            'username' => 'niluh02',
            'email' => 'niluh124@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000002',
        ]);

        $response->assertSessionHasErrors(['nama' => 'Nama lengkap wajib diisi.']);
    }

    #[Test]
    public function tc_reg_03_nama_satu_karakter()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'A',
            'username' => 'niluh03',
            'email' => 'niluh125@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000003',
        ]);

        $response->assertSessionHasErrors(['nama' => 'Nama tidak boleh 1 karakter']);
    }

    #[Test]
    public function tc_reg_04_nama_batas_maksimal_100_karakter_valid()
    {
        $nama = str_repeat('A', 100);
        $response = $this->post('/register/submit', [
            'nama' => $nama,
            'username' => 'niluh04',
            'email' => 'niluh126@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000004',
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function tc_reg_05_nama_melebihi_101_karakter()
    {
        $nama = str_repeat('A', 101);
        $response = $this->post('/register/submit', [
            'nama' => $nama,
            'username' => 'niluh05',
            'email' => 'niluh127@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000005',
        ]);

        $response->assertSessionHasErrors(['nama' => 'Nama tidak boleh lebih dari 100 karakter.']);
    }

    #[Test]
    public function tc_reg_06_tidak_mengisi_username()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => '',
            'email' => 'niluh128@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000006',
        ]);

        $response->assertSessionHasErrors(['username' => 'Username wajib diisi.']);
    }

    #[Test]
    public function tc_reg_07_username_tidak_boleh_lebih_100_karakter()
    {
        $username = str_repeat('A', 101);
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => $username,
            'email' => 'niluh128@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000006',
        ]);

        $response->assertSessionHasErrors(['username' => 'Username tidak boleh lebih dari 100 karakter.']);
    }

    #[Test]
    public function tc_reg_08_username_sudah_digunakan()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh01',
            'email' => 'niluh129@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000007',
        ]);

        $response->assertSessionHasErrors(['username' => 'Username sudah digunakan.']);
    }

    #[Test]
    public function tc_reg_09_tidak_mengisi_email()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh10',
            'email' => '',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000008',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email wajib diisi.']);
    }

    #[Test]
    public function tc_reg_10_format_email_tidak_valid()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh12',
            'email' => 'niluh-at-gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000009',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function tc_reg_11_email_sudah_digunakan()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh15',
            'email' => 'niluh123@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081200000010',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
    }

    #[Test]
    public function tc_reg_12_password_kurang_dari_6_karakter()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh17',
            'email' => 'niluh130@gmail.com',
            'password' => 'Ab1!',
            'confirm_password' => 'Ab1!',
            'nomor_telepon' => '081200000011',
        ]);

        $response->assertSessionHasErrors(['password' => 'Password minimal 6 karakter.']);
    }

    #[Test]
    public function tc_reg_13_password_tepat_6_karakter()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh18',
            'email' => 'niluh131@gmail.com',
            'password' => 'Abc1!A',
            'confirm_password' => 'Abc1!A',
            'nomor_telepon' => '081200000012',
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function tc_reg_14_konfirmasi_password_tidak_diisi()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh19',
            'email' => 'niluh132@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => '',
            'nomor_telepon' => '081200000013',
        ]);

        $response->assertSessionHasErrors(['confirm_password' => 'Konfirmasi password wajib diisi.']);
    }

    #[Test]
    public function tc_reg_15_konfirmasi_password_tidak_sama()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh19',
            'email' => 'niluh132@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Xyz789!',
            'nomor_telepon' => '081200000013',
        ]);

        $response->assertSessionHasErrors(['confirm_password' => 'Konfirmasi password tidak sama.']);
    }

    #[Test]
    public function tc_reg_16_tidak_mengisi_nomor_hp()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh20',
            'email' => 'danu133@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '',
        ]);

        $response->assertSessionHasErrors(['nomor_telepon' => 'Nomor HP wajib diisi.']);
    }

    #[Test]
    public function tc_reg_17_nomor_hp_bukan_angka()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh21',
            'email' => 'niluh134@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '08xxabcd90',
        ]);

        $response->assertSessionHasErrors(['nomor_telepon' => 'Nomor HP harus berupa angka.']);
    }

    #[Test]
    public function tc_reg_18_nomor_hp_sudah_digunakan()
    {
        $response = $this->post('/register/submit', [
            'nama' => 'Niluh Septiawan',
            'username' => 'niluh22',
            'email' => 'niluh135@gmail.com',
            'password' => 'Abc123!',
            'confirm_password' => 'Abc123!',
            'nomor_telepon' => '081234567890',
        ]);

        $response->assertSessionHasErrors(['nomor_telepon' => 'Nomor HP sudah digunakan.']);
    }

    #[Test]
    public function tc_reg_19_semua_field_kosong()
    {
        $response = $this->post('/register/submit', [
            'nama' => '',
            'username' => '',
            'email' => '',
            'password' => '',
            'confirm_password' => '',
            'nomor_telepon' => '',
        ]);

        $response->assertSessionHasErrors([
            'nama',
            'username',
            'email',
            'password',
            'confirm_password',
            'nomor_telepon',
        ]);
    }
}
