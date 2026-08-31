# 📋 Lembar Kerja PID (Papan Interaktif Digital)

Aplikasi berbasis web untuk pengelolaan dan pengisian **Lembar Kerja Evaluasi/Eksplorasi Fitur Papan Interaktif Digital (PID)** secara interaktif dan *real-time*. Dikembangkan khusus untuk memfasilitasi kegiatan digitalisasi pembelajaran di lingkungan **BBPMP Provinsi Jawa Timur**.

---

## 🌟 Fitur Utama

### 👨‍🏫 Bagi Host / Fasilitator
* **Manajemen Sesi & Generasi PIN:** Membuat sesi lembar kerja berdasarkan jenis instrumen (PAUD/SD/SMP/SLB/PKBM atau SMA/SMK) dengan Kode PIN kustom atau otomatis.
* **Integrasi QR Code:** Menghasilkan QR Code unik untuk setiap sesi yang dapat diunduh (PNG) untuk memudahkan presensi/pengerjaan peserta.
* **Kontrol Sesi Real-Time:** Membuka/menutup akses pengerjaan peserta (Status Aktif/Nonaktif) secara fleksibel.
* **Pemantauan & Rekap Data:** Memantau kemajuan peserta secara *live*, melihat jawaban individu via pop-up kustom, dan mengunduh rekapitulasi data (Excel/Spreadsheet).
* **Papan Peringkat (Leaderboard):** Tampilan leaderboard untuk melihat tingkat pencapaian peserta secara visual.

### 👨‍🎓 Bagi Peserta / Responden
* **Akses Tanpa Akun:** Cukup melakukan *scan* QR Code atau memasukkan PIN Sesi aktif untuk memulai.
* **Antarmuka Interaktif:** Pilihan status penguasaan fitur (**Sudah / Belum**) dengan tombol pilihan terstruktur per level atau sub-kategori.
* **Validasi Keaktifan Sesi:** Pengisian dipastikan aman dan hanya dapat dilakukan selama sesi dinyatakan aktif oleh Host.
* **Opsi Cetak & Ekspor Multi-Format:**
  * **Cetak Ringkasan (PDF):** Mengunduh ringkasan hasil evaluasi dan pencapaian level.
  * **Cetak Detail per Pertanyaan:** Menghasilkan dokumen cetak resmi format A4 (1 kolom berurut ke bawah) berisi seluruh item pertanyaan beserta status ceklis dan penomoran halaman otomatis (*Halaman X dari Y*).

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** PHP 8.x (PDO / MySQL)
* **Frontend:** HTML5, CSS3, JavaScript (ES6)
* **Framework CSS:** Bootstrap 5.3
* **Icon & UI Components:** Bootstrap Icons, SweetAlert2
* **Fitur Cetak:** Native Browser Print with CSS Paged Media (`@page` counters) & HTML2PDF / jsPDF.
* **Container** Docker
