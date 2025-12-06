# AI-Powered-Fraud-Detection
Website ini adalah sebuah website yang dapat mendeteksi atau memprediksi tingkat kebohongan dari suatu klaim. klaim yang ada disini adalah klaim asuransi, untuk saat ini difokuskan ke asuransi kesehatan.

# 🛡️ AI-Powered Insurance Fraud Detection System

![Project Status](https://img.shields.io/badge/status-active-success)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)
![Python](https://img.shields.io/badge/Python-3.x-3776AB)
![MySQL](https://img.shields.io/badge/MySQL-Advanced-4479A1)

Sistem cerdas untuk mendeteksi potensi kecurangan (*fraud*) pada klaim asuransi kesehatan. Aplikasi ini menggunakan arsitektur **Hybrid (Polyglot)** yang menggabungkan kemudahan antarmuka **PHP** dengan kekuatan komputasi **Python**.

> **Project ini dibuat untuk memenuhi tugas mata kuliah Pemrograman Web & Basis Data Lanjut.**

## 📸 Tampilan Aplikasi

| Input Form (Smart Estimate) | Analysis Result (Risk Score) |
|:---------------------------:|:----------------------------:|
| ![Input Form](screenshots/input_form.jpg) | ![Result](screenshots/result_page.jpg) |

| Admin Dashboard (CRUD) |
|:----------------------:|
| ![Dashboard](screenshots/dashboard.jpg) |

*(Pastikan kamu mengupload gambar ke folder screenshots/ agar gambar di atas muncul)*

## ✨ Fitur Utama

### 🧠 1. Hybrid Engine (PHP x Python)
* **PHP (Frontend & Controller):** Menangani antarmuka pengguna, manajemen database CRUD, dan pelaporan.
* **Python (Backend Logic):** Bertindak sebagai "otak" untuk menghitung probabilitas fraud menggunakan algoritma statistik.

### 🔍 2. Algoritma Deteksi (Fraud Engine)
Sistem menilai risiko berdasarkan parameter berikut:
* **Bayesian Probability:** Menghitung peluang fraud berdasarkan data historis.
* **Anomaly Detection:** Mendeteksi klaim biaya yang jauh di atas rata-rata (`Min/Max Cost` threshold).
* **Hospital Risk Score:** Memberikan bendera merah pada Rumah Sakit dengan riwayat fraud tinggi.
* **CBR (Case-Based Reasoning):** Mencocokkan klaim baru dengan modus operandi kasus fraud masa lalu.

### 🛠️ 3. Fitur Database Lanjut (MySQL)
Project ini tidak hanya tabel biasa, tetapi menerapkan:
* ✅ **Stored Procedures:** `sp_update_claim` untuk keamanan update data.
* ✅ **Triggers:** `tr_before_delete_claim` untuk audit trail (backup data sebelum dihapus).
* ✅ **Views:** `v_claim_report` untuk penyajian laporan yang efisien.
* ✅ **Functions:** `f_get_risk_label` untuk penentuan label kategori (SAFE/SUSPICIOUS/HIGH RISK) di level database.

### 💻 4. User Experience (UX)
* **Smart Input:** Autocomplete nama Rumah Sakit & Format Rupiah otomatis.
* **Dynamic Estimation:** Estimasi biaya & lama rawat muncul otomatis berdasarkan diagnosis yang dipilih.
* **Responsive Design:** Tampilan adaptif untuk Laptop, Tablet, dan Mobile.
* **Auto-Learning:** Jika terdeteksi *High Risk*, database otomatis memperbarui statistik risiko RS dan menyimpan kasus baru sebagai referensi.

## 🚀 Instalasi & Cara Menjalankan

### Prasyarat
* Web Server (Laragon / XAMPP) dengan PHP 7.4+.
* Python 3.x terinstall di sistem (`PATH` environment variable harus aktif).
* MySQL / MariaDB.

### Langkah-langkah
1.  **Clone Repository**
    ```bash
    git clone [https://github.com/username-kamu/insurance-fraud-detection.git](https://github.com/username-kamu/insurance-fraud-detection.git)
    cd insurance-fraud-detection
    ```

2.  **Setup Database**
    * Buat database baru bernama `fraud_detection`.
    * Import file `database.sql` yang ada di repository ini ke database tersebut.

3.  **Setup Python Dependencies**
    Install driver MySQL untuk Python:
    ```bash
    pip install mysql-connector-python
    ```

4.  **Konfigurasi Koneksi**
    * Cek file `koneksi.php` (untuk PHP).
    * Cek file `database.py` (untuk Python).
    * Pastikan username & password database sesuai dengan settingan lokal kamu (default Laragon: user `root`, pass kosong).

5.  **Jalankan**
    * Simpan folder project di `C:\laragon\www\` atau `htdocs`.
    * Buka browser dan akses: `http://localhost/insurance-fraud-detection/index.php`

## 📂 Struktur Folder
