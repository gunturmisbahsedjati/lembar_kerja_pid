<?php
require 'config.php';

$respondent_id = $_GET['respondent_id'] ?? null;
$current_level_order = (int)($_GET['level'] ?? 1);

if (!$respondent_id) {
    header("Location: index");
    exit;
}

// Ambil info Responden
$stmt = $pdo->prepare("SELECT * FROM respondents WHERE id = ?");
$stmt->execute([$respondent_id]);
$respondent = $stmt->fetch();

// Verifikasi Level Progression
if ($current_level_order > ($respondent['completed_level'] + 1)) {
    $allowed_level = $respondent['completed_level'] + 1;
    header("Location: play?respondent_id=$respondent_id&level=$allowed_level");
    exit;
}

// Ambil Data Level saat ini
$stmt = $pdo->prepare("SELECT * FROM levels WHERE level_order = ?");
$stmt->execute([$current_level_order]);
$level_data = $stmt->fetch();

if (!$level_data) {
    // Jika semua level selesai
    header("Location: leaderboard");
    exit;
}

// Ambil Pertanyaan/Fitur di Level ini
$stmt = $pdo->prepare("SELECT * FROM features WHERE level_id = ?");
$stmt->execute([$level_data['id']]);
$features = $stmt->fetchAll();

// Submit Jawaban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['status'] ?? [];
    $all_sudah = true;

    foreach ($features as $f) {
        $status = $answers[$f['id']] ?? 'Belum';

        // Simpan atau update jawaban
        $stmt_ans = $pdo->prepare("INSERT INTO answers (respondent_id, feature_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
        $stmt_ans->execute([$respondent_id, $f['id'], $status, $status]);

        if ($status !== 'Sudah') {
            $all_sudah = false;
        }
    }

    // Jika SEMUA fitur di level ini "Sudah", tingkatkan completed_level peserta
    if ($all_sudah && $respondent['completed_level'] < $current_level_order) {
        $stmt_up = $pdo->prepare("UPDATE respondents SET completed_level = ? WHERE id = ?");
        $stmt_up->execute([$current_level_order, $respondent_id]);
    }

    // Cek Level Selanjutnya
    $next_level = $current_level_order + 1;
    $stmt_next = $pdo->prepare("SELECT * FROM levels WHERE level_order = ?");
    $stmt_next->execute([$next_level]);

    if ($all_sudah && $stmt_next->fetch()) {
        header("Location: play?respondent_id=$respondent_id&level=$next_level");
    } else {
        header("Location: leaderboard");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($level_data['level_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-4">
    <div class="container" style="max-width: 800px;">
        <div class="card shadow border-0 p-4 mb-4">
            <h4 class="text-secondary mb-1">Peserta: <strong><?= htmlspecialchars($respondent['name']) ?></strong> (<?= htmlspecialchars($respondent['institution']) ?>)</h4>
            <h2 class="fw-bold text-primary"><?= htmlspecialchars($level_data['level_name']) ?></h2>
            <p class="text-muted">Pilih "Sudah" jika Anda telah menguasai/menggunakan fitur berikut. Anda harus menjawab **Sudah** pada semua poin di level ini untuk dapat lanjut ke level berikutnya.</p>
        </div>

        <form method="POST">
            <?php foreach ($features as $idx => $f): ?>
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold text-dark"><?= ($idx + 1) ?>. <?= htmlspecialchars($f['feature_name']) ?></h5>
                        <p class="mb-1"><strong>Praktik Penggunaan:</strong> <?= htmlspecialchars($f['usage_practice']) ?></p>
                        <p class="text-muted"><strong>Contoh Pemanfaatan:</strong> <?= htmlspecialchars($f['example_usage']) ?></p>

                        <div class="mt-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status[<?= $f['id'] ?>]" id="sudah_<?= $f['id'] ?>" value="Sudah" required>
                                <label class="form-check-label fw-bold text-success" for="sudah_<?= $f['id'] ?>">Sudah (√)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status[<?= $f['id'] ?>]" id="belum_<?= $f['id'] ?>" value="Belum" required>
                                <label class="form-check-label fw-bold text-danger" for="belum_<?= $f['id'] ?>">Belum</label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold my-4">Simpan & Lanjutkan</button>
        </form>
    </div>
</body>

</html>