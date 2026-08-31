<?php
session_start();
require 'config.php';

// Proteksi Halaman Host
if (!isset($_SESSION['host_logged_in'])) {
    header("Location: login");
    exit;
}

// PERBAIKAN 1: Ambil session_id sebagai STRING (hapus casting (int))
$session_id = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

if (empty($session_id)) {
    header("Location: host");
    exit;
}

// Ambil Informasi Sesi / PIN
$stmt_session = $pdo->prepare("SELECT * FROM game_sessions WHERE id = ?");
$stmt_session->execute([$session_id]);
$session = $stmt_session->fetch();

if (!$session) {
    header("Location: host");
    exit;
}

$toast_status = null;
$toast_message = '';

// ==========================================
// FITUR EXPORT EXCEL
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    // Ambil semua daftar pertanyaan/fitur sesuai instrumen sesi
    $stmt_features = $pdo->prepare("
        SELECT f.id, f.feature_name, l.level_name 
        FROM features f
        JOIN levels l ON l.id = f.level_id
        WHERE l.instrument_type = ?
        ORDER BY l.level_order ASC, f.id ASC
    ");
    $stmt_features->execute([$session['instrument_type']]);
    $features = $stmt_features->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua peserta di sesi ini
    $stmt_resp = $pdo->prepare("
        SELECT id, name, institution, completed_level, created_at 
        FROM respondents 
        WHERE session_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt_resp->execute([$session_id]);
    $respondents_data = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);

    // Ambil seluruh jawaban peserta
    $stmt_all_ans = $pdo->prepare("
        SELECT a.respondent_id, a.feature_id, a.status 
        FROM answers a
        JOIN respondents r ON r.id = a.respondent_id
        WHERE r.session_id = ?
    ");
    $stmt_all_ans->execute([$session_id]);
    $raw_answers = $stmt_all_ans->fetchAll(PDO::FETCH_ASSOC);

    // Mapping jawaban [respondent_id][feature_id] = status
    $answers_map = [];
    foreach ($raw_answers as $ans) {
        $answers_map[$ans['respondent_id']][$ans['feature_id']] = $ans['status'];
    }

    // Set Header untuk Download File Excel (.xls)
    $filename = "Data_Peserta_PIN_" . $session['pin'] . "_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr style='background-color:#0d6efd; color:#ffffff; font-weight:bold;'>";
    echo "<th>No</th>";
    echo "<th>Nama Peserta</th>";
    echo "<th>Instansi / Sekolah</th>";
    echo "<th>Level Selesai</th>";
    echo "<th>Waktu Bergabung</th>";

    // Header Kolom Pertanyaan
    foreach ($features as $f) {
        echo "<th>[" . htmlspecialchars($f['level_name']) . "] " . htmlspecialchars($f['feature_name']) . "</th>";
    }
    echo "</tr>";

    // Baris Data Peserta & Jawabannya
    foreach ($respondents_data as $idx => $resp) {
        echo "<tr>";
        echo "<td>" . ($idx + 1) . "</td>";
        echo "<td>" . htmlspecialchars($resp['name']) . "</td>";
        echo "<td>" . htmlspecialchars($resp['institution']) . "</td>";
        echo "<td>Level " . $resp['completed_level'] . "</td>";
        echo "<td>" . date('d M Y H:i', strtotime($resp['created_at'])) . "</td>";

        foreach ($features as $f) {
            $status = $answers_map[$resp['id']][$f['id']] ?? 'Belum';
            $bgColor = ($status === 'Sudah') ? '#d1e7dd' : '#f8d7da';
            echo "<td style='background-color:$bgColor; text-align:center;'>" . $status . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// PERBAIKAN 2: Proses Hapus Peserta & Jawabannya (Gunakan String ID)
if (isset($_GET['action']) && $_GET['action'] === 'delete_respondent') {
    $respondent_id = isset($_GET['respondent_id']) ? trim($_GET['respondent_id']) : '';

    if (!empty($respondent_id)) {
        $pdo->beginTransaction();
        try {
            // Hapus jawaban peserta
            $stmt_del_ans = $pdo->prepare("DELETE FROM answers WHERE respondent_id = ?");
            $stmt_del_ans->execute([$respondent_id]);

            // Hapus peserta
            $stmt_del_resp = $pdo->prepare("DELETE FROM respondents WHERE id = ? AND session_id = ?");
            $stmt_del_resp->execute([$respondent_id, $session_id]);

            $pdo->commit();
            header("Location: detail?session_id=" . urlencode($session_id) . "&msg=deleted");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: detail?session_id=" . urlencode($session_id) . "&msg=error");
            exit;
        }
    }
}

// Cek Notifikasi Pesan untuk Toast
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $toast_status = 'success';
        $toast_message = 'Peserta dan seluruh jawabannya berhasil dihapus!';
    } elseif ($_GET['msg'] === 'error') {
        $toast_status = 'error';
        $toast_message = 'Gagal menghapus data peserta.';
    }
}

// PERBAIKAN 3: Endpoint AJAX get_answers (Gunakan String ID)
if (isset($_GET['api']) && $_GET['api'] === 'get_answers') {
    header('Content-Type: application/json');
    $respondent_id = isset($_GET['respondent_id']) ? trim($_GET['respondent_id']) : '';

    $stmt_ans = $pdo->prepare("
        SELECT 
            l.level_name,
            f.feature_name, 
            f.usage_practice,
            COALESCE(a.status, 'Belum') as status
        FROM respondents r
        JOIN game_sessions gs ON gs.id = r.session_id
        JOIN levels l ON l.instrument_type = gs.instrument_type
        JOIN features f ON f.level_id = l.id
        LEFT JOIN answers a ON a.feature_id = f.id AND a.respondent_id = r.id
        WHERE r.id = ?
        ORDER BY l.level_order ASC, f.id ASC
    ");
    $stmt_ans->execute([$respondent_id]);
    $answers_detail = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($answers_detail);
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
$no = 1;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Responden - PIN <?= htmlspecialchars($session['pin']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS untuk Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.bootstrap5.min.css" rel="stylesheet">

    <!-- Pustaka SweetAlert2 -->
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
        <a class="navbar-brand fw-bold" href="host">← Kembali ke Dashboard Host</a>
        <span class="text-white">Detail Responden</span>
    </nav>

    <div class="container" style="max-width: 1100px;">

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
        <div class="card shadow-sm border-0 p-4 mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">Daftar Peserta (<?= count($respondents) ?>)</h4>
                <div>
                    <!-- Tombol Export Excel -->
                    <a href="detail?session_id=<?= urlencode($session['id']) ?>&action=export_excel" class="btn btn-success btn-sm me-2 fw-bold">
                        📊 Cetak Excel
                    </a>
                    <a href="leaderboard?session_id=<?= urlencode($session['id']) ?>" target="_blank" class="btn btn-outline-info btn-sm">Lihat Leaderboard</a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="detailTable" class="table table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Nama Peserta</th>
                            <th>Instansi / Sekolah</th>
                            <th>Level Selesai</th>
                            <th>Fitur Dikuasai</th>
                            <th>Waktu Bergabung</th>
                            <th class="text-center" data-orderable="false">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($respondents as $r): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                <td><?= htmlspecialchars($r['institution']) ?></td>
                                <td><span class="badge bg-success fs-6">Level <?= $r['completed_level'] ?></span></td>
                                <td>
                                    <span class="badge bg-primary fs-6"><?= $r['total_sudah'] ?> Fitur</span>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                                <td class="text-center text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <!-- PERBAIKAN 4: Kirim r['id'] sebagai String ke JavaScript -->
                                        <a href="result?respondent_id=<?= $r['id'] ?>" class="btn btn-success fw-bold" target="_blank">
                                            Hasil
                                        </a>
                                        <button type="button" class="btn btn-info text-white fw-bold" onclick="showAnswersModal('<?= htmlspecialchars(addslashes($r['id'])) ?>', '<?= htmlspecialchars(addslashes($r['name'])) ?>')">
                                            Detail
                                        </button>
                                        <button type="button" class="btn btn-danger fw-bold" onclick="confirmDelete('<?= htmlspecialchars(addslashes($r['id'])) ?>', '<?= htmlspecialchars(addslashes($r['name'])) ?>')">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Pop-Up Detail Pertanyaan & Jawaban Peserta -->
    <div class="modal fade" data-bs-backdrop="static" id="answersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalTitle">Detail Jawaban Peserta</h5>
                        <small class="text-white-50" id="modalSubTitle">Kategori Instrumen: <?= str_replace('_', ' ', $session['instrument_type']) ?></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Memuat pertanyaan dan jawaban...</p>
                    </div>
                    <div class="table-responsive d-none" id="modalTableWrapper">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">Level</th>
                                    <th style="width: 65%;">Pertanyaan / Fitur Instrumen</th>
                                    <th style="width: 15%;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="modalAnswersBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS & FixedHeader -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

    <script>
        const answersModal = new bootstrap.Modal(document.getElementById('answersModal'));

        $(document).ready(function() {
            var table = $('#detailTable').DataTable({
                fixedHeader: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                language: {
                    search: "Cari Peserta:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Peserta tidak ditemukan",
                    info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                    infoEmpty: "Tidak ada data peserta",
                    infoFiltered: "(difilter dari _MAX_ total peserta)",
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
            const url = new URL(window.location.href);
            url.searchParams.delete('msg');
            window.history.replaceState({}, document.title, url.toString());
        }

        <?php if ($toast_status && $toast_message): ?>
            Toast.fire({
                icon: '<?= $toast_status ?>',
                title: '<?= $toast_message ?>'
            });
        <?php endif; ?>

        // PERBAIKAN 5: URL Encoding untuk Fetch AJAX Modal
        function showAnswersModal(respondentId, respondentName) {
            document.getElementById('modalTitle').textContent = `Detail Jawaban: ${respondentName}`;
            document.getElementById('modalLoading').classList.remove('d-none');
            document.getElementById('modalTableWrapper').classList.add('d-none');

            answersModal.show();

            const sessionId = encodeURIComponent('<?= $session_id ?>');
            const respId = encodeURIComponent(respondentId);

            fetch(`detail?session_id=${sessionId}&api=get_answers&respondent_id=${respId}`)
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('modalAnswersBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada pertanyaan/jawaban untuk instrumen ini.</td></tr>';
                    } else {
                        data.forEach(item => {
                            const badgeClass = item.status === 'Sudah' ? 'bg-success' : 'bg-danger';
                            const practiceInfo = item.usage_practice ? `<br><small class="text-muted">Praktik: ${item.usage_practice}</small>` : '';

                            const row = `
                                <tr>
                                    <td><span class="badge bg-secondary">${item.level_name}</span></td>
                                    <td>
                                        <strong>${item.feature_name}</strong>
                                        ${practiceInfo}
                                    </td>
                                    <td class="text-center"><span class="badge ${badgeClass}">${item.status}</span></td>
                                </tr>
                            `;
                            tbody.insertAdjacentHTML('beforeend', row);
                        });
                    }

                    document.getElementById('modalLoading').classList.add('d-none');
                    document.getElementById('modalTableWrapper').classList.remove('d-none');
                })
                .catch(err => {
                    console.error('Error fetching answers:', err);
                    document.getElementById('modalLoading').innerHTML = '<div class="alert alert-danger">Gagal memuat detail pertanyaan dan jawaban.</div>';
                });
        }

        // PERBAIKAN 6: URL Encoding untuk Redirection Hapus
        function confirmDelete(respondentId, respondentName) {
            Swal.fire({
                title: 'Hapus Peserta?',
                text: `Apakah Anda yakin ingin menghapus peserta "${respondentName}" beserta seluruh jawabannya?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const sessionId = encodeURIComponent('<?= $session_id ?>');
                    const respId = encodeURIComponent(respondentId);
                    window.location.href = `detail?session_id=${sessionId}&action=delete_respondent&respondent_id=${respId}`;
                }
            });
        }
    </script>
</body>

</html>