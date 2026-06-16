# 🏠 Properti Finder

**Properti Subsidized Finder & KPR Matchmaker API**

Aplikasi **Laravel 10 REST API** untuk mencari properti bersubsidi (FLPP), mendapatkan rekomendasi properti berdasarkan gaji, dan melakukan simulasi KPR dengan metode anuitas.

> ⚠️ **Pure Backend API** — Proyek ini hanya berisi backend. Tidak ada frontend (web/mobile) di repository ini.

---

## 📁 Struktur Proyek

```
properti-finder/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── PropertiController.php
│   │   │   └── KprController.php
│   │   └── Models/
│   │       └── Properti.php
│   ├── database/migrations/
│   │   └── 2024_01_01_000001_create_propertis_table.php
│   ├── routes/
│   │   └── api.php
│   ├── composer.json
│   └── .env
└── README.md
```

---

## 🔧 Prasyarat

- **PHP** ^8.1
- **Composer** versi terbaru
- **MySQL** / MariaDB
- **Node.js** & **NPM** (untuk Vite/asset building, opsional)

---

## ⚙️ Instalasi & Konfigurasi

```bash
# 1. Masuk ke direktori backend
cd backend

# 2. Install dependensi Composer
composer install

# 3. Copy .env dan sesuaikan (buat dari .env.example jika ada,
#    atau gunakan template di bawah)
# cp .env.example .env
```

Buat file `.env` dengan konten minimal berikut:

```env
APP_NAME=PropertiFinder
APP_ENV=local
APP_KEY=base64:XXXXXXXXXXXX   # akan digenerate otomatis
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=properti_finder
DB_USERNAME=root
DB_PASSWORD=
```

Lanjutkan:

```bash
# 4. Generate application key
php artisan key:generate

# 5. Buat database di MySQL terlebih dahulu
# mysql -u root -p -e "CREATE DATABASE properti_finder"

# 6. Jalankan migrasi
php artisan migrate

# 7. (Opsional) Isi data properti melalui seeder / database
#    Tambahkan file DatabaseSeeder atau jalankan query manual

# 8. Jalankan development server
php artisan serve
```

---

## 🌐 API Endpoints

### 1. Daftar Properti Subsidi

```
GET /api/properti/subsidi
```

Mengembalikan daftar properti bersubsidi (FLPP) yang masih tersedia.

**Response:**
```json
{
  "success": true,
  "message": "Daftar properti subsidi",
  "data": [...]
}
```

---

### 2. Rekomendasi Properti Berdasarkan Gaji

```
GET /api/properti/rekomendasi?gaji=5000000&lokasi=Jakarta
```

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| `gaji` | number | ✅ | Gaji bulanan (min Rp1.000.000) |
| `lokasi` | string | ❌ | Filter berdasarkan kota/lokasi |

Sistem akan menghitung:
- Maksimal cicilan 30% dari gaji
- Estimasi harga maksimal (tenor 20 tahun, bunga 6% efektif)
- Rekomendasi properti yang sesuai
- Simulasi KPR untuk setiap properti

**Response:**
```json
{
  "success": true,
  "message": "Rekomendasi properti berdasarkan gaji dan lokasi",
  "meta": {
    "gaji": 5000000,
    "max_cicilan": 1500000,
    "max_harga": 209307928.57,
    "total": 3
  },
  "data": [
    {
      "properti": { ... },
      "simulasi_kpr": {
        "harga_properti": 185000000,
        "dp_10_persen": 18500000,
        "pokok_pinjaman": 166500000,
        "bunga_per_tahun": "6%",
        "tenor_tahun": 20,
        "cicilan_per_bulan": 1192985.06,
        "max_cicilan_30_persen_gaji": 1500000,
        "sisa_gaji_setelah_cicilan": 307014.94,
        "layak": true,
        "status_kelayakan": "Layak",
        "rekomendasi": "Rumah ini memenuhi syarat KPR FLPP Bersubsidi. Segera ajukan!"
      }
    }
  ]
}
```

---

### 3. Simulasi KPR

```
POST /api/kpr/simulasi
Content-Type: application/json
```

| Parameter | Tipe | Wajib | Default | Deskripsi |
|-----------|------|-------|---------|-----------|
| `gaji` | number | ✅ | — | Gaji bulanan (min Rp1.000.000) |
| `harga_properti` | number | ✅ | — | Harga properti (min Rp50.000.000) |
| `dp` | number | ❌ | 10 | Persentase DP (0-100) |
| `tenor_tahun` | integer | ❌ | 20 | Tenor KPR (5, 10, 15, atau 20 tahun) |
| `bunga_per_tahun` | number | ❌ | 6 | Suku bunga tahunan dalam persen |

**Response:**
```json
{
  "success": true,
  "message": "Hasil simulasi KPR",
  "data": {
    "input": { ... },
    "hasil": {
      "cicilan_per_bulan": 1192985.06,
      "total_pembayaran": 286316414.94,
      "total_bunga": 119816414.94,
      "max_cicilan_30_persen": 1500000,
      "sisa_gaji": 307014.94
    },
    "kelayakan": {
      "layak": true,
      "status": "✅ LAYAK",
      "pesan": "Selamat! Anda layak mengajukan KPR untuk properti ini."
    },
    "rincian_tahunan": [
      {
        "tahun": 1,
        "pokok": 5603893.29,
        "bunga": 8713927.47,
        "sisa_pokok": 160896106.71
      },
      ...
    ]
  }
}
```

---

## 🧪 Menjalankan Test

```bash
cd backend
php artisan test
```

---

## 🛠 Teknologi yang Digunakan

- **Laravel 10** — Framework backend PHP
- **MySQL** — Database relasional
- **Validator Laravel** — Validasi input request

---

## 📊 Logika Bisnis

### Perhitungan KPR (Metode Anuitas)

```
Cicilan per bulan = P × [r(1+r)^n] / [(1+r)^n - 1]

Dimana:
P = Pokok pinjaman (harga - DP)
r = Bunga bulanan (bunga tahunan / 12)
n = Tenor dalam bulan
```

### Kelayakan KPR
- Maksimal cicilan **30%** dari gaji bulanan
- Jika cicilan ≤ 30% gaji → **LAYAK**
- Jika cicilan > 30% gaji → **TIDAK LAYAK**

### Batas Harga Rumah Subsidi FLPP
- Maksimal harga properti subsidi: **Rp 200.000.000**