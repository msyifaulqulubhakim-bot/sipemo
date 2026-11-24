# LAPORAN AUDIT & RENCANA PENGAMANAN DATABASE TESTING SIPeMo
**Project:** SIPeMo (`C:\laragon\www\SiPeMo`)  
**Mode:** AUDIT & PLANNING ONLY (READ-ONLY)  
**Waktu Audit:** 2026-11-11  
**Dokumen Output:** `laporan_audit_pengamanan_testing.md`

---

## 1. Kondisi Konfigurasi Sekarang

### 1.1. Konfigurasi Lingkungan Aplikasi
* **File `.env`:**
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=sipemo`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=`
  *Status:* Aplikasi operasional menggunakan MySQL database `sipemo`.

* **File `.env.testing`:**
  - **Tidak ada** di root project.

* **File `config/database.php`:**
  - Default connection: `mysql`.
  - Definisi koneksi `sqlite` sudah tersedia standar dari framework Laravel.

* **File `phpunit.xml`:**
  - Mengarahkan `DB_CONNECTION=mysql` dan `DB_DATABASE=sipemo`.
  *Peringatan:* Jika dijalankan dalam kondisi ini tanpa isolasi ketat, perintah pengujian dapat berinteraksi langsung dengan database MySQL `sipemo`.

* **File `tests/TestCase.php`:**
  - Saat ini merupakan class kosong:
    ```php
    abstract class TestCase extends BaseTestCase
    {
        //
    }
    ```
  - **Belum memiliki mekanisme proteksi (safety guard)** apa pun untuk mencegah eksekusi pengujian pada database utama.

* **Kondisi Driver PHP Runtime (Laragon PHP 8.3.36):**
  - Pemeriksaan `PDO::getAvailableDrivers()`: **Hanya mengembalikan `mysql`**.
  - Pemeriksaan `C:\laragon\bin\php\php-8.3.36-1-Win32-vs16-x64\php.ini`:
    - Baris 961: `;extension=pdo_sqlite` (**DINONAKTIFKAN / DIKOMENTARI**)
    - Baris 972: `;extension=sqlite3` (**DINONAKTIFKAN / DIKOMENTARI**)
  - *Temuan Kritis:* File DLL `php_pdo_sqlite.dll` dan `php_sqlite3.dll` tersedia secara fisik di folder `ext/`, namun **belum diaktifkan** di `php.ini`.

---

## 2. Daftar Semua Test dan Potensi Risikonya

Terdapat **10 file pengujian** di direktori `tests/`:

| No | File Pengujian | Trait Database Saat Ini | Analisis & Potensi Risiko | Tingkat Risiko |
|:--:|:---|:---:|:---|:---:|
| 1 | `tests/Unit/ExampleTest.php` | Tidak ada | Assert logic murni (`assertTrue(true)`), tanpa koneksi DB. | **Aman (Hijau)** |
| 2 | `tests/Feature/ExampleTest.php` | Tidak ada | HTTP GET `/` (read-only render view). | **Aman (Hijau)** |
| 3 | `tests/Feature/PeriodeDatabaseFoundationTest.php` | `DatabaseTransactions` | Melakukan DML (insert, update, delete) pada tabel Periode, Tahap, History, User, Jurusan, MK, Aplikasi. Aman jika di dalam transaksi, namun jika dijalankan di MySQL tanpa isolasi, nilai `AUTO_INCREMENT` tetap naik. | **Sedang (Kuning)** |
| 4 | `tests/Feature/ProfileTest.php` | `DatabaseTransactions` | Menguji manipulasi user profile. Jika kembali dipasang `RefreshDatabase`, akan memicu `migrate:fresh`. | **Tinggi jika ada RefreshDatabase** |
| 5 | `tests/Feature/Auth/AuthenticationTest.php` | `DatabaseTransactions` | Menguji login/logout user. Berbahaya jika menggunakan `RefreshDatabase`. | **Tinggi jika ada RefreshDatabase** |
| 6 | `tests/Feature/Auth/EmailVerificationTest.php` | `DatabaseTransactions` | Menguji verifikasi email user. Berbahaya jika menggunakan `RefreshDatabase`. | **Tinggi jika ada RefreshDatabase** |
| 7 | `tests/Feature/Auth/PasswordConfirmationTest.php` | `DatabaseTransactions` | Menguji konfirmasi password. Berbahaya jika menggunakan `RefreshDatabase`. | **Tinggi jika ada RefreshDatabase** |
| 8 | `tests/Feature/Auth/PasswordResetTest.php` | `DatabaseTransactions` | Menguji reset password token. Berbahaya jika menggunakan `RefreshDatabase`. | **Tinggi jika ada RefreshDatabase** |
| 9 | `tests/Feature/Auth/PasswordUpdateTest.php` | `DatabaseTransactions` | Menguji pembaruan password. Berbahaya jika menggunakan `RefreshDatabase`. | **Tinggi jika ada RefreshDatabase** |
| 10 | `tests/Feature/Auth/RegistrationTest.php` | `DatabaseTransactions` | Menguji registrasi user baru. Berbahaya jika menggunakan `RefreshDatabase`. | **Tinggi jika ada RefreshDatabase** |

### ⚠️ Bahaya Penggunaan `RefreshDatabase` vs `DatabaseTransactions`:
* **`RefreshDatabase`:** Menjalankan `migrate:fresh` / `db:wipe` jika state migrasi berubah. Pada MySQL database `sipemo`, ini menyebabkan insiden fatal terhapusnya seluruh tabel (seperti yang terjadi pada 11 Nov 14:28:36 WIB).
* **`DatabaseTransactions`:** Membungkus operasi test di dalam `START TRANSACTION` dan melakukan `ROLLBACK` di `tearDown()`. Tidak melakukan drop tabel, namun jika terjadi *implicit commit* (seperti query DDL `ALTER`/`CREATE`), transaksi akan ter-commit permanen ke database.

---

## 3. Kompatibilitas Migration SIPeMo dengan SQLite

Dari audit terhadap seluruh **34 file migration** pada direktori `database/migrations/`, ditemukan bahwa **aplikasi SIPeMo saat ini TIDAK KOMPATIBEL SECARA LANGSUNG (OUT-OF-THE-BOX) dengan SQLite**.

Berikut adalah rincian ketidakkompatibelannya:

### 3.1. Masalah Kritis 1: Raw SQL DDL Spesifik MySQL
Dua file migration mengeksekusi perintah mentah `DB::statement()` yang menggunakan sintaks eksklusif MySQL (`ALTER TABLE ... MODIFY COLUMN ... ENUM(...)`):
1. **`database/migrations/2025_10_16_060258_update_penyusun_applications_status_enum.php`:**
   - Baris 21 & 32:
     ```php
     DB::statement("ALTER TABLE penyusun_applications MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
     ```
     *Dampak di SQLite:* SQLite **TIDAK MENDUKUNG** klausa `MODIFY COLUMN` dan tidak mengenal tipe data `ENUM`. Menjalankan migrasi ini di SQLite akan langsung melempar error fatal:
     `PDOException: near "MODIFY": syntax error`.
2. **`database/migrations/2026_11_11_141500_refine_periode_and_history_tables.php`:**
   - Baris 16 & 35:
     ```php
     DB::statement("ALTER TABLE periode_penyusunans MODIFY COLUMN status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft'");
     ```
     *Dampak di SQLite:* Error fatal sintaksis yang sama persis (`near "MODIFY": syntax error`).

### 3.2. Masalah Kritis 2: Drop Foreign Key di SQLite
Terdapat **10 pemanggilan `$table->dropForeign(...)`** pada file migration (misalnya pada `refine_periode_and_history_tables`, `update_tahap_penyusunans`, dll.).  
SQLite tidak memiliki fitur native untuk mendrop foreign key constraint tanpa membuat ulang seluruh struktur tabel. Pada SQLite in-memory, pemanggilan dropForeign sering menimbulkan deadlock constraint violation jika foreign key checks aktif.

### 3.3. Masalah Kritis 3: Driver PHP SQLite Belum Aktif
Pada environment PHP Laragon saat ini, ekstensi `pdo_sqlite` masih dikomentari di `php.ini`. Menjalankan koneksi SQLite saat ini akan langsung menghasilkan error: `could not find driver`.

---

## 4. Rekomendasi Konfigurasi Paling Aman

Untuk memastikan database utama `sipemo` **100% TERLINDUNGI DAN TIDAK PERNAH TERSENTUH PENGUJIAN**, direkomendasikan strategi pertahanan berlapis:

### Lapisan 1: Evaluasi Pilihan Engine Testing

| Pendekatan | Kelebihan | Tantangan / Syarat | Rekomendasi |
|---|---|---|:---:|
| **Opsi A: SQLite In-Memory (`:memory:`)** | - 100% berjalan di RAM.<br>- Tidak ada file fisik.<br>- Mustahil menghapus database MySQL `sipemo`. | - Wajib mengaktifkan `extension=pdo_sqlite` di `php.ini`.<br>- Wajib memberi guard `if (DB::getDriverName() === 'mysql')` pada 2 migration raw DDL. | **Sangat Direkomendasikan Jangka Panjang** |
| **Opsi B: Fail-Safe Guard pada `TestCase.php` (Anti-sipemo Shield)** | - Menjamin pengujian DIBATALKAN SEKETIKA jika koneksi mengarah ke database `sipemo`.<br>- Bekerja pada engine apa pun. | - Tidak memerlukan pengubahan `php.ini`. | **Wajib Dipasang Segera** |

---

## 5. Daftar File yang Perlu Diubah Nantinya (Setelah Disetujui)

Berikut adalah rencana perubahan file yang dirancang secara presisi:

### 5.1. File `tests/TestCase.php` (Pemasangan Anti-sipemo Shield)
Tambahkan safety check mutlak pada method `setUp()`:
```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // FAIL-SAFE GUARD: Batalkan seketika jika pengujian mengarah ke MySQL database 'sipemo'
        $connection = config('database.default');
        $databaseName = config("database.connections.{$connection}.database");

        if ($connection === 'mysql' && strtolower($databaseName) === 'sipemo') {
            throw new \RuntimeException(
                "CRITICAL SAFETY VIOLATION: Pengujian mendeteksi koneksi ke database utama 'sipemo'! " .
                "PHPUnit dibatalkan seketika demi melindungi database produksi."
            );
        }
    }
}
```

### 5.2. File `C:\laragon\bin\php\php-8.3.36-1-Win32-vs16-x64\php.ini` (Jika Memilih SQLite)
Aktifkan ekstensi SQLite:
```ini
; SEBELUM
;extension=pdo_sqlite
;extension=sqlite3

; SESUDAH
extension=pdo_sqlite
extension=sqlite3
```

### 5.3. Penyesuaian 2 Migration Raw DDL (Jika Memilih SQLite)
Bungkus statement raw MySQL agar tidak dieksekusi saat menggunakan driver SQLite:
1. `database/migrations/2025_10_16_060258_update_penyusun_applications_status_enum.php`:
   ```php
   if (\DB::getDriverName() === 'mysql') {
       \DB::statement("ALTER TABLE penyusun_applications MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
   }
   ```
2. `database/migrations/2026_11_11_141500_refine_periode_and_history_tables.php`:
   ```php
   if (\DB::getDriverName() === 'mysql') {
       \DB::statement("ALTER TABLE periode_penyusunans MODIFY COLUMN status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft'");
   }
   ```

### 5.4. File `phpunit.xml` (Jika Memilih SQLite)
Arahkan lingkungan testing ke SQLite in-memory:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 6. Risiko dan Dampak Setiap Perubahan

1. **Dampak Pemasangan Safety Guard di `TestCase.php`:**
   - **Risiko:** 0%.
   - **Dampak:** Memberikan jaminan 100% bahwa jika ada developer atau command line yang lupa dan menjalankan `phpunit` langsung ke `sipemo`, sistem akan melempar exception dan berhenti sebelum menyentuh data.
2. **Dampak Pengaktifan SQLite di `php.ini`:**
   - **Risiko:** Rendah (hanya mengaktifkan modul resmi PHP yang sudah ada di folder `ext/`).
   - **Dampak:** Memungkinkan pengujian secepat kilat di dalam memori tanpa I/O disk.
3. **Dampak Guard `if (\DB::getDriverName() === 'mysql')` pada Migration:**
   - **Risiko:** Sangat rendah.
   - **Dampak:** Menjaga agar pada environment MySQL produksi perilakunya tetap 100% sama, sementara pada SQLite in-memory tidak terjadi crash sintaks.

---

## 7. Rencana Pengujian Setelah Konfigurasi Disetujui

Setelah konfigurasi disetujui dan diterapkan:

1. **Uji Validasi Safety Guard (Simulasi Pelanggaran):**
   - Secara sengaja atur koneksi test ke `sipemo`.
   - Jalankan test tunggal.
   - **Hasil yang Diharapkan:** Test harus melempar exception `CRITICAL SAFETY VIOLATION` dan berhenti tanpa mengubah database.
2. **Uji Driver SQLite In-Memory:**
   - Pastikan `php -r "echo extension_loaded('pdo_sqlite');"` bernilai `1`.
3. **Uji Eksekusi Migration pada SQLite In-Memory:**
   - Jalankan `php artisan test --filter=ExampleTest` dengan koneksi SQLite `:memory:`.
   - Pastikan migrasi in-memory berhasil tanpa error `near "MODIFY"`.
4. **Verifikasi Integritas MySQL `sipemo`:**
   - Periksa database MySQL `sipemo` untuk memastikan tidak ada tabel atau data yang tersentuh selama pengujian SQLite berjalan.

---
*Status: Laporan audit dan perencanaan selesai. Sesuai larangan mutlak, **TIDAK ADA PERUBAHAN SUMBER DAYA APAPUN** yang dilakukan.*

