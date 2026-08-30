CREATE DATABASE IF NOT EXISTS quizizz_db;
USE quizizz_db;

-- Tabel Users untuk Login Host
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL
);

INSERT INTO users (username, password, name) VALUES
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1g.9y.4FjKmgG89J2l9sOdB8P8/nF1a', 'Administrator/Pengajar')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- Tabel Level
CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrument_type ENUM('PAUD_SD_SMP_SLB_PKBM', 'SMA_SMK') NOT NULL,
    level_name VARCHAR(100) NOT NULL,
    level_order INT NOT NULL
);

-- Tabel Fitur / Pertanyaan per Level
CREATE TABLE IF NOT EXISTS features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    usage_practice TEXT NULL,
    example_usage TEXT NULL,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
);

-- Tabel Sesi Game / PIN Key
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pin VARCHAR(10) NOT NULL UNIQUE,
    session_name VARCHAR(255) NOT NULL,
    instrument_type ENUM('PAUD_SD_SMP_SLB_PKBM', 'SMA_SMK') NOT NULL DEFAULT 'PAUD_SD_SMP_SLB_PKBM',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Tabel Responden / Peserta
CREATE TABLE IF NOT EXISTS respondents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    institution VARCHAR(255) NOT NULL,
    completed_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE
);

-- Tabel Jawaban
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    respondent_id INT NOT NULL,
    feature_id INT NOT NULL,
    status ENUM('Sudah', 'Belum') NOT NULL,
    FOREIGN KEY (respondent_id) REFERENCES respondents(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
);

-- ========================================================
-- 1. DATA INSTRUMEN PAUD_SD_SMP_SLB_PKBM
-- ========================================================
INSERT INTO levels (id, instrument_type, level_name, level_order) VALUES
(1, 'PAUD_SD_SMP_SLB_PKBM', 'LEVEL 1. SUBSTITUTION', 1),
(2, 'PAUD_SD_SMP_SLB_PKBM', 'LEVEL 2. AUGMENTATION', 2),
(3, 'PAUD_SD_SMP_SLB_PKBM', 'LEVEL 3. MODIFICATION', 3),
(4, 'PAUD_SD_SMP_SLB_PKBM', 'LEVEL 4. REDEFINITION', 4);

INSERT INTO features (level_id, feature_name, usage_practice, example_usage) VALUES
(1, 'Whiteboard', 'Menulis menggunakan stylus.', 'Menjelaskan konsep atau rumus atau peta konsep.'),
(1, 'Pen Tool', 'Mengubah warna dan ketebalan pena.', 'Memberi penekanan pada informasi penting.'),
(1, 'Eraser, Undo, Redo', 'Menghapus dan memperbaiki tulisan.', 'Menyempurnakan penjelasan tanpa mengulang.'),
(1, 'Media Viewer', 'Membuka PDF, PPT, gambar, dan video.', 'Menyajikan materi pembelajaran digital.'),
(1, 'Annotation', 'Memberi anotasi pada dokumen.', 'Menjelaskan bagian penting suatu materi.'),
(1, 'Save Whiteboard', 'Menyimpan hasil Whiteboard.', 'Membagikan catatan pembelajaran kepada murid.'),

(2, 'Dual Whiteboard', 'Menampilkan dua papan tulis digital secara berdampingan.', 'Membandingkan materi dengan hasil diskusi atau analisis murid secara bersamaan.'),
(2, 'Insert Object', 'Menambahkan gambar dan bentuk.', 'Menyusun diagram atau ilustrasi.'),
(2, 'Shape & Text', 'Membuat bagan sederhana.', 'Menyusun peta konsep bersama murid.'),
(2, 'Screen Capture', 'Mengambil tangkapan layar.', 'Mendokumentasikan hasil pembelajaran.'),
(2, 'Screen Recording', 'Merekam aktivitas layar.', 'Membuat video pembelajaran.'),
(2, 'Export PDF', 'Menyimpan hasil Whiteboard.', 'Membagikan hasil diskusi.'),
(2, 'QR Code', 'Membagikan file.', 'Murid mengakses materi secara mandiri.'),

(3, 'Multi-touch', 'Kolaborasi beberapa pengguna.', 'Murid menyusun peta konsep bersama.'),
(3, 'Screen Sharing', 'Menampilkan layar laptop/HP.', 'Presentasi hasil kerja kelompok.'),
(3, 'Browser', 'Mengakses sumber belajar.', 'Eksplorasi Simulasi Ruang Murid atau PhET.'),
(3, 'Screen Capture & Annotation', 'Mengambil tangkapan layar dan mengimpornya ke Whiteboard.', 'Menganalisis gambar, grafik, atau hasil simulasi melalui anotasi dan diskusi bersama.'),
(3, 'Floating Window', 'Membuka aplikasi pendukung.', 'Membandingkan berbagai sumber belajar.'),

(4, 'Kecerdasan Artifisial', 'Membuat media pembelajaran.', 'Menyusun media interaktif.'),
(4, 'LMS', 'Menghubungkan kelas digital.', 'Distribusi materi dan tugas.'),
(4, 'Video Conference', 'Pembelajaran sinkron.', 'Kolaborasi dengan narasumber.'),
(4, 'Hybrid Learning', 'Menggabungkan berbagai layanan.', 'Belajar luring dan daring.'),
(4, 'Digital Presentation', 'Presentasi hasil belajar.', 'Gallery Walk Digital.');

-- ========================================================
-- 2. DATA INSTRUMEN SMA_SMK (Dari PDF SMA_SMK PID 2026)
-- ========================================================
-- Pemisahan Level untuk SMA_SMK
INSERT INTO levels (id, instrument_type, level_name, level_order) VALUES
(5, 'SMA_SMK', 'Modul Tata Kelola & Keamanan PID', 1),
(6, 'SMA_SMK', 'Level S - Substitution', 2),
(7, 'SMA_SMK', 'Level A - Augmentation', 3),
(8, 'SMA_SMK', 'Level M - Modification', 4),
(9, 'SMA_SMK', 'Level R - Redefinition', 5);

-- 1. Modul Tata Kelola & Keamanan PID (SMA_SMK)
INSERT INTO features (level_id, feature_name, usage_practice, example_usage) VALUES
(5, 'Peralatan pendukung perawatan & pemeliharaan PID', 'Perawatan & Troubleshooting', 'Modul Tata Kelola'),
(5, 'Prosedur & teknis perawatan/pemeliharaan PID', 'Perawatan & Troubleshooting', 'Modul Tata Kelola'),
(5, 'Panduan troubleshooting aplikasi', 'Perawatan & Troubleshooting', 'Modul Tata Kelola'),
(5, 'Pengamanan peralatan (fisik, teknis, operasional)', 'Keamanan & SOP', 'Modul Tata Kelola'),
(5, 'Prosedur penyimpanan PID', 'Keamanan & SOP', 'Modul Tata Kelola'),
(5, 'SOP & form pendukung (kebijakan, checklist, form kendala/instalasi)', 'Keamanan & SOP', 'Modul Tata Kelola'),
(5, 'Fitur manajemen on/off otomatis (auto power schedule)', 'Manajemen Daya & Sistem', 'Modul Tata Kelola'),
(5, 'Manajemen akun pengguna PID (menambah/menghapus/mengatur hak akses)', 'Manajemen Daya & Sistem', 'Modul Tata Kelola');

-- 2. Level S - Substitution (SMA_SMK)
INSERT INTO features (level_id, feature_name, usage_practice, example_usage) VALUES
(6, 'Menghidupkan/mematikan PID & navigasi menu dasar', 'Pengoperasian Dasar Perangkat', 'Substitution'),
(6, 'Mengenal komponen/peralatan PID', 'Pengoperasian Dasar Perangkat', 'Substitution'),
(6, 'Pengaturan dasar audio, kamera & keyboard on-screen', 'Pengoperasian Dasar Perangkat', 'Substitution'),
(6, 'Mengaktifkan & menghubungkan ke jaringan internet / hotspot', 'Pengoperasian Dasar Perangkat', 'Substitution'),
(6, 'Mengubah pengaturan bahasa sistem', 'Pengoperasian Dasar Perangkat', 'Substitution'),
(6, 'Membuka aplikasi Whiteboard & menulis dengan stylus', 'Whiteboard Dasar', 'Substitution'),
(6, 'Mengubah warna, ketebalan pena & fungsi Eraser/Undo/Redo', 'Whiteboard Dasar', 'Substitution'),
(6, 'Mengubah background/kanvas & menambah halaman/jendela baru', 'Whiteboard Dasar', 'Substitution'),
(6, 'Membuka file media (PDF, PPT, gambar, video) & memberi anotasi sederhana', 'Media & Anotasi', 'Substitution'),
(6, 'Menyimpan hasil Whiteboard ke file & menghubungkan perangkat via kabel', 'Penyimpanan & Konektivitas Dasar', 'Substitution');

-- 3. Level A - Augmentation (SMA_SMK)
INSERT INTO features (level_id, feature_name, usage_practice, example_usage) VALUES
(7, 'Split Screen (dua aplikasi) & Split Whiteboard (dua area kerja)', 'Tampilan & Presentasi', 'Augmentation'),
(7, 'Menyisipkan objek, gambar, dan bentuk (shape) ke Whiteboard', 'Whiteboard Lanjutan', 'Augmentation'),
(7, 'Menduplikasi objek/teks & Smart Shape / Smart Word', 'Whiteboard Lanjutan', 'Augmentation'),
(7, 'Menggunakan alat bantu presisi (penggaris, busur, dll)', 'Whiteboard Lanjutan', 'Augmentation'),
(7, 'Mengakses web dari dalam aplikasi Whiteboard', 'Whiteboard Lanjutan', 'Augmentation'),
(7, 'Tangkapan layar (screenshot) & Perekam layar (screen recording)', 'Dokumentasi Layar', 'Augmentation'),
(7, 'Mengekspor hasil Whiteboard ke PDF/gambar', 'Dokumentasi Layar', 'Augmentation'),
(7, 'Manajemen folder & file (USB, internal, cloud)', 'Manajemen File', 'Augmentation'),
(7, 'Berbagi file hasil kerja via QR Code, USB, atau Cloud', 'Berbagi File', 'Augmentation'),
(7, 'Screen sharing nirkabel dari satu laptop/HP ke PID', 'Konektivitas', 'Augmentation'),
(7, 'Gestur multi-touch dasar (pinch/rotate/geser)', 'Konektivitas', 'Augmentation');

-- 4. Level M - Modification (SMA_SMK)
INSERT INTO features (level_id, feature_name, usage_practice, example_usage) VALUES
(8, 'Kolaborasi multi-touch antar banyak pengguna sekaligus', 'Kolaborasi', 'Modification'),
(8, 'Menyunting hasil diskusi bersama secara real-time', 'Kolaborasi', 'Modification'),
(8, 'Screen sharing multi-perangkat untuk kolaborasi/perbandingan kelompok', 'Konektivitas Lanjutan', 'Modification'),
(8, 'Mengakses & memanfaatkan aplikasi/sumber belajar pendukung', 'Eksplorasi Sumber Belajar', 'Modification'),
(8, 'Menampilkan & membandingkan multi-sumber belajar sekaligus', 'Eksplorasi Sumber Belajar', 'Modification'),
(8, 'Menggunakan fitur polling/kuis real-time', 'Asesmen Interaktif', 'Modification');

-- 5. Level R - Redefinition (SMA_SMK)
INSERT INTO features (level_id, feature_name, usage_practice, example_usage) VALUES
(9, 'Menganalisis capaian/tujuan pembelajaran sebagai dasar pengembangan', 'Pengembangan Konten KA', 'Redefinition'),
(9, 'Mengembangkan media pembelajaran interaktif dengan KA', 'Pengembangan Konten KA', 'Redefinition'),
(9, 'Menyusun asesmen (butir soal/rubrik/umpan balik) dengan KA', 'Pengembangan Konten KA', 'Redefinition'),
(9, 'Mengintegrasikan media & asesmen ke skenario pembelajaran utuh', 'Integrasi & Diseminasi', 'Redefinition'),
(9, 'Menyajikan hasil pengembangan untuk umpan balik', 'Integrasi & Diseminasi', 'Redefinition'),
(9, 'Menghubungkan pembelajaran dengan LMS/Video Conference', 'Integrasi & Diseminasi', 'Redefinition');