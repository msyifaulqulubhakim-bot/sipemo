# LAPORAN BACKUP DATABASE SIPeMo SEBELUM PENGAMANAN
**Project:** SIPeMo (`C:\laragon\www\SiPeMo`)  
**Waktu Backup:** 2026-11-12 14:12:55 WIB  
**Status Eksekusi:** SUKSES  
**Dokumen:** `laporan_backup_database_sipemo.md`

---

## 1. Identitas Database yang Dicadangkan

* **Database Name:** `sipemo`
* **DBMS:** MySQL Community Server v8.0.30 (Laragon x64)
* **Host / Port:** `127.0.0.1:3306`
* **User:** `root` (Password: kosong)
* **Koneksi Aplikasi (`.env`):** `DB_CONNECTION=mysql`, `DB_DATABASE=sipemo`
* **Data Directory MySQL:** `C:\laragon\data\mysql-8\`

---

## 2. Jumlah Tabel

Total tabel yang dicadangkan: **20 tabel** (100% dari seluruh tabel pada database `sipemo`).

Daftar tabel:
1. `cache`
2. `cache_locks`
3. `failed_jobs`
4. `final_drafts`
5. `job_batches`
6. `jobs`
7. `jurusans`
8. `mata_kuliahs`
9. `migrations`
10. `moduls`
11. `notifications`
12. `password_reset_tokens`
13. `penyusun_applications`
14. `periode_histories`
15. `periode_penyusunans`
16. `publication_moduls`
17. `reviewer_applications`
18. `sessions`
19. `tahap_penyusunans`
20. `users`

---

## 3. Jumlah Migration

* **Total Migrasi Tercatat:** **34 migration** (seluruhnya pada Batch 1).
* **Migrasi Tahap 1 yang Termasuk:**
  - `2026_11_11_140000_create_periode_penyusunans_table`
  - `2026_11_11_140100_create_periode_histories_table`
  - `2026_11_11_140200_add_periode_id_to_tahap_penyusunans_table`
  - `2026_11_11_140300_add_periode_id_to_penyusun_applications_table`
  - `2026_11_11_141500_refine_periode_and_history_tables`

---

## 4. Lokasi dan Ukuran Backup

* **Direktori Penyimpanan:** `C:\Users\DELL\Downloads\SIPeMo_Backups\`
* **Nama File:** `sipemo_backup_20261112_141255.sql`
* **Ukuran File:** **31.703 bytes (30,96 KB)**
* **Waktu Pembuatan File:** 12 November 2026 14:12:57 WIB
* **Perintah Pembuatan:**
  ```powershell
  mysqldump.exe --host=127.0.0.1 --port=3306 --user=root --single-transaction --routines --triggers --databases sipemo --result-file="C:\Users\DELL\Downloads\SIPeMo_Backups\sipemo_backup_20261112_141255.sql"
  ```

### Status Backup Lama:
* **File:** `C:\Users\DELL\Downloads\sipemo.sql`
* **Ukuran:** 30.693 bytes
* **Timestamp:** 10 November 2026 13:39:49 WIB
* **Status:** **TIDAK TERGANGGU / TIDAK DITIMPA (PRESERVED)**

---

## 5. Hasil Pemeriksaan Integritas File Backup

Pengujian integritas struktur file SQL menunjukkan:
1. **Header Validitas SQL:** Header standar `MySQL dump` ditemukan (`Valid`).
2. **Footer Validitas SQL:** Tag penutup `Dump completed` ditemukan (`Valid`).
3. **Cakupan Tabel:** Seluruh 20 definisi `CREATE TABLE` ditemukan lengkap di dalam file.
4. **Elemen Kunci Tahap 1:**
   - Tabel `periode_penyusunans`: **Lengkap** (beserta kolom `active_guard` virtual dan unique index `unique_active_periode`).
   - Tabel `periode_histories`: **Lengkap** (beserta foreign key `ON DELETE RESTRICT`).
   - Kolom `periode_id` pada `tahap_penyusunans` dan `penyusun_applications`: **Lengkap**.
5. **Ukuran File:** 31.703 bytes (valid, tidak kosong/bukan 0 KB).

---

## 6. Konfirmasi Bahwa Database Tidak Mengalami Perubahan

Pemeriksaan ulang pasca-backup terhadap database aktif `sipemo` memastikan:
* Tidak ada perintah DDL (`ALTER`, `DROP`, `TRUNCATE`, `CREATE`) yang dijalankan pada database.
* Tidak ada operasi DML (`INSERT`, `UPDATE`, `DELETE`) yang dijalankan pada tabel aplikasi.
* Jumlah baris pada seluruh 20 tabel identik persis dengan kondisi sebelum backup dimulai:
  - Seluruh tabel aplikasi utama (`users`, `jurusans`, `mata_kuliahs`, `penyusun_applications`, `tahap_penyusunans`, `moduls`, `final_drafts`, `publication_moduls`, `reviewer_applications`, `notifications`, `periode_penyusunans`, `periode_histories`) tetap pada 0 baris.
  - Tabel `migrations` tetap berisi 34 baris.
  - Tabel sistem runtime Laravel (`cache` 4 baris, `sessions` 3 baris) tidak mengalami anomali.

---
*Status: Backup selesai dan terverifikasi secara penuh. Tidak ada file source code yang diubah. Sesuai instruksi mutlak, pengerjaan dihentikan di sini menunggu arahan dan persetujuan Anda sebelum melanjutkan ke implementasi safety guard.*

