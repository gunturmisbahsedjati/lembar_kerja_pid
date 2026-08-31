<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Panduan Penggunaan Aplikasi Lembar Kerja PID</title>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 40px;
            max-width: 800px;
            margin: auto;
        }

        h1 {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            text-align: center;
        }

        h2 {
            color: #212529;
            background-color: #e9ecef;
            padding: 8px 12px;
            border-left: 5px solid #0d6efd;
            margin-top: 30px;
        }

        h3 {
            color: #495057;
            margin-top: 20px;
        }

        ol,
        ul {
            padding-left: 25px;
        }

        li {
            margin-bottom: 8px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <h1>Panduan Penggunaan Aplikasi<br>Lembar Kerja PID</h1>

    <p>Dokumen ini berisi petunjuk penggunaan aplikasi <strong>Lembar Kerja PID</strong> yang ditujukan bagi <strong>Host (Fasilitator)</strong> dan <strong>Peserta (Responden)</strong>.</p>

    <h2>1. Alur Penggunaan untuk Host (Fasilitator)</h2>
    <p>Host bertugas membuat sesi lembar kerja, mengelola Kode PIN, membagikan link/QR Code, serta memantau dan mengunduh hasil pengisian peserta.</p>

    <h3>Langkah 1: Login Host</h3>
    <ul>
        <li>Akses halaman login aplikasi (misal: <code>/login</code>).</li>
        <li>Masukkan <em>username</em> dan <em>password</em> akun Host.</li>
        <li>Klik <strong>Login</strong> untuk masuk ke Dashboard Host.</li>
    </ul>

    <h3>Langkah 2: Membuat Sesi & PIN Key Baru</h3>
    <ul>
        <li>Pada Dashboard Host, masukkan <strong>Nama Sesi / Kelas</strong> dan pilih <strong>Jenis Instrumen</strong>.</li>
        <li>(Opsional) Isikan <strong>PIN (Opsional)</strong> jika ingin menentukan PIN kustom secara manual, atau biarkan kosong agar sistem mengacak PIN 6-digit otomatis.</li>
        <li>Klik <strong>Buat PIN</strong>. Notifikasi berhasil/gagal akan muncul melalui pesan pop-up <em>Toast</em> di kanan atas.</li>
    </ul>

    <h3>Langkah 3: Membagikan QR Code Sesi</h3>
    <ul>
        <li>Pada daftar tabel sesi, klik tombol <strong>QR Code</strong> di baris sesi yang diinginkan.</li>
        <li>Halaman QR Code otomatis menampilkan <strong>Nomor PIN Key Sesi</strong> dan logo aplikasi di tengah QR Code.</li>
        <li>Klik tombol <strong>Unduh QR Code (PNG)</strong> untuk mengunduh gambar QR Code dengan nama file berformat PIN terkait.</li>
        <li><em>Catatan Validasi:</em> QR Code hanya dapat diakses jika parameter PIN valid dan status sesi dalam kondisi <strong>AKTIF</strong>.</li>
    </ul>

    <h3>Langkah 4: Mengelola Sesi & Memantau Peserta</h3>
    <ul>
        <li><strong>Ubah Status:</strong> Klik tombol <strong>Aktifkan/Nonaktifkan</strong> untuk membuka atau menutup akses peserta ke PIN terkait.</li>
        <li><strong>Detail Sesi:</strong> Masuk ke halaman <strong>Detail</strong> (<code>detail.php</code>) untuk memantau peserta secara <em>real-time</em> dan melihat jawaban individual via modal pop-up.</li>
        <li><strong>Hapus Sesi:</strong> Klik tombol <strong>Hapus</strong> untuk menghapus PIN. Sistem akan menampilkan dialog konfirmasi kustom <em>SweetAlert2</em> sebelum data benar-benar dihapus.</li>
    </ul>

    <h3>Langkah 5: Melihat Leaderboard & Rekap Data</h3>
    <ul>
        <li>Klik tombol <strong>Leaderboard</strong> untuk membuka papan peringkat pencapaian peserta pada jendela baru.</li>
        <li>Di dalam halaman detail, Anda dapat mengunduh rekapitulasi seluruh jawaban peserta dalam format spreadsheet.</li>
    </ul>

    <h2>2. Alur Penggunaan untuk Peserta (Responden)</h2>
    <p>Peserta tidak perlu mendaftar akun, cukup melakukan scan QR Code Sesi atau memasukkan Kode PIN Sesi yang diberikan oleh Host.</p>

    <h3>Langkah 1: Bergabung ke Sesi</h3>
    <ul>
        <li>Buka halaman utama aplikasi atau <em>scan</em> <strong>QR Code Sesi</strong>.</li>
        <li>Masukkan <strong>Kode PIN Sesi</strong> (pastikan sesi statusnya aktif).</li>
        <li>Isikan <strong>Nama Lengkap</strong> dan <strong>Instansi / Sekolah</strong>.</li>
        <li>Klik <strong>Mulai / Bergabung</strong>.</li>
    </ul>

    <h3>Langkah 2: Mengisi Lembar Kerja</h3>
    <ul>
        <li>Peserta memilih status penguasaan pada setiap indikator/fitur (<strong>Sudah</strong> atau <strong>Belum</strong>).</li>
        <li>Selesaikan indikator pada level berjalan untuk membuka level berikutnya.</li>
    </ul>

    <h3>Langkah 3: Menyelesaikan Sesi</h3>
    <ul>
        <li>Kirim/simpan jawaban akhir setelah seluruh level terisi.</li>
        <li>Lihat ringkasan pencapaian level dan jumlah fitur yang dikuasai.</li>
    </ul>

    <div class="footer">
        Dokumen Panduan Lembar Kerja PID &bull; BBPMP Provinsi Jawa Tengah &bull; <?= date('Y') ?>
    </div>

</body>

</html>