<?php
require 'config.php';

$respondent_id = isset($_GET['respondent_id']) ? trim($_GET['respondent_id']) : null;
$current_level_order = (int)($_GET['level'] ?? 1);

if (!$respondent_id) {
    header("Location: /");
    exit;
}

// Ambil info Responden, Tipe Instrumen, serta Status Keaktifan Sesi (Hanya menggunakan gs.status)
$stmt = $pdo->prepare("
    SELECT r.*, gs.instrument_type, gs.status AS session_status 
    FROM respondents r 
    JOIN game_sessions gs ON gs.id = r.session_id 
    WHERE r.id = ?
");
$stmt->execute([$respondent_id]);
$respondent = $stmt->fetch();

// 1. Validasi Apakah Responden / Session ID Ditemukan
if (!$respondent) {
    header("Location: index?error=session_not_found");
    exit;
}

// 2. Validasi Apakah Session Aktif (Mengecek nilai status dari gs.status)
$is_session_active = false;

if (isset($respondent['session_status'])) {
    $status_lower = strtolower(trim($respondent['session_status']));
    // Mengizinkan status yang bernilai 'active', 'aktif', '1', atau 'open'
    if (in_array($status_lower, ['active', 'aktif', '1', 'open'])) {
        $is_session_active = true;
    }
}

if (!$is_session_active) {
    // Jika sesi tidak aktif / ditutup, arahkan kembali ke index
    header("Location: /?error=session_inactive");
    exit;
}

// Verifikasi Progress Level
if ($current_level_order > ($respondent['completed_level'] + 1)) {
    $allowed_level = $respondent['completed_level'] + 1;
    header("Location: play?respondent_id=$respondent_id&level=$allowed_level");
    exit;
}

// Ambil Data Level Berdasarkan Instrumen Sesi
$stmt = $pdo->prepare("SELECT * FROM levels WHERE instrument_type = ? AND level_order = ?");
$stmt->execute([$respondent['instrument_type'], $current_level_order]);
$level_data = $stmt->fetch();

if (!$level_data) {
    header("Location: result?respondent_id=" . $respondent_id);
    exit;
}

// Ambil Pertanyaan/Item di Level ini
$stmt = $pdo->prepare("SELECT * FROM features WHERE level_id = ? ORDER BY id ASC");
$stmt->execute([$level_data['id']]);
$features = $stmt->fetchAll();

$is_paud_sd = ($respondent['instrument_type'] === 'PAUD_SD_SMP_SLB_PKBM');

// Jika SMA/SMK, kelompokkan berdasarkan Sub-Kategori (usage_practice)
$grouped_features = [];
if (!$is_paud_sd) {
    $grouped_features = array_reduce($features, function ($acc, $item) {
        $category = !empty($item['usage_practice']) ? $item['usage_practice'] : 'Umum';
        $acc[$category][] = $item;
        return $acc;
    }, []);
}

// Submit Jawaban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['status'] ?? [];

    foreach ($features as $f) {
        $status = $answers[$f['id']] ?? 'Belum';

        $stmt_ans = $pdo->prepare("INSERT INTO answers (respondent_id, feature_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
        $stmt_ans->execute([$respondent_id, $f['id'], $status, $status]);
    }

    // Update progress completed_level jika level saat ini lebih tinggi
    if ($respondent['completed_level'] < $current_level_order) {
        $stmt_up = $pdo->prepare("UPDATE respondents SET completed_level = ? WHERE id = ?");
        $stmt_up->execute([$current_level_order, $respondent_id]);
    }

    $next_level = $current_level_order + 1;
    $stmt_next = $pdo->prepare("SELECT * FROM levels WHERE instrument_type = ? AND level_order = ?");
    $stmt_next->execute([$respondent['instrument_type'], $next_level]);

    if ($stmt_next->fetch()) {
        header("Location: play?respondent_id=$respondent_id&level=$next_level");
    } else {
        header("Location: result?respondent_id=" . $respondent_id);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
    <title><?= htmlspecialchars($level_data['level_name']) ?> - LEMBAR KERJA PESERTA PID</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Pustaka SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">

    <style>
        body {
            background: linear-gradient(-45deg, #1e3c72, #2a5298, #00c6ff, #0072ff);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }

        .subcat-header {
            background: linear-gradient(90deg, #1a252f 0%, #2c3e50 100%);
            color: #ffffff;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin-bottom: 0;
        }

        .table-custom thead tr th {
            background-color: #1a252f !important;
            color: #ffffff !important;
            padding: 12px 16px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table-custom tbody tr {
            transition: all 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.04);
        }

        .table-custom td {
            padding: 12px 16px;
            border-bottom: 1px solid #e9ecef;
        }

        /* Styling Pilihan Tombol Status Ceklis */
        .btn-check:checked+.btn-success-custom {
            background-color: #198754;
            color: #ffffff;
            border-color: #198754;
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
        }

        .btn-check:checked+.btn-danger-custom {
            background-color: #dc3545;
            color: #ffffff;
            border-color: #dc3545;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }

        .btn-status {
            font-weight: 600;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .instruction-box {
            background-color: #e8f4fd;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            padding: 10px 15px;
        }
    </style>
</head>

<body class="p-2 p-md-4">
    <div class="container" style="max-width: 1050px;">

        <!-- Header Lembar Kerja Peserta -->
        <div class="card glass-card border-0 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div>
                    <span class="badge bg-primary px-3 py-2 fs-6 mb-2">EKSPLORASI PEMANFAATAN FITUR PAPAN INTERAKTIF DIGITAL (PID)</span>
                    <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($level_data['level_name']) ?></h2>
                </div>
                <div class="text-end bg-light p-2 rounded border">
                    <small class="text-muted d-block">IDENTITAS PESERTA:</small>
                    <strong class="text-dark d-block"><?= htmlspecialchars($respondent['name']) ?></strong>
                    <small class="text-secondary"><?= htmlspecialchars($respondent['institution']) ?></small>
                </div>
            </div>

            <!-- Petunjuk Pengisian -->
            <div class="instruction-box mt-2">
                <small class="text-dark d-block">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i>
                    <strong>Petunjuk Pengisian:</strong> Pilih status <strong>Sudah (✓)</strong> jika Anda telah menguasai/menerapkan fitur tersebut. Pilih <strong>Belum (✗)</strong> jika belum.
                </small>
            </div>
        </div>

        <form id="levelForm" method="POST">
            <?php if ($is_paud_sd): ?>
                <!-- TAMPILAN TABEL PAUD_SD_SMP_SLB_PKBM -->
                <div class="card glass-card border-0 p-3 p-md-4 mb-4">
                    <div class="table-responsive rounded-3">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 5%;" class="text-center">No</th>
                                    <th style="width: 22%;">Fitur PID</th>
                                    <th style="width: 30%;">Praktik Penggunaan</th>
                                    <th style="width: 28%;">Contoh Pemanfaatan dalam Pembelajaran</th>
                                    <th style="width: 15%;" class="text-center">Status (√)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($features as $idx => $f): ?>
                                    <tr>
                                        <td class="text-center fw-bold align-middle text-secondary"><?= ($idx + 1) ?></td>
                                        <td class="fw-bold text-dark align-middle">
                                            <?= htmlspecialchars($f['feature_name']) ?>
                                        </td>
                                        <td class="text-secondary align-middle">
                                            <?= htmlspecialchars($f['usage_practice'] ?? '-') ?>
                                        </td>
                                        <td class="text-muted align-middle">
                                            <?= htmlspecialchars($f['example_usage'] ?? '-') ?>
                                        </td>
                                        <td class="text-center text-nowrap align-middle">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="status[<?= $f['id'] ?>]" id="sudah_<?= $f['id'] ?>" value="Sudah" required autocomplete="off">
                                                <label class="btn btn-outline-success btn-status btn-success-custom" for="sudah_<?= $f['id'] ?>">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Sudah
                                                </label>

                                                <input type="radio" class="btn-check" name="status[<?= $f['id'] ?>]" id="belum_<?= $f['id'] ?>" value="Belum" required autocomplete="off">
                                                <label class="btn btn-outline-danger btn-status btn-danger-custom" for="belum_<?= $f['id'] ?>">
                                                    <i class="bi bi-x-circle-fill me-1"></i> Belum
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- TAMPILAN TABEL SMA/SMK (Per Sub-Kategori) -->
                <?php foreach ($grouped_features as $sub_category => $items): ?>
                    <div class="card glass-card border-0 mb-4 overflow-hidden shadow-sm">
                        <div class="subcat-header">
                            <i class="bi bi-folder2-open"></i>
                            <span><?= htmlspecialchars($sub_category) ?></span>
                            <span class="badge bg-light text-dark ms-auto fs-7"><?= count($items) ?> Item</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 8%;" class="text-center">No</th>
                                        <th style="width: 62%;">Item Kemampuan / Fitur PID</th>
                                        <th style="width: 30%;" class="text-center">Status Ceklis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $idx => $f): ?>
                                        <tr>
                                            <td class="text-center fw-bold align-top pt-3 text-secondary"><?= ($idx + 1) ?></td>
                                            <td>
                                                <div class="fw-bold fs-6 text-dark mb-1">
                                                    <?= htmlspecialchars($f['feature_name']) ?>
                                                </div>
                                            </td>
                                            <td class="text-center text-nowrap align-middle">
                                                <div class="btn-group w-100" role="group">
                                                    <input type="radio" class="btn-check" name="status[<?= $f['id'] ?>]" id="sudah_<?= $f['id'] ?>" value="Sudah" required autocomplete="off">
                                                    <label class="btn btn-outline-success btn-status btn-success-custom" for="sudah_<?= $f['id'] ?>">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Sudah
                                                    </label>

                                                    <input type="radio" class="btn-check" name="status[<?= $f['id'] ?>]" id="belum_<?= $f['id'] ?>" value="Belum" required autocomplete="off">
                                                    <label class="btn btn-outline-danger btn-status btn-danger-custom" for="belum_<?= $f['id'] ?>">
                                                        <i class="bi bi-x-circle-fill me-1"></i> Belum
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Tombol Navigasi Simpan -->
            <button type="button" id="btnSimpan" class="btn btn-success btn-lg w-100 fw-bold py-3 mb-5 shadow-lg">
                <i class="bi bi-check2-all me-2"></i> Simpan & Lanjutkan
            </button>
        </form>
    </div>

    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>

    <!-- Script Konfirmasi SweetAlert2 -->
    <script>
        document.getElementById('btnSimpan').addEventListener('click', function() {
            const form = document.getElementById('levelForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Simpan',
                text: "Apakah Anda yakin semua status item sudah terisi dengan benar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Simpan & Lanjutkan',
                cancelButtonText: 'Periksa Kembali',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
</body>

</html>