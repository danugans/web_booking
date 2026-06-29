<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemesanan;
use Midtrans\Snap;
use Midtrans\Config;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PembayaranController extends Controller
{
    public function show($id)
    {
        $pemesanan = Pemesanan::findOrFail($id);

        Config::$serverKey = config('midtrans.serverKey');
        Config::$isProduction = config('midtrans.isProduction');
        Config::$isSanitized = config('midtrans.isSanitized');
        Config::$is3ds = config('midtrans.is3ds');

        if (!$pemesanan->snap_token) {
            $params = [
                'transaction_details' => [
                    'order_id' => $pemesanan->order_id,
                    'gross_amount' => $pemesanan->total_harga,
                ],
                'customer_details' => [
                    'first_name' => $pemesanan->pelanggan->nama,
                    'email' => $pemesanan->pelanggan->email,
                ],
                'callbacks' => [
                    'finish' => route('pembayaran.finish', $pemesanan->id)
                ]
            ];

            $snapToken = Snap::getSnapToken($params);
            $pemesanan->snap_token = $snapToken;
            $pemesanan->save();
        }

        return view('pembayaran.snap', compact('pemesanan'));
    }

    public function finish(Request $request, $id)
    {
        $pemesanan = Pemesanan::findOrFail($id);
        $pemesanan->status_pembayaran = 'sudah_dibayar';
        $pemesanan->save();

        return redirect()->route('pemesanan.succes', ['id' => $id])->with('success', 'Pembayaran berhasil!');
    }

    public function buktiPemesanan($id)
    {
        $pemesanan = Pemesanan::with(['pelanggan', 'meja', 'slots'])
            ->where('id', $id)
            ->firstOrFail();

        return view('landingpage.succes', compact('pemesanan'));
    }

    public function downloadBukti($id)
    {
        $pemesanan = Pemesanan::with(['pelanggan', 'meja', 'slots'])->findOrFail($id);

        $pdf = Pdf::loadView('landingpage.invoice', compact('pemesanan'))->setPaper('a4');

        return $pdf->download('bukti-pemesanan-' . $pemesanan->order_id . '.pdf');
    }

    public function batal($id)
    {
        $pemesanan = Pemesanan::findOrFail($id);
        $idMeja = $pemesanan->id_meja;

        // Hapus pemesanan
        $pemesanan->delete();

        return redirect()->route('detailmeja.show', ['id' => $idMeja])
            ->with('error', 'Pemesanan telah dibatalkan karena pembayaran tidak diselesaikan.');
    }
}
