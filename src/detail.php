<?php
session_start();
require 'config.php';

// Proteksi Halaman Host
if (!isset($_SESSION['host_logged_in'])) {
    header("Location: login");
    exit;
}

$session_id = (int)($_GET['session_id'] ?? 0);

// Ambil Informasi Sesi / PIN
$stmt_session = $pdo->prepare("SELECT * FROM game_sessions WHERE id = ?");
$stmt_session->execute([$session_id]);
$session = $stmt_session->fetch();

if (!$session) {
    header("Location: host");
    exit;
}

// Ambil Seluruh Responden pada Sesi Ini
$stmt_respondents = $pdo->prepare("
    SELECT r.*, 
           COUNT(a.id) as total_answers,
           SUM(CASE WHEN a.status = 'Sudah' THEN 1 ELSE 0 END) as total_sudah
    FROM respondents r
    LEFT JOIN answers a ON a.respondent_id = r.id
    WHERE r.session_id = ?
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt_respondents->execute([$session_id]);
$respondents = $stmt_respondents->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Responden - PIN <?= htmlspecialchars($session['pin']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="host">← Kembali ke Dashboard Host</a>
        <span class="text-white">Detail Responden</span>
    </nav>

    <div class="container" style="max-width: 1000px;">
        <!-- Header Ringkasan Sesi -->
        <div class="card shadow-sm border-0 mb-4 p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-primary mb-1"><?= htmlspecialchars($session['session_name']) ?></h3>
                    <p class="text-muted mb-0">
                        Instrumen: <strong><?= str_replace('_', ' ', $session['instrument_type']) ?></strong> |
                        Dibuat: <strong><?= date('d M Y H:i', strtotime($session['created_at'])) ?></strong>
                    </p>
                </div>
                <div>
                    <span class="badge bg-warning text-dark fs-3 font-monospace px-3 py-2">PIN: <?= htmlspecialchars($session['pin']) ?></span>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Responden -->
        <div class="card shadow-sm border-0 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">Daftar Peserta (<?= count($respondents) ?>)</h4>
                <a href="leaderboard?session_id=<?= $session['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm">Lihat Leaderboard</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Peserta</th>
                            <th>Instansi / Sekolah</th>
                            <th>Level Selesai</th>
                            <th>Fitur Dikuasai (Sudah)</th>
                            <th>Waktu Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($respondents)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada peserta yang bergabung pada sesi PIN ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($respondents as $idx => $r): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['institution']) ?></td>
                                    <td><span class="badge bg-success fs-6">Level <?= $r['completed_level'] ?></span></td>
                                    <td>
                                        <span class="badge bg-primary fs-6"><?= $r['total_sudah'] ?> Fitur</span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>