<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Properti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertiController extends Controller
{
    /**
     * GET /api/properti/subsidi
     * Daftar properti bersubsidi.
     */
    public function subsidi()
    {
        $propertis = Properti::subsidi()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar properti subsidi',
            'data' => $propertis,
        ]);
    }

    /**
     * GET /api/properti/rekomendasi?gaji=X&lokasi=Y
     * Rekomendasi properti berdasarkan gaji dan lokasi.
     */
    public function rekomendasi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gaji' => 'required|numeric|min:1000000',
            'lokasi' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gaji = $request->gaji;
        $lokasi = $request->lokasi;

        // Max cicilan 30% dari gaji
        $maxCicilan = $gaji * 0.30;

        // Tenor: 20 tahun (240 bulan), bunga: 6% efektif per tahun
        $bungaEfektif = 0.06 / 12;
        $tenor = 240;

        // Rumus KPR: M = P * (r * (1+r)^n) / ((1+r)^n - 1)
        // cari max harga (P) dari cicilan max (M)
        $factor = ($bungaEfektif * pow(1 + $bungaEfektif, $tenor)) / (pow(1 + $bungaEfektif, $tenor) - 1);
        $maxHarga = $maxCicilan / $factor;

        // Batas maksimal harga rumah subsidi FLPP: Rp 200jt
        $maxHarga = min($maxHarga, 200000000);

        $query = Properti::where('status', 'tersedia');

        if ($lokasi) {
            $query->where(function ($q) use ($lokasi) {
                $q->where('kota', 'like', "%{$lokasi}%")
                  ->orWhere('lokasi', 'like', "%{$lokasi}%");
            });
        }

        $query->where('harga', '<=', $maxHarga);

        // Prioritaskan yang subsidi
        $propertis = $query->orderBy('subsidi', 'desc')->orderBy('harga', 'asc')->get();

        // Hitung simulasi KPR untuk setiap properti
        $results = $propertis->map(function ($properti) use ($gaji, $maxCicilan) {
            $simulasi = $this->hitungKpr($properti->harga, $gaji);
            return [
                'properti' => $properti,
                'simulasi_kpr' => $simulasi,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi properti berdasarkan gaji dan lokasi',
            'meta' => [
                'gaji' => (float) $gaji,
                'max_cicilan' => round($maxCicilan, 2),
                'max_harga' => round($maxHarga, 2),
                'total' => $results->count(),
            ],
            'data' => $results,
        ]);
    }

    /**
     * Hitung simulasi KPR.
     */
    private function hitungKpr(float $harga, float $gaji): array
    {
        $dp = $harga * 0.10; // DP 10%
        $pokokPinjaman = $harga - $dp;

        // Tenor: 20 tahun (240 bulan)
        $tenorTahun = 20;
        $tenorBulan = $tenorTahun * 12;

        // Bunga: 6% efektif per tahun
        $bungaTahunan = 0.06;
        $bungaBulanan = $bungaTahunan / 12;

        // Cicilan per bulan (rumus anuitas)
        $factor = ($bungaBulanan * pow(1 + $bungaBulanan, $tenorBulan)) / (pow(1 + $bungaBulanan, $tenorBulan) - 1);
        $cicilanPerBulan = $pokokPinjaman * $factor;

        // Cek kelayakan (max 30% dari gaji)
        $maxCicilan = $gaji * 0.30;
        $layak = $cicilanPerBulan <= $maxCicilan;

        $sisaGaji = $gaji - $cicilanPerBulan;

        return [
            'harga_properti' => round($harga, 2),
            'dp_10_persen' => round($dp, 2),
            'pokok_pinjaman' => round($pokokPinjaman, 2),
            'bunga_per_tahun' => '6%',
            'tenor_tahun' => $tenorTahun,
            'cicilan_per_bulan' => round($cicilanPerBulan, 2),
            'max_cicilan_30_persen_gaji' => round($maxCicilan, 2),
            'sisa_gaji_setelah_cicilan' => round($sisaGaji, 2),
            'layak' => $layak,
            'status_kelayakan' => $layak ? 'Layak' : 'Tidak Layak',
            'rekomendasi' => $this->getRekomendasi($layak, $harga),
        ];
    }

    /**
     * Dapatkan rekomendasi KPR berdasarkan kelayakan.
     */
    private function getRekomendasi(bool $layak, float $harga): string
    {
        if (!$layak) {
            return 'Coba cari properti dengan harga lebih rendah atau tambah DP. Cicilan tidak boleh melebihi 30% gaji.';
        }

        if ($harga <= 200000000) {
            return 'Rumah ini memenuhi syarat KPR FLPP Bersubsidi. Segera ajukan!';
        }

        return 'Rumah ini layak KPR. Anda bisa mengajukan KPR konvensional atau syariah.';
    }
}