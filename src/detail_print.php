<?php
require 'config.php';
require 'function.php';

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

// Ambil Seluruh Level dan Feature/Pertanyaan beserta Status Jawaban
$stmt_details = $pdo->prepare("
    SELECT 
        l.id AS level_id,
        l.level_name,
        l.level_order,
        f.id AS feature_id,
        f.feature_name,
        a.status
    FROM levels l
    JOIN features f ON f.level_id = l.id
    LEFT JOIN answers a ON a.feature_id = f.id AND a.respondent_id = ?
    WHERE l.instrument_type = ?
    ORDER BY l.level_order ASC, f.id ASC
");
$stmt_details->execute([$respondent_id, $respondent['instrument_type']]);
$raw_details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan Data Berdasarkan Level
$grouped_data = [];
foreach ($raw_details as $row) {
    $lvl_name = $row['level_name'];
    $grouped_data[$lvl_name][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lembar Ceklis SAMR - <?= htmlspecialchars($respondent['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: -20px;
        }

        .table-ceklis {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table-ceklis th,
        .table-ceklis td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .bg-level-header {
            background-color: #d9e2f3 !important;
            font-weight: bold;
        }

        .check-box-cell {
            text-align: center;
            width: 80px;
            font-size: 11pt;
            font-weight: bold;
        }

        /* NOMOR HALAMAN KHUSUS CETAK */
        @media print {
            .no-print {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 1cm 1cm 1.5cm 1cm;

                /* Menampilkan nomor halaman di pojok kanan bawah */
                @bottom-right {
                    content: "Halaman " counter(page) " dari " counter(pages);
                    font-family: Arial, sans-serif;
                    font-size: 9pt;
                    color: #555;
                }

                /* Menampilkan footer identitas di pojok kiri bawah */
                @bottom-left {
                    content: "BBPMP Provinsi Jawa Timur";
                    font-family: Arial, sans-serif;
                    font-size: 9pt;
                    color: #555;
                }
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body class="p-4">

    <!-- Tombol Aksi di Luar Cetak -->
    <div class="no-print text-end mb-3">
        <button onclick="window.print()" class="btn btn-primary fw-bold me-1">
            <i class="bi bi-printer me-1"></i> Cetak Dokumen
        </button>
        <button onclick="window.close()" class="btn btn-secondary fw-bold">
            Tutup
        </button>
    </div>

    <div class="container-fluid p-0">
        <!-- Header Dokumen SAMR -->
        <div class="header-title mb-3">
            <h5 class="fw-bold mb-0">LEMBAR KERJA PESERTA</h5>
            <h5 class="fw-bold">EKSPLORASI PEMANFAATAN FITUR PAPAN INTERAKTIF DIGITAL</h5>
        </div>

        <!-- Identitas Peserta -->
        <table class="mb-3" style="border: 0px;">
            <tr>
                <td><strong>Nama</strong></td>
                <td style="padding-left: 0.5em;padding-right: 0.5em;">:</td>
                <td><?= htmlspecialchars($respondent['name']) ?></td>
            </tr>
            <tr>
                <td><strong>Instansi/Sekolah</strong></td>
                <td style="padding-left: 0.5em;padding-right: 0.5em;">:</td>
                <td><?= htmlspecialchars($respondent['institution']) ?></td>
            </tr>
            <tr>
                <td><strong>Instrumen</strong></td>
                <td style="padding-left: 0.5em;padding-right: 0.5em;">:</td>
                <td><?= htmlspecialchars(str_replace('_', ' ', $respondent['instrument_type'])) ?></td>
            </tr>
            <tr>
                <td><strong>Kelompok / Sesi</strong></td>
                <td style="padding-left: 0.5em;padding-right: 0.5em;">:</td>
                <td><?= htmlspecialchars($respondent['session_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Waktu Pengisian</strong></td>
                <td style="padding-left: 0.5em;padding-right: 0.5em;">:</td>
                <td><?= Indonesia2Tgl($respondent['created_at']) ?></td>
            </tr>
        </table>

        <!-- Loop Per Level -->
        <?php foreach ($grouped_data as $level_name => $features): ?>
            <table class="table-ceklis">
                <thead>
                    <tr class="bg-level-header">
                        <th colspan="3" style="border: 0px;" class="text-start fs-6 p-2"><?= htmlspecialchars($level_name) ?></th>
                    </tr>
                    <tr class="bg-light text-center fw-bold">
                        <th width="5%">No</th>
                        <th width="80%">Fitur / Item Evaluasi</th>
                        <th width="15%">Ceklis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($features as $index => $item): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($item['feature_name']) ?></td>
                            <td class="check-box-cell">
                                <?= ($item['status'] === 'Sudah') ? '✓' : '' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>