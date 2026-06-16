<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Properti extends Model
{
    protected $table = 'propertis';

    protected $fillable = [
        'nama',
        'tipe',
        'subsidi',
        'harga',
        'lokasi',
        'kota',
        'developer',
        'luas_tanah',
        'luas_bangunan',
        'kamar_tidur',
        'kamar_mandi',
        'deskripsi',
        'gambar',
        'status',
    ];

    protected $casts = [
        'subsidi' => 'boolean',
        'harga' => 'decimal:2',
    ];

    /**
     * Scope untuk properti subsidi.
     */
    public function scopeSubsidi($query)
    {
        return $query->where('subsidi', true)->where('status', 'tersedia');
    }

    /**
     * Scope untuk filter berdasarkan gaji (maks 30% dari gaji untuk cicilan).
     */
    public function scopeByGaji($query, $gaji)
    {
        $maxCicilan = $gaji * 0.30;
        // Asumsi tenor 20 tahun, bunga 6% efektif
        $maxHarga = $maxCicilan * 12 * 20;
        return $query->where('harga', '<=', $maxHarga);
    }

    /**
     * Scope filter berdasarkan lokasi.
     */
    public function scopeByLokasi($query, $lokasi)
    {
        return $query->where(function ($q) use ($lokasi) {
            $q->where('kota', 'like', "%{$lokasi}%")
              ->orWhere('lokasi', 'like', "%{$lokasi}%");
        });
    }
}