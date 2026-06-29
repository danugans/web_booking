<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Pemesanan;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use Carbon\Carbon;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    protected Pengguna $admin;

    private function loginAdmin()
    {
        // Membuat admin/owner
         $this->admin = Pengguna::factory()->create([
            'jenis_pengguna' => 'admin'
        ]);

        // Login menggunakan guard adminowner
         $this->actingAs($this->admin, 'penggunas');
    }

    public function test_dashboard_menampilkan_data_dengan_benar()
    {
        $this->loginAdmin(); // HARUS karena rute memakai middleware auth.adminowner

        // ==== BUAT DATA PELANGGAN ====
        $pelanggan1 = Pelanggan::factory()->create();
        $pelanggan2 = Pelanggan::factory()->create();

        // ==== PEMESANAN ====
        Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan1->id,
            'total_harga' => 50000,
            'status_pembayaran' => 'sudah_dibayar',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        Pemesanan::factory()->create([
            'id_pelanggan' => $pelanggan2->id,
            'total_harga' => 30000,
            'status_pembayaran' => 'belum_dibayar',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        // ==== HIT ROUTE ====
        $response = $this->get(route('beranda'));

        // ==== CEK STATUS & VIEW ====
        $response->assertStatus(200);
        $response->assertViewIs('dashboard.beranda');

        // ==== ASSERT VALUE ====
        $response->assertViewHas('totalPelanggan', 2);
        $response->assertViewHas('totalBooking', 2);
        $response->assertViewHas('totalPendapatan', 50000);

        // Cek grafik booking dan grafik pendapatan dikirim
        $response->assertViewHas('laporanBooking');
        $response->assertViewHas('laporanPendapatan');
    }
}
