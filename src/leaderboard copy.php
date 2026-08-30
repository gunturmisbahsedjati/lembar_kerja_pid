<?php
session_start();
require 'config.php';

// API Response untuk Chart AJAX Auto-Refresh
if (isset($_GET['api']) && isset($_GET['session_id'])) {
    header('Content-Type: application/json');
    $session_id = (int)$_GET['session_id'];

    // Cek instrumen sesi
    $stmt_s = $pdo->prepare("SELECT instrument_type FROM game_sessions WHERE id = ?");
    $stmt_s->execute([$session_id]);
    $session = $stmt_s->fetch();

    if (!$session) {
        echo json_encode([]);
        exit;
    }

    $instrument = $session['instrument_type'];

    // Ambil daftar level yang sesuai dengan instrumen sesi
    $stmt_l = $pdo->prepare("SELECT id, level_name FROM levels WHERE instrument_type = ? ORDER BY level_order ASC");
    $stmt_l->execute([$instrument]);
    $levels = $stmt_l->fetchAll(PDO::FETCH_ASSOC);

    $chart_data = [];
    foreach ($levels as $l) {
        $stmt_c = $pdo->prepare("
            SELECT COUNT(DISTINCT r.id) as total 
            FROM respondents r 
            JOIN answers a ON a.respondent_id = r.id 
            JOIN features f ON f.id = a.feature_id 
            WHERE r.session_id = ? AND f.level_id = ? AND a.status = 'Sudah'
        ");
        $stmt_c->execute([$session_id, $l['id']]);
        $res = $stmt_c->fetch();

        $chart_data[] = [
            'level_name' => $l['level_name'],
            'total' => (int)$res['total']
        ];
    }

    echo json_encode($chart_data);
    exit;
}

// Ambil Sesi yang Dipilih atau Sesi Aktif Terakhir
$session_id = (int)($_GET['session_id'] ?? 0);

if ($session_id === 0) {
    $active_session = $pdo->query("SELECT id FROM game_sessions WHERE status = 'active' ORDER BY created_at DESC LIMIT 1")->fetch();
    $session_id = $active_session ? $active_session['id'] : 0;
}

// Data Seluruh Sesi untuk Dropdown Filter
$all_sessions = $pdo->query("SELECT * FROM game_sessions ORDER BY created_at DESC")->fetchAll();

// Detail Sesi Aktif saat ini
$current_session = null;
if ($session_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $current_session = $stmt->fetch();
}

// Data Peserta / Responden
$respondents = [];
if ($session_id > 0) {
    $stmt_r = $pdo->prepare("
        SELECT r.*, 
               SUM(CASE WHEN a.status = 'Sudah' THEN 1 ELSE 0 END) as total_sudah
        FROM respondents r
        LEFT JOIN answers a ON a.respondent_id = r.id
        WHERE r.session_id = ?
        GROUP BY r.id
        ORDER BY r.completed_level DESC, total_sudah DESC, r.created_at ASC
    ");
    $stmt_r->execute([$session_id]);
    $respondents = $stmt_r->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Leaderboard & Progres Eksplorasi PID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light p-4">
    <div class="container" style="max-width: 1000px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-primary mb-0">Leaderboard & Progres Eksplorasi</h2>
            <a href="index" class="btn btn-outline-secondary">← Kembali ke Beranda</a>
        </div>

        <!-- Filter Pilih Sesi PIN Key -->
        <div class="card shadow-sm border-0 mb-4 p-3">
            <form method="GET" action="leaderboard" class="row align-items-center g-2">
                <div class="col-auto">
                    <label class="fw-bold">Pilih Sesi PIN Key:</label>
                </div>
                <div class="col-md-6">
                    <select name="session_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($all_sessions as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $session_id ? 'selected' : '' ?>>
                                PIN: <?= htmlspecialchars($s['pin']) ?> - <?= htmlspecialchars($s['session_name']) ?> (<?= str_replace('_', ' ', $s['instrument_type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($current_session): ?>
            <div class="card shadow-sm border-0 mb-4 p-4 text-center">
                <h4 class="fw-bold text-dark"><?= htmlspecialchars($current_session['session_name']) ?></h4>
                <p class="mb-0 text-muted">
                    Instrumen: <span class="badge bg-info text-dark fs-6"><?= str_replace('_', ' ', $current_session['instrument_type']) ?></span> |
                    Status: <span class="badge <?= $current_session['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= strtoupper($current_session['status']) ?></span>
                </p>
            </div>

            <!-- Chart Grafik Progres Per Level -->
            <div class="card shadow-sm border-0 p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3 text-center">Statistik Kelulusan Per Level</h5>
                <canvas id="progressChart" height="100"></canvas>
            </div>

            <!-- Tabel Peringkat Responden -->
            <div class="card shadow-sm border-0 p-4">
                <h5 class="fw-bold text-dark mb-3">Peringkat Peserta Teratas</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Peserta</th>
                                <th>Instansi</th>
                                <th>Level Selesai</th>
                                <th>Poin (Fitur Dikuasai)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($respondents)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada peserta di sesi ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($respondents as $idx => $r): ?>
                                    <tr>
                                        <td><strong><?= $idx + 1 ?></strong></td>
                                        <td><?= htmlspecialchars($r['name']) ?></td>
                                        <td><?= htmlspecialchars($r['institution']) ?></td>
                                        <td><span class="badge bg-success">Level <?= $r['completed_level'] ?></span></td>
                                        <td><strong><?= $r['total_sudah'] ?></strong> Fitur</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">Belum ada sesi PIN Key yang aktif atau dipilih.</div>
        <?php endif; ?>
    </div>

    <?php if ($session_id > 0): ?>
        <script>
            let chartInstance = null;

            function updateChart() {
                fetch(`leaderboard?api=1&session_id=<?= $session_id ?>`)
                    .then(res => res.json())
                    .then(data => {
                        const labels = data.map(d => d.level_name);
                        const totals = data.map(d => d.total);

                        if (chartInstance) {
                            chartInstance.data.labels = labels;
                            chartInstance.data.datasets[0].data = totals;
                            chartInstance.update();
                        } else {
                            const ctx = document.getElementById('progressChart').getContext('2d');
                            chartInstance = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Jumlah Peserta Menguasai Fitur',
                                        data: totals,
                                        backgroundColor: 'rgba(74, 0, 224, 0.7)',
                                        borderColor: 'rgba(74, 0, 224, 1)',
                                        borderWidth: 1,
                                        borderRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                precision: 0
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
            }

            // Muat Chart Pertama Kali & Auto Refresh Setiap 5 Detik
            updateChart();
            setInterval(updateChart, 5000);
        </script>
    <?php endif; ?>
</body>

</html>