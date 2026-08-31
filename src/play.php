<?php
require 'config.php';

$respondent_id = $_GET['respondent_id'] ?? null;
$current_level_order = (int)($_GET['level'] ?? 1);

if (!$respondent_id) {
    header("Location: index");
    exit;
}

// Ambil info Responden & Tipe Instrumen Sesi
$stmt = $pdo->prepare("
    SELECT r.*, gs.instrument_type 
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
    header("Location: leaderboard?session_id=" . $respondent['session_id']);
    exit;
}

// Ambil Pertanyaan di Level ini
$stmt = $pdo->prepare("SELECT * FROM features WHERE level_id = ?");
$stmt->execute([$level_data['id']]);
$features = $stmt->fetchAll();

// Submit Jawaban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['status'] ?? [];

    foreach ($features as $f) {
        $status = $answers[$f['id']] ?? 'Belum';

        $stmt_ans = $pdo->prepare("INSERT INTO answers (respondent_id, feature_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
        $stmt_ans->execute([$respondent_id, $f['id'], $status, $status]);
    }

    // Selalu update progress completed_level jika level saat ini lebih tinggi
    if ($respondent['completed_level'] < $current_level_order) {
        $stmt_up = $pdo->prepare("UPDATE respondents SET completed_level = ? WHERE id = ?");
        $stmt_up->execute([$current_level_order, $respondent_id]);
    }

    $next_level = $current_level_order + 1;
    $stmt_next = $pdo->prepare("SELECT * FROM levels WHERE instrument_type = ? AND level_order = ?");
    $stmt_next->execute([$respondent['instrument_type'], $next_level]);

    // Jika level berikutnya ada, langsung arahkan ke level berikutnya
    if ($stmt_next->fetch()) {
        header("Location: play?respondent_id=$respondent_id&level=$next_level");
    } else {
        header("Location: leaderboard?session_id=" . $respondent['session_id']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($level_data['level_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Pustaka SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">
    <style>
        body {
            background: linear-gradient(-45deg, #4a00e0, #8e2de2, #00c6ff, #0072ff);
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
    </style>
</head>

<body class="p-4">
    <div class="container" style="max-width: 800px;">
        <div class="card glass-card border-0 p-4 mb-4">
            <h5 class="text-secondary mb-1">Peserta: <strong><?= htmlspecialchars($respondent['name']) ?></strong> (<?= htmlspecialchars($respondent['institution']) ?>)</h5>
            <span class="badge bg-primary w-auto mb-2" style="width: fit-content;">Instrumen: <?= str_replace('_', ' ', $respondent['instrument_type']) ?></span>
            <h2 class="fw-bold text-primary"><?= htmlspecialchars($level_data['level_name']) ?></h2>
            <p class="text-muted mb-0">Pilih status penguasaan untuk setiap poin di level ini. Setelah semua terisi, klik tombol di bawah untuk melanjutkan ke level berikutnya.</p>
        </div>

        <form id="levelForm" method="POST">
            <?php foreach ($features as $idx => $f): ?>
                <div class="card glass-card border-0 mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold text-dark"><?= ($idx + 1) ?>. <?= htmlspecialchars($f['feature_name']) ?></h5>
                        <?php if ($f['usage_practice']): ?>
                            <p class="mb-1"><strong>Kategori / Praktik:</strong> <?= htmlspecialchars($f['usage_practice']) ?></p>
                        <?php endif; ?>
                        <?php if ($f['example_usage']): ?>
                            <p class="text-muted"><strong><?= $respondent['instrument_type'] == 'PAUD_SD_SMP_SLB_PKBM' ? 'Contoh : ' : 'Keterangan : ' ?></strong> <?= htmlspecialchars($f['example_usage']) ?></p>
                        <?php endif; ?>

                        <div class="mt-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status[<?= $f['id'] ?>]" id="sudah_<?= $f['id'] ?>" value="Sudah" required>
                                <label class="form-check-label fw-bold text-success" for="sudah_<?= $f['id'] ?>">Sudah (√)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status[<?= $f['id'] ?>]" id="belum_<?= $f['id'] ?>" value="Belum" required>
                                <label class="form-check-label fw-bold text-danger" for="belum_<?= $f['id'] ?>">Belum (✗)</label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="button" id="btnSimpan" class="btn btn-success btn-lg w-100 fw-bold my-4 shadow">Simpan & Lanjutkan</button>
        </form>
    </div>

    <script>
        document.getElementById('btnSimpan').addEventListener('click', function() {
            const form = document.getElementById('levelForm');

            // Cek kelengkapan pengisian radio button
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Pop-up Konfirmasi SweetAlert2
            Swal.fire({
                title: 'Konfirmasi Simpan',
                text: "Apakah Anda yakin ingin menyimpan jawaban dan melanjutkan ke level berikutnya?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
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