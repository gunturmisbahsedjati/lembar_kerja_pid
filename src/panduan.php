<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Panduan Penggunaan Aplikasi Lembar Kerja PID</title>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
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

        .badge-new {
            background-color: #198754;
            color: #fff;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: bold;
            vertical-align: middle;
            margin-left: 5px;
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

            .no-print {
                display: none;
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
        <li><em>Catatan Validasi Sesi:</em> QR Code hanya dapat diakses jika parameter PIN valid dan status sesi dalam kondisi <strong>AKTIF</strong>. Sistem secara otomatis menolak akses ke sesi yang dinonaktifkan/ditutup.</li>
    </ul>

    <h3>Langkah 4: Mengelola Sesi & Memantau Peserta</h3>
    <ul>
        <li><strong>Ubah Status (Sesi Aktif/Nonaktif):</strong> Klik tombol <strong>Aktifkan/Nonaktifkan</strong> untuk membuka atau menutup akses pengerjaan peserta secara langsung. <span class="badge-new">Update</span></li>
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
        <li>Masukkan <strong>Kode PIN Sesi</strong>. Sistem akan memverifikasi apakah PIN terdaftar dan status sesi aktif. <span class="badge-new">Update</span></li>
        <li>Isikan <strong>Nama Lengkap</strong> dan <strong>Instansi / Sekolah</strong>.</li>
        <li>Klik <strong>Mulai / Bergabung</strong>.</li>
    </ul>

    <h3>Langkah 2: Mengisi Lembar Kerja</h3>
    <ul>
        <li>Peserta memilih status penguasaan pada setiap indikator/fitur (<strong>Sudah</strong> atau <strong>Belum</strong>) melalui tombol pilihan interaktif.</li>
        <li>Selesaikan seluruh indikator pada level berjalan untuk dapat menyimpan dan membuka level berikutnya.</li>
        <li>Sistem akan terus melakukan validasi sesi aktif saat pengiriman lembar kerja. Jika sesi ditutup oleh Host, pengerjaan tidak dapat dilanjutkan. <span class="badge-new">Update</span></li>
    </ul>

    <h3>Langkah 3: Menyelesaikan Sesi & Fitur Pencetakan <span class="badge-new">Update</span></h3>
    <ul>
        <li>Kirim/simpan jawaban akhir setelah seluruh level terisi. Sistem akan mengarahkan ke halaman hasil/ringkasan.</li>
        <li>Pada halaman ringkasan, peserta dapat memanfaatkan tombol **Dropdown Menu Cetak** untuk memilih mode pencetakan:
            <ul>
                <li><strong>Cetak Ringkasan (PDF):</strong> Mengunduh dokumen ringkasan pencapaian level dan skor peserta.</li>
                <li><strong>Cetak Detail per Pertanyaan:</strong> Membuka dokumen lembar kerja lengkap yang menampilkan seluruh daftar pertanyaan/fitur berurut kebawah beserta ceklis status jawaban.</li>
            </ul>
        </li>
        <li>Seluruh dokumen cetak detail secara otomatis dilengkapi **Nomor Halaman (Halaman X dari Y)** dan identitas instansi di bagian footer halaman A4.</li>
    </ul>

    <div class="footer">
        Dokumen Panduan Lembar Kerja PID &bull; BBPMP Provinsi Jawa Timur &bull; <?= date('Y') ?>
    </div>
    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>
</body>

</html>