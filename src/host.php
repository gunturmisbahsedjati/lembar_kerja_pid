<?php
session_start();
require 'config.php';

if (!isset($_SESSION['host_logged_in'])) {
    header("Location: login");
    exit;
}

// Proses Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login");
    exit;
}

$message = '';

// 1. Buat PIN Key Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_pin'])) {
    $session_name = trim($_POST['session_name']);
    $custom_pin = trim($_POST['custom_pin']);

    if (!empty($custom_pin)) {
        $pin = $custom_pin;
    } else {
        $pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO game_sessions (pin, session_name) VALUES (?, ?)");
        $stmt->execute([$pin, $session_name]);

        // REDIRECT (PRG Pattern) agar tidak muncul resubmission saat refresh
        header("Location: host?msg=created&pin=" . urlencode($pin));
        exit;
    } catch (PDOException $e) {
        header("Location: host?msg=error");
        exit;
    }
}

// 2. Ubah Status Sesi (Aktif / Nonaktif)
if (isset($_GET['toggle_id'])) {
    $stmt = $pdo->prepare("UPDATE game_sessions SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$_GET['toggle_id']]);
    header("Location: host");
    exit;
}

// 3. FITUR BARU: Hapus Sesi / PIN Key
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM game_sessions WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: host?msg=deleted");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $message = "PIN Key dan seluruh data terkait berhasil dihapus!";
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created' && isset($_GET['pin'])) {
        $message = "PIN Key <strong>" . htmlspecialchars($_GET['pin']) . "</strong> berhasil dibuat!";
    } elseif ($_GET['msg'] === 'error') {
        $message = "Gagal membuat PIN. PIN Key mungkin sudah digunakan.";
    } elseif ($_GET['msg'] === 'deleted') {
        $message = "PIN Key dan seluruh data terkait berhasil dihapus!";
    }
}

// Ambil Seluruh Daftar Sesi PIN Key
$sessions = $pdo->query("
    SELECT s.*, COUNT(r.id) as total_respondents 
    FROM game_sessions s 
    LEFT JOIN respondents r ON r.session_id = s.id 
    GROUP BY s.id 
    ORDER BY s.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Host - Kelola PIN Key</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="#">Dashboard Host PID</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white">Halo, <strong><?= htmlspecialchars($_SESSION['host_name']) ?></strong></span>
            <a href="host?action=logout" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container" style="max-width: 950px;">
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show"><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Form Buat PIN -->
        <div class="card shadow-sm border-0 mb-4 p-4">
            <h4 class="fw-bold text-primary mb-3">Buat PIN Key Baru</h4>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Sesi / Sesi Kelas</label>
                    <input type="text" name="session_name" class="form-control" placeholder="Contoh: Eksplorasi PID Kelas A" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">PIN Key Kustom (Opsional)</label>
                    <input type="text" name="custom_pin" class="form-control" placeholder="Kosongkan utk acak 6 digit">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="create_pin" class="btn btn-success w-100 fw-bold">Buat PIN</button>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Sesi PIN -->
        <div class="card shadow-sm border-0 p-4">
            <h4 class="fw-bold text-dark mb-3">Daftar Sesi & PIN Key</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>PIN Key</th>
                            <th>Nama Sesi</th>
                            <th>Peserta</th>
                            <th>Status</th>
                            <th>Tanggal Buat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada PIN Key yang dibuat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><span class="badge bg-warning text-dark fs-5 font-monospace"><?= htmlspecialchars($s['pin']) ?></span></td>
                                    <td><?= htmlspecialchars($s['session_name']) ?></td>
                                    <td><strong><?= $s['total_respondents'] ?></strong> Peserta</td>
                                    <td>
                                        <span class="badge <?= $s['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= strtoupper($s['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($s['created_at'])) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="host?toggle_id=<?= $s['id'] ?>" class="btn <?= $s['status'] === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                                <?= $s['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </a>
                                            <a href="leaderboard?session_id=<?= $s['id'] ?>" class="btn btn-info text-white">Leaderboard</a>

                                            <!-- Tombol Hapus PIN -->
                                            <a href="host?delete_id=<?= $s['id'] ?>"
                                                class="btn btn-danger"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus PIN Key <?= htmlspecialchars($s['pin']) ?>? Semua data responden terkait PIN ini akan ikut terhapus!');">
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
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