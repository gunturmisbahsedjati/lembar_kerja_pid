<?php
session_start();
require 'config.php';

// Ambil Sesi yang Dipilih atau Sesi Aktif Terakhir secara Otomatis (Mendukung VARCHAR)
$session_id = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

if (empty($session_id)) {
    $active_session = $pdo->query("SELECT id FROM game_sessions WHERE status = 'active' ORDER BY created_at DESC LIMIT 1")->fetch();
    $session_id = $active_session ? (string)$active_session['id'] : '';
}

// Detail Sesi Aktif
$current_session = null;
if (!empty($session_id)) {
    $stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $current_session = $stmt->fetch();
}

// API Response untuk Data Grafik via AJAX Auto-Refresh
if (isset($_GET['api']) && !empty($session_id)) {
    header('Content-Type: application/json');

    if (!$current_session) {
        echo json_encode(['main_chart' => [], 'level_charts' => []]);
        exit;
    }

    $instrument = $current_session['instrument_type'];

    // Ambil daftar level yang sesuai dengan instrumen sesi
    $stmt_l = $pdo->prepare("SELECT id, level_name FROM levels WHERE instrument_type = ? ORDER BY level_order ASC");
    $stmt_l->execute([$instrument]);
    $levels = $stmt_l->fetchAll(PDO::FETCH_ASSOC);

    $main_chart = [];
    $level_charts = [];

    foreach ($levels as $level) {
        // 1. Data Grafik Utama (Jumlah Responden Unik yang Memiliki Jawaban 'Sudah' di Level Ini)
        $stmt_c = $pdo->prepare("
            SELECT COUNT(DISTINCT r.id) as total 
            FROM respondents r 
            JOIN answers a ON a.respondent_id = r.id 
            JOIN features f ON f.id = a.feature_id 
            WHERE r.session_id = ? AND f.level_id = ? AND a.status = 'Sudah'
        ");
        $stmt_c->execute([$session_id, $level['id']]);
        $res = $stmt_c->fetch();

        $main_chart[] = [
            'level_name' => $level['level_name'],
            'total' => (int)($res['total'] ?? 0)
        ];

        // 2. Data Grafik Per Level (Sumbu X: Pertanyaan/Fitur, Sumbu Y: Jumlah Responden 'Sudah')
        $stmt_f = $pdo->prepare("
            SELECT 
                f.feature_name,
                COUNT(CASE WHEN a.status = 'Sudah' THEN 1 END) as total_sudah
            FROM features f
            LEFT JOIN answers a ON a.feature_id = f.id AND a.respondent_id IN (
                SELECT id FROM respondents WHERE session_id = ?
            )
            WHERE f.level_id = ?
            GROUP BY f.id, f.feature_name
            ORDER BY f.id ASC
        ");
        $stmt_f->execute([$session_id, $level['id']]);
        $features = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

        $level_charts[] = [
            'level_id' => $level['id'],
            'level_name' => $level['level_name'],
            'features' => array_column($features, 'feature_name'),
            'totals' => array_map('intval', array_column($features, 'total_sudah'))
        ];
    }

    echo json_encode([
        'main_chart' => $main_chart,
        'level_charts' => $level_charts
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Progres Eksplorasi PID</title>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
    <link rel="apple-touch-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Pustaka Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card-custom {
            border-radius: 14px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="p-3 p-md-4">
    <div class="container-fluid" style="max-width: 1200px;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <!-- Pembungkus Logo + Teks Judul -->
            <div class="d-flex align-items-center">
                <img src="logo.png" alt="Logo" width="48" height="48" class="me-3">
                <div>
                    <h2 class="fw-bold text-primary mb-0">Statistik Eksplorasi PID</h2>
                    <small class="text-muted">BBPMP Provinsi Jawa Timur</small>
                </div>
            </div>

            <!-- Tombol Navigasi -->
            <!-- <a href="/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Lembar Kerja PID
            </a> -->
        </div>

        <?php if ($current_session): ?>
            <!-- Header Informasi Sesi -->
            <div class="card card-custom p-4 mb-4 text-center bg-white">
                <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($current_session['session_name']) ?></h4>
                <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                    <span class="badge bg-warning text-dark font-monospace fs-6 px-3 py-2">
                        <i class="bi bi-key-fill me-1"></i> PIN: <?= htmlspecialchars($current_session['pin']) ?>
                    </span>
                    <span class="badge bg-info text-dark fs-6 px-3 py-2">
                        <i class="bi bi-journal-text me-1"></i> <?= str_replace('_', ' ', $current_session['instrument_type']) ?>
                    </span>
                </div>
            </div>

            <!-- GRAFIK UTAMA: Grafik Batang & Grafik Pie -->
            <h4 class="fw-bold text-dark mb-3">Grafik Penguasaan Level</h4>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card card-custom p-4 h-100 bg-white">
                        <!-- <h5 class="fw-bold text-dark mb-3 text-center">Grafik Utama (Batang)</h5> -->
                        <div style="position: relative; width: 100%; height: 280px;">
                            <canvas id="mainBarChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-custom p-4 h-100 bg-white">
                        <!-- <h5 class="fw-bold text-dark mb-3 text-center">Proporsi Penguasaan Level (Pie)</h5> -->
                        <div style="position: relative; width: 100%; height: 280px;">
                            <canvas id="mainPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4 text-muted">
            <h4 class="fw-bold text-dark mb-3">Detail Statistik Per Level & Pertanyaan</h4>

            <!-- Grid Layout 2 Kolom untuk Grafik Detail -->
            <div id="chartsContainer" class="row g-4 mb-5">
                <div class="col-12 text-center py-5 text-muted">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div>Memuat grafik detail per level...</div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center py-4 rounded-3 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                Belum ada sesi PIN Key yang aktif atau dipilih.
            </div>
        <?php endif; ?>
    </div>
    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>
    <?php if (!empty($session_id)): ?>
        <script>
            let mainBarChartInstance = null;
            let mainPieChartInstance = null;
            const levelChartInstances = {};

            // Palette Warna Konsisten
            const chartColors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
            ];

            function updateAllCharts() {
                const sessionId = encodeURIComponent("<?= htmlspecialchars($session_id, ENT_QUOTES) ?>");
                fetch(`${window.location.pathname}?api=1&session_id=${sessionId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.main_chart || !data.level_charts) return;

                        const mainLabels = data.main_chart.map(d => d.level_name);
                        const mainTotals = data.main_chart.map(d => d.total);
                        const colors = chartColors.slice(0, mainLabels.length);

                        // 1A. UPDATE GRAFIK UTAMA (BATANG)
                        if (mainBarChartInstance) {
                            mainBarChartInstance.data.labels = mainLabels;
                            mainBarChartInstance.data.datasets[0].data = mainTotals;
                            mainBarChartInstance.update();
                        } else {
                            const ctxBar = document.getElementById('mainBarChart').getContext('2d');
                            mainBarChartInstance = new Chart(ctxBar, {
                                type: 'bar',
                                data: {
                                    labels: mainLabels,
                                    datasets: [{
                                        label: 'Jumlah Responden Menguasai',
                                        data: mainTotals,
                                        backgroundColor: colors,
                                        borderRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Level / Kategori',
                                                font: {
                                                    weight: 'bold'
                                                }
                                            },
                                            ticks: {
                                                callback: function(val) {
                                                    let text = this.getLabelForValue(val);
                                                    return text.length > 18 ? text.substr(0, 16) + '...' : text;
                                                }
                                            }
                                        },
                                        y: {
                                            beginAtZero: true,
                                            title: {
                                                display: true,
                                                text: 'Jumlah Responden',
                                                font: {
                                                    weight: 'bold'
                                                }
                                            },
                                            ticks: {
                                                precision: 0
                                            }
                                        }
                                    }
                                }
                            });
                        }

                        // 1B. UPDATE GRAFIK UTAMA (PIE)
                        if (mainPieChartInstance) {
                            mainPieChartInstance.data.labels = mainLabels;
                            mainPieChartInstance.data.datasets[0].data = mainTotals;
                            mainPieChartInstance.update();
                        } else {
                            const ctxPie = document.getElementById('mainPieChart').getContext('2d');
                            mainPieChartInstance = new Chart(ctxPie, {
                                type: 'pie',
                                data: {
                                    labels: mainLabels,
                                    datasets: [{
                                        data: mainTotals,
                                        backgroundColor: colors
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    }
                                }
                            });
                        }

                        // 2. UPDATE GRAFIK DETAIL PER LEVEL
                        const container = document.getElementById('chartsContainer');

                        data.level_charts.forEach((levelData, index) => {
                            const canvasId = `chart_level_${levelData.level_id}`;
                            // Ambil warna yang selaras dengan grafik utama berdasarkan index level
                            const currentColor = chartColors[index % chartColors.length];

                            if (!document.getElementById(canvasId)) {
                                if (index === 0) container.innerHTML = '';

                                const cardHTML = `
                                <div class="col-md-6">
                                    <div class="card card-custom p-4 h-100 bg-white">
                                        <h5 class="fw-bold text-dark mb-3">${levelData.level_name}</h5>
                                        <div style="position: relative; width: 100%; height: 260px;">
                                            <canvas id="${canvasId}"></canvas>
                                        </div>
                                    </div>
                                </div>
                            `;
                                container.insertAdjacentHTML('beforeend', cardHTML);
                            }

                            const ctxLevel = document.getElementById(canvasId).getContext('2d');

                            if (levelChartInstances[canvasId]) {
                                levelChartInstances[canvasId].data.labels = levelData.features;
                                levelChartInstances[canvasId].data.datasets[0].data = levelData.totals;
                                levelChartInstances[canvasId].data.datasets[0].backgroundColor = currentColor;
                                levelChartInstances[canvasId].data.datasets[0].borderColor = currentColor;
                                levelChartInstances[canvasId].update();
                            } else {
                                levelChartInstances[canvasId] = new Chart(ctxLevel, {
                                    type: 'bar',
                                    data: {
                                        labels: levelData.features,
                                        datasets: [{
                                            label: 'Jumlah Responden Menguasai (Sudah)',
                                            data: levelData.totals,
                                            backgroundColor: currentColor,
                                            borderColor: currentColor,
                                            borderWidth: 1,
                                            borderRadius: 6
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            x: {
                                                title: {
                                                    display: true,
                                                    text: 'Item / Fitur PID',
                                                    font: {
                                                        weight: 'bold'
                                                    }
                                                },
                                                ticks: {
                                                    callback: function(val) {
                                                        let text = this.getLabelForValue(val);
                                                        return text.length > 18 ? text.substr(0, 16) + '...' : text;
                                                    }
                                                }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Jumlah Responden',
                                                    font: {
                                                        weight: 'bold'
                                                    }
                                                },
                                                ticks: {
                                                    precision: 0
                                                }
                                            }
                                        },
                                        plugins: {
                                            tooltip: {
                                                callbacks: {
                                                    title: function(context) {
                                                        return context[0].label;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        });
                    })
                    .catch(err => console.error("Gagal memuat statistik grafik:", err));
            }

            document.addEventListener('DOMContentLoaded', () => {
                updateAllCharts();
                setInterval(updateAllCharts, 5000);
            });
        </script>
    <?php endif; ?>
</body>

</html>