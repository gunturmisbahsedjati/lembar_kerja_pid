<?php
require 'config.php';

$session_id = $_GET['session_id'] ?? null;

if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    $sql = "
        SELECT 
            l.level_name, 
            COUNT(r.id) AS total_respondents
        FROM levels l
        LEFT JOIN respondents r ON r.completed_level >= l.level_order
    ";

    if ($session_id) {
        $sql .= " AND r.session_id = " . (int)$session_id;
    }

    $sql .= " GROUP BY l.id, l.level_name, l.level_order ORDER BY l.level_order ASC";

    $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];

    foreach ($data as $row) {
        $labels[] = $row['level_name'];
        $values[] = (int)$row['total_respondents'];
    }

    echo json_encode(['labels' => $labels, 'values' => $values]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Leaderboard - Grafik Batang Progress Level PID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-dark text-white p-4">
    <div class="container" style="max-width: 900px;">
        <h2 class="text-center fw-bold text-warning mb-2">Papan Peringkat Progress Peserta</h2>
        <p class="text-center text-light mb-4">Jumlah Responden yang Berhasil Menyelesaikan Setiap Level</p>

        <div class="card bg-white p-4 shadow-lg rounded">
            <canvas id="levelChart" width="400" height="200"></canvas>
        </div>

        <div class="text-center mt-4">
            <a href="index" class="btn btn-outline-light btn-lg">Kembali ke Beranda</a>
        </div>
    </div>

    <script>
        let barChart = null;

        function renderChart() {
            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get('session_id') || '';

            fetch(`leaderboard?api=1&session_id=${sessionId}`)
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('levelChart').getContext('2d');
                    if (barChart) barChart.destroy();

                    barChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Jumlah Responden Selesai',
                                data: data.values,
                                backgroundColor: [
                                    'rgba(13, 110, 253, 0.7)',
                                    'rgba(25, 135, 84, 0.7)',
                                    'rgba(255, 193, 7, 0.7)',
                                    'rgba(220, 53, 69, 0.7)'
                                ],
                                borderColor: [
                                    'rgb(13, 110, 253)',
                                    'rgb(25, 135, 84)',
                                    'rgb(255, 193, 7)',
                                    'rgb(220, 53, 69)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Tingkat / Level (Sumbu X)',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Jumlah Responden (Sumbu Y)',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
        }

        setInterval(renderChart, 3000);
        renderChart();
    </script>
</body>

</html>