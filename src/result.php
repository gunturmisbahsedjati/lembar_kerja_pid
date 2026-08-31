<?php
require 'config.php';
require 'function.php';

// PERBAIKAN: Mengambil respondent_id sebagai string (bukan (int))
$respondent_id = isset($_GET['respondent_id']) ? trim($_GET['respondent_id']) : null;

if (!$respondent_id) {
    header("Location: index");
    exit;
}

// Ambil Info Responden & Sesi
$stmt = $pdo->prepare("
    SELECT r.*, gs.session_name, gs.instrument_type 
    FROM respondents r 
    JOIN game_sessions gs ON gs.id = r.session_id 
    WHERE r.id = ?
");
$stmt->execute([$respondent_id]);
$respondent = $stmt->fetch();

if (!$respondent) {
    header("Location: index");
    exit;
}

// Ambil Persentase Jawaban per Level
$stmt_levels = $pdo->prepare("
    SELECT 
        l.id as level_id,
        l.level_name,
        l.level_order,
        COUNT(f.id) as total_features,
        SUM(CASE WHEN a.status = 'Sudah' THEN 1 ELSE 0 END) as total_sudah
    FROM levels l
    JOIN features f ON f.level_id = l.id
    LEFT JOIN answers a ON a.feature_id = f.id AND a.respondent_id = ?
    WHERE l.instrument_type = ?
    GROUP BY l.id
    ORDER BY l.level_order ASC
");
$stmt_levels->execute([$respondent_id, $respondent['instrument_type']]);
$level_results = $stmt_levels->fetchAll(PDO::FETCH_ASSOC);

// Hitung Ringkasan Total
$grand_total_features = 0;
$grand_total_sudah = 0;
foreach ($level_results as &$lvl) {
    $grand_total_features += $lvl['total_features'];
    $grand_total_sudah += $lvl['total_sudah'];
    $lvl['percentage'] = $lvl['total_features'] > 0
        ? round(($lvl['total_sudah'] / $lvl['total_features']) * 100)
        : 0;
}
unset($lvl);

$overall_percentage = $grand_total_features > 0
    ? round(($grand_total_sudah / $grand_total_features) * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
    <title>Hasil Evaluasi - <?= htmlspecialchars($respondent['name']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">

    <!-- html2pdf.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            background: linear-gradient(-45deg, #11998e, #38ef7d, #00c6ff, #0072ff);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            margin: 0;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .progress-bar-custom {
            transition: width 1s ease-in-out;
        }

        /* SEMBUNYIKAN ELEMEN KHUSUS PDF SECARA DEFAULT */
        .pdf-only {
            display: none !important;
        }

        /* STYLE KHUSUS DITERAPKAN SAAT PRINT/KONVERSI PDF */
        .pdf-render {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 10px !important;
        }

        /* TAMPILKAN ELEMEN KHUSUS PDF SAAT DALAM MODE CETAK PDF */
        .pdf-render .pdf-only {
            display: block !important;
        }

        .pdf-render .glass-card {
            background: #ffffff !important;
            backdrop-filter: none !important;
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 12px !important;
            margin-bottom: 20px !important;
        }

        .pdf-render .progress {
            border: 1px solid #ccc !important;
            background-color: #e9ecef !important;
        }

        .pdf-render .badge {
            border: 1px solid transparent;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="p-3 p-md-4">
    <div class="container" style="max-width: 850px;">
        <!-- Header Halaman Web -->
        <div class="card glass-card border-0 p-4 mb-4 text-center">
            <img src="logo.png" alt="Logo" class="mx-auto d-block mb-3" style="width: 80px;" onerror="this.style.display='none'">
            <h2 class="fw-bold text-success mt-2 ">Hasil Evaluasi Penguasaan PID</h2>
            <h4 class="fw-bold text-success mb-1">BBPMP Provinsi Jawa Timur</h4>
            <p class="text-muted">Terima kasih telah mengisi instrumen evaluasi ini!</p>
        </div>
        <!-- Area Konten Utama yang Akan Dicetak PDF -->
        <div id="printArea">
            <div class="d-flex align-items-center justify-content-center">
                <img src="logo.png" alt="Logo" width="75" height="75" class="me-3 pdf-only">
                <div class="text-center">
                    <h3 class="fw-bold mb-0 pdf-only">Hasil Evaluasi Penguasaan PID<br>BBPMP Provinsi Jawa Timur</h3>
                    <!-- <small class="text-muted">BBPMP Provinsi Jawa Timur</small> -->
                </div>
            </div>
            <!-- <img src="logo.png" alt="Logo" class="pdf-only mx-auto d-block" style="width: 50px;" onerror="this.style.display='none'"> -->
            <!-- <h5 class="fw-bold pdf-only text-center">Hasil Evaluasi Penguasaan PID<br>BBPMP Provinsi Jawa Timur</h5> -->
            <!-- Card Header / Profil -->
            <div class="card glass-card border-0 p-4 mb-4 text-center">
                <!-- JUDUL INI HANYA MUNCUL DI CETAKAN PDF -->
                <div class="p-3 bg-light rounded-3 text-start mb-2 border">

                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Nama Peserta</small>
                            <strong><?= htmlspecialchars($respondent['name']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Nama Sesi</small>
                            <strong><?= htmlspecialchars($respondent['session_name']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Instansi / Sekolah</small>
                            <strong><?= htmlspecialchars($respondent['institution']) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Instrumen</small>
                            <span class="badge bg-primary"><?= str_replace('_', ' ', $respondent['instrument_type']) ?></span>
                        </div>
                        <div class="col-6"></div>
                        <div class="col-6">
                            <small class="text-muted d-block">Tanggal Pengisian</small>
                            <span class="badge bg-secondary"><?= Indonesia2Tgl($respondent['created_at']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Capaian Keseluruhan -->
                <div class="card bg-success text-white border-0 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total Penguasaan Keseluruhan</span>
                        <span class="display-6 fw-bold"><?= $overall_percentage ?>%</span>
                    </div>
                    <small class="text-white-50 text-start">Menguasai <?= $grand_total_sudah ?> dari <?= $grand_total_features ?> fitur/pertanyaan</small>
                </div>
            </div>

            <!-- Daftar Persentase per Level -->
            <div class="card glass-card border-0 p-4 mb-4">
                <h4 class="fw-bold text-dark mb-2">Rincian Persentase per Level</h4>

                <div class="d-flex flex-column gap-2">
                    <?php foreach ($level_results as $lvl):
                        $color = $lvl['percentage'] >= 75 ? 'bg-success' : ($lvl['percentage'] >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                    ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold text-dark mb-0">
                                    <?= htmlspecialchars($lvl['level_name']) ?>
                                </h6>
                                <span class="badge <?= $color ?> fs-6 font-monospace">
                                    <?= $lvl['percentage'] ?>%
                                </span>
                            </div>
                            <div class="text-muted small mb-2">
                                Menguasai <strong><?= $lvl['total_sudah'] ?></strong> dari <strong><?= $lvl['total_features'] ?></strong> fitur
                            </div>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar <?= $color ?>"
                                    role="progressbar"
                                    style="width: <?= $lvl['percentage'] ?>%;"
                                    aria-valuenow="<?= $lvl['percentage'] ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?= $lvl['percentage'] ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="text-center mb-4 no-print d-flex justify-content-center gap-2 flex-wrap">
            <button id="pdfBtn" onclick="downloadPDF()" class="btn btn-danger btn-lg fw-bold shadow">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Cetak PDF
            </button>
            <a href="index" class="btn btn-light btn-lg fw-bold shadow">
                🏠 Selesai & Kembali
            </a>
        </div>

    </div>

    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>

    <script>
        function downloadPDF() {
            const element = document.getElementById('printArea');
            const btn = document.getElementById('pdfBtn');

            // Feedback visual pada tombol
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

            // Tambahkan class khusus render PDF untuk menghilangkan backdrop-blur & menampilkan elemen .pdf-only
            element.classList.add('pdf-render');

            const opt = {
                margin: [0.1, 0.3, 0.2, 0.3], // margin atas, kanan, bawah, kiri dalam inci
                filename: 'Hasil_Evaluasi_<?= preg_replace('/[^a-zA-Z0-9_]/', '_', $respondent['name']) ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    scrollY: 0,
                    logging: false
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                // Kembalikan tampilan halaman ke semula setelah konversi selesai
                element.classList.remove('pdf-render');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-file-earmark-pdf-fill me-1"></i> Cetak PDF';
            }).catch(err => {
                console.error(err);
                element.classList.remove('pdf-render');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-file-earmark-pdf-fill me-1"></i> Cetak PDF';
            });
        }
    </script>
</body>

</html>