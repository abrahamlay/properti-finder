<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KprController extends Controller
{
    /**
     * POST /api/kpr/simulasi
     * Kalkulasi KPR berdasarkan gaji, harga properti, dan tenor.
     */
    public function simulasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gaji' => 'required|numeric|min:1000000',
            'harga_properti' => 'required|numeric|min:50000000',
            'dp' => 'nullable|numeric|min:0',
            'tenor_tahun' => 'nullable|integer|in:5,10,15,20',
            'bunga_per_tahun' => 'nullable|numeric|min:0|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gaji = $request->gaji;
        $hargaProperti = $request->harga_properti;
        $dpPersen = $request->dp ?? 10; // default DP 10%
        $tenorTahun = $request->tenor_tahun ?? 20;
        $bungaTahunan = ($request->bunga_per_tahun ?? 6) / 100;

        // Hitung cicilan
        $simulasi = $this->hitungAnuitas($hargaProperti, $gaji, $dpPersen, $tenorTahun, $bungaTahunan);

        return response()->json([
            'success' => true,
            'message' => 'Hasil simulasi KPR',
            'data' => $simulasi,
        ]);
    }

    /**
     * Hitung cicilan KPR dengan metode anuitas.
     */
    private function hitungAnuitas(float $harga, float $gaji, float $dpPersen, int $tenorTahun, float $bungaTahunan): array
    {
        $dp = $harga * ($dpPersen / 100);
        $pokokPinjaman = $harga - $dp;
        $tenorBulan = $tenorTahun * 12;
        $bungaBulanan = $bungaTahunan / 12;

        if ($bungaBulanan == 0) {
            $cicilanPerBulan = $pokokPinjaman / $tenorBulan;
        } else {
            $factor = ($bungaBulanan * pow(1 + $bungaBulanan, $tenorBulan)) / (pow(1 + $bungaBulanan, $tenorBulan) - 1);
            $cicilanPerBulan = $pokokPinjaman * $factor;
        }

        $maxCicilan = $gaji * 0.30;
        $layak = $cicilanPerBulan <= $maxCicilan;
        $totalPembayaran = $cicilanPerBulan * $tenorBulan;
        $totalBunga = $totalPembayaran - $pokokPinjaman;

        // Rincian tahunan
        $rincianTahunan = [];
        $sisaPokok = $pokokPinjaman;

        for ($tahun = 1; $tahun <= $tenorTahun; $tahun++) {
            $pokokPerTahun = 0;
            $bungaPerTahun = 0;
            for ($bln = 1; $bln <= 12; $bln++) {
                $bungaBln = $sisaPokok * $bungaBulanan;
                $pokokBln = $cicilanPerBulan - $bungaBln;
                $sisaPokok -= $pokokBln;
                $bungaPerTahun += $bungaBln;
                $pokokPerTahun += $pokokBln;
            }
            $rincianTahunan[] = [
                'tahun' => $tahun,
                'pokok' => round($pokokPerTahun, 2),
                'bunga' => round($bungaPerTahun, 2),
                'sisa_pokok' => round(max($sisaPokok, 0), 2),
            ];
        }

        return [
            'input' => [
                'gaji' => (float) $gaji,
                'harga_properti' => (float) $harga,
                'dp_persen' => (float) $dpPersen,
                'dp_nominal' => round($dp, 2),
                'pokok_pinjaman' => round($pokokPinjaman, 2),
                'tenor_tahun' => $tenorTahun,
                'bunga_per_tahun' => ($bungaTahunan * 100) . '%',
            ],
            'hasil' => [
                'cicilan_per_bulan' => round($cicilanPerBulan, 2),
                'total_pembayaran' => round($totalPembayaran, 2),
                'total_bunga' => round($totalBunga, 2),
                'max_cicilan_30_persen' => round($maxCicilan, 2),
                'sisa_gaji' => round($gaji - $cicilanPerBulan, 2),
            ],
            'kelayakan' => [
                'layak' => $layak,
                'status' => $layak ? '✅ LAYAK' : '❌ TIDAK LAYAK',
                'pesan' => $layak
                    ? 'Selamat! Anda layak mengajukan KPR untuk properti ini.'
                    : 'Cicilan melebihi 30% gaji Anda. Pertimbangkan menambah DP, memperpanjang tenor, atau mencari properti dengan harga lebih rendah.',
            ],
            'rincian_tahunan' => $rincianTahunan,
        ];
    }
}