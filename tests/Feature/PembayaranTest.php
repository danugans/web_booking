<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Pemesanan;
use App\Models\Pelanggan;
use App\Models\Meja;
use App\Models\PemesananSlot;
use Mockery;
use Midtrans\Snap;
use Illuminate\Support\Facades\Route;

class PembayaranTest extends TestCase
{
    use RefreshDatabase;

    private function loginPelanggan()
{
    $pelanggan = \App\Models\Pelanggan::factory()->create();
    $this->actingAs($pelanggan, 'pelanggan'); // SESUAIKAN guard!!!
    return $pelanggan;
}

public function test_show_menghasilkan_snap_token_dan_menampilkan_view()
{
    // LOGIN DULU
    $pelanggan = $this->loginPelanggan();

    // Mock Snap
    $mockSnap = \Mockery::mock('alias:' . \Midtrans\Snap::class);
    $mockSnap->shouldReceive('getSnapToken')->andReturn('dummy_snap_token');

    $meja = \App\Models\Meja::factory()->create();

    $pemesanan = \App\Models\Pemesanan::factory()->create([
        'id_pelanggan' => $pelanggan->id,
        'id_meja' => $meja->id,
        'order_id' => 'ORDER123',
    ]);

    $response = $this->get(route('pembayaran.show', $pemesanan->id));

    $response->assertStatus(200);
    $response->assertViewIs('pembayaran.snap');

    $this->assertDatabaseHas('pemesanan', [
        'id' => $pemesanan->id,
        'snap_token' => 'dummy_snap_token'
    ]);
}

public function test_finish_mengubah_status_pembayaran_dan_redirect()
{
    $pelanggan = $this->loginPelanggan();

    $meja = \App\Models\Meja::factory()->create();

    $pemesanan = \App\Models\Pemesanan::factory()->create([
        'id_pelanggan' => $pelanggan->id,
        'id_meja' => $meja->id,
        'status_pembayaran' => 'belum_dibayar'
    ]);

    $response = $this->get(route('pembayaran.finish', $pemesanan->id));

    $response->assertRedirect(route('pemesanan.succes', $pemesanan->id));

    $this->assertDatabaseHas('pemesanan', [
        'id' => $pemesanan->id,
        'status_pembayaran' => 'sudah_dibayar'
    ]);
}

public function test_batal_menghapus_pemesanan_dan_redirect()
{
    $pelanggan = $this->loginPelanggan();

    $meja = \App\Models\Meja::factory()->create();

    $pemesanan = \App\Models\Pemesanan::factory()->create([
        'id_pelanggan' => $pelanggan->id,
        'id_meja' => $meja->id
    ]);

    $response = $this->get(route('pembayaran.batal', $pemesanan->id));

    $response->assertRedirect(route('detailmeja.show', $meja->id));

    $this->assertDatabaseMissing('pemesanan', [
        'id' => $pemesanan->id
    ]);
}
    
}
