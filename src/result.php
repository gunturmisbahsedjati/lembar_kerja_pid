<?php
require 'config.php';

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
    <title>Hasil Evaluasi - <?= htmlspecialchars($respondent['name']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">

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
    </style>
</head>

<body class="p-3 p-md-4">
    <div class="container" style="max-width: 850px;">

        <!-- Card Header / Profil -->
        <div class="card glass-card border-0 p-4 mb-4 text-center">
            <span class="fs-1">🎉</span>
            <h2 class="fw-bold text-success mt-2 mb-1">Hasil Evaluasi Penguasaan</h2>
            <p class="text-muted mb-3">Terima kasih telah mengisi instrumen evaluasi ini!</p>

            <div class="p-3 bg-light rounded-3 text-start mb-3">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nama Peserta</small>
                        <strong><?= htmlspecialchars($respondent['name']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Instansi / Sekolah</small>
                        <strong><?= htmlspecialchars($respondent['institution']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nama Sesi</small>
                        <strong><?= htmlspecialchars($respondent['session_name']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Instrumen</small>
                        <span class="badge bg-primary"><?= str_replace('_', ' ', $respondent['instrument_type']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Capaian Keseluruhan -->
            <div class="card bg-success text-white border-0 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Total Penguasaan Keseluruhan</span>
                    <span class="display-6 fw-bold"><?= $overall_percentage ?>%</span>
                </div>
                <small class="text-white-50 text-start mt-1">Menguasai <?= $grand_total_sudah ?> dari <?= $grand_total_features ?> fitur/pertanyaan</small>
            </div>
        </div>

        <!-- Daftar Persentase per Level -->
        <div class="card glass-card border-0 p-4 mb-4">
            <h4 class="fw-bold text-dark mb-4">Rincian Persentase per Level</h4>

            <div class="d-flex flex-column gap-4">
                <?php foreach ($level_results as $lvl):
                    $color = $lvl['percentage'] >= 75 ? 'bg-success' : ($lvl['percentage'] >= 50 ? 'bg-warning' : 'bg-danger');
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
                            <div class="progress-bar progress-bar-striped progress-bar-animated <?= $color ?>"
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

        <!-- Tombol Aksi -->
        <div class="text-center mb-4">
            <a href="index" class="btn btn-light btn-lg fw-bold shadow">
                🏠 Selesai & Kembali
            </a>
        </div>

    </div>
</body>

</html>