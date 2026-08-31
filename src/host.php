<?php
session_start();
require 'config.php';

if (!isset($_SESSION['host_logged_in'])) {
    header("Location: login");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login");
    exit;
}

$toast_status = null;
$toast_message = '';

// Buat PIN Key Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_pin'])) {
    $session_name = trim($_POST['session_name']);
    $instrument_type = trim($_POST['instrument_type']);
    $custom_pin = trim($_POST['custom_pin']);

    $pin = !empty($custom_pin) ? $custom_pin : str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Generate ID Unik String Uppercase untuk game_sessions
    $code2 = time() . '-' . uniqid();
    $session_id = strtoupper($code2);

    try {
        $stmt = $pdo->prepare("INSERT INTO game_sessions (id, pin, session_name, instrument_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$session_id, $pin, $session_name, $instrument_type]);
        header("Location: host?msg=created&pin=" . urlencode($pin));
        exit;
    } catch (PDOException $e) {
        header("Location: host?msg=error");
        exit;
    }
}

// Ubah Status Sesi
if (isset($_GET['toggle_id'])) {
    $toggle_id = trim($_GET['toggle_id']);
    $stmt = $pdo->prepare("UPDATE game_sessions SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$toggle_id]);
    header("Location: host?msg=toggled");
    exit;
}

// Hapus Sesi
if (isset($_GET['delete_id'])) {
    $delete_id = trim($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM game_sessions WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: host?msg=deleted");
    exit;
}

// Cek Notifikasi Pesan
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created' && isset($_GET['pin'])) {
        $toast_status = 'success';
        $toast_message = "PIN Key " . htmlspecialchars($_GET['pin']) . " berhasil dibuat!";
    } elseif ($_GET['msg'] === 'error') {
        $toast_status = 'error';
        $toast_message = "Gagal membuat PIN. PIN Key mungkin sudah digunakan.";
    } elseif ($_GET['msg'] === 'toggled') {
        $toast_status = 'success';
        $toast_message = "Status PIN berhasil diubah!";
    } elseif ($_GET['msg'] === 'deleted') {
        $toast_status = 'success';
        $toast_message = "PIN Key berhasil dihapus!";
    }
}

$sessions = $pdo->query("
    SELECT s.*, COUNT(r.id) as total_respondents 
    FROM game_sessions s 
    LEFT JOIN respondents r ON r.session_id = s.id 
    GROUP BY s.id 
    ORDER BY s.status ASC, s.created_at DESC
")->fetchAll();
$no = 1;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Host - Kelola PIN Key</title>
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS untuk Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.bootstrap5.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS & JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">

    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0;
        }

        div.dataTables_wrapper div.dataTables_filter {
            margin-bottom: 15px;
        }

        table.dataTable thead tr th {
            background-color: #212529 !important;
            color: #ffffff !important;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="#">Dashboard Host PID</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white">Halo, <strong><?= htmlspecialchars($_SESSION['host_name'] ?? 'Host') ?></strong></span>
            <a href="/" target="_blank" class="btn btn-success text-white btn-sm">Link Form</a>
            <a href="host?action=logout" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container" style="max-width: 1100px;">

        <!-- Tabel Daftar Sesi PIN dengan DataTables -->
        <div class="card shadow-sm border-0 p-4 mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">Daftar Sesi & PIN Key</h4>
                <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#createPinModal">
                    + Buat PIN Baru
                </button>
            </div>

            <div class="table-responsive">
                <table id="hostTable" class="table table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>PIN Key</th>
                            <th>Nama Sesi</th>
                            <th>Instrumen</th>
                            <th>Peserta</th>
                            <th>Status</th>
                            <th class="text-center" data-orderable="false">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge bg-warning text-dark fs-5 font-monospace"><?= htmlspecialchars($s['pin']) ?></span></td>
                                <td><?= htmlspecialchars($s['session_name']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= str_replace('_', ' ', $s['instrument_type']) ?></span></td>
                                <td><strong><?= $s['total_respondents'] ?></strong> Peserta</td>
                                <td>
                                    <span class="badge <?= $s['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= strtoupper($s['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="detail?session_id=<?= urlencode($s['id']) ?>" class="btn btn-primary">
                                            Detail
                                        </a>

                                        <a href="qrcode?pin=<?= urlencode($s['pin']) ?>" target="_blank" class="btn btn-dark">
                                            QR Code
                                        </a>

                                        <a href="host?toggle_id=<?= urlencode($s['id']) ?>" class="btn <?= $s['status'] === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                            <?= $s['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </a>
                                        <a href="leaderboard?session_id=<?= urlencode($s['id']) ?>" target="_blank" class="btn btn-info text-white">Leaderboard</a>

                                        <!-- Tombol Hapus dengan penanganan parameter string ID -->
                                        <button type="button" class="btn btn-danger" onclick="confirmDelete('<?= htmlspecialchars($s['id']) ?>', '<?= htmlspecialchars($s['pin']) ?>')">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form Buat PIN -->
    <div class="modal fade" id="createPinModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="createPinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="createPinModalLabel">Buat PIN Key Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Sesi / Kelas</label>
                            <input type="text" name="session_name" class="form-control" placeholder="Contoh: Eksplorasi PID Kelas A" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Instrumen</label>
                            <select name="instrument_type" class="form-select" required>
                                <option value="PAUD_SD_SMP_SLB_PKBM">PAUD, SD, SMP, SLB, PKBM</option>
                                <option value="SMA_SMK">SMA & SMK</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">PIN (Opsional)</label>
                            <input type="text" name="custom_pin" class="form-control" placeholder="Acak 6 digit jika dikosongkan">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="create_pin" class="btn btn-success fw-bold">Buat PIN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>
    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#hostTable').DataTable({
                fixedHeader: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                language: {
                    search: "Cari Data:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Data tidak ditemukan",
                    info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                    infoEmpty: "Tidak ada data tersedia",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Lanjut",
                        previous: "Kembali"
                    }
                }
            });
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        if (window.location.search.includes('msg=')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        <?php if ($toast_status && $toast_message): ?>
            Toast.fire({
                icon: '<?= $toast_status ?>',
                title: '<?= $toast_message ?>'
            });
        <?php endif; ?>

        // Parameter ID dikirim sebagai string ke JavaScript
        function confirmDelete(id, pin) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `PIN ${pin} dan seluruh data terkait akan dihapus secara permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `host?delete_id=${encodeURIComponent(id)}`;
                }
            });
        }
    </script>
</body>

</html>