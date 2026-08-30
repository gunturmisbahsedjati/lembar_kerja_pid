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
    <p>Host bertugas membuat sesi lembar kerja, membagikan Kode PIN, serta memantau dan mengunduh hasil pengisian peserta.</p>

    <h3>Langkah 1: Login Host</h3>
    <ul>
        <li>Akses halaman login aplikasi (misal: <code>/login</code>).</li>
        <li>Masukkan <em>username</em> dan <em>password</em> akun Host.</li>
        <li>Klik <strong>Login</strong> untuk masuk ke Dashboard Host.</li>
    </ul>

    <h3>Langkah 2: Membuat Sesi Baru</h3>
    <ul>
        <li>Pada Dashboard Host, buat sesi permainan/lembar kerja baru.</li>
        <li>Masukkan <strong>Nama Sesi</strong> dan pilih <strong>Jenis Instrumen</strong>.</li>
        <li>Klik <strong>Buat Sesi</strong>. Sistem secara otomatis menghasilkan <strong>Kode PIN</strong> unik.</li>
    </ul>

    <h3>Langkah 3: Mengelola & Memantau Peserta</h3>
    <ul>
        <li>Bagikan Kode PIN sesi kepada para peserta.</li>
        <li>Masuk ke halaman <strong>Detail Sesi</strong> (<code>detail.php</code>) untuk memantau peserta secara <em>real-time</em>.</li>
        <li>Klik tombol <strong>Detail</strong> pada nama peserta untuk melihat jawaban individual via pop-up modal.</li>
        <li>Klik tombol <strong>Hapus</strong> jika ada peserta yang perlu dikeluarkan dari sesi.</li>
    </ul>

    <h3>Langkah 4: Melihat Leaderboard & Rekap Data</h3>
    <ul>
        <li>Klik tombol <strong>Lihat Leaderboard</strong> untuk melihat peringkat pencapaian peserta.</li>
        <li>Klik tombol <strong>📊 Cetak Excel</strong> untuk mengunduh rekapitulasi seluruh jawaban peserta.</li>
    </ul>

    <h2>2. Alur Penggunaan untuk Peserta (Responden)</h2>
    <p>Peserta tidak perlu mendaftar akun, cukup menggunakan Kode PIN Sesi yang diberikan oleh Host.</p>

    <h3>Langkah 1: Bergabung ke Sesi</h3>
    <ul>
        <li>Buka halaman utama aplikasi.</li>
        <li>Masukkan <strong>Kode PIN Sesi</strong>.</li>
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
        Dokumen Panduan Lembar Kerja PID &bull; Dicetak secara otomatis
    </div>

</body>

</html>