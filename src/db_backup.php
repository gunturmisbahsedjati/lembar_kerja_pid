<?php
session_start();
require 'config.php';

// Hak akses: Hanya ADMIN yang boleh mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: host");
    exit;
}

$toast_status = null;
$toast_message = '';

// 1. PROSES BACKUP DATABASE
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
    try {
        $tables = [];
        $query = $pdo->query("SHOW TABLES");
        while ($row = $query->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql_dump = "-- Backup Database PHP\n";
        $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop Table Jika Ada
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";

            // Struktur Tabel
            $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $sql_dump .= $row2[1] . ";\n\n";

            // Data Tabel
            $stmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $sql_dump .= "INSERT INTO `$table` VALUES(";
                $values = [];
                foreach ($row as $value) {
                    if (is_null($value)) {
                        $values[] = "NULL";
                    } else {
                        // Safe quoting untuk menghindari syntax error saat restore
                        $values[] = $pdo->quote($value);
                    }
                }
                $sql_dump .= implode(', ', $values);
                $sql_dump .= ");\n";
            }
            $sql_dump .= "\n";
        }

        $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Download File .sql
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql_dump));
        echo $sql_dump;
        exit;
    } catch (Exception $e) {
        $toast_status = 'error';
        $toast_message = "Gagal membuat backup: " . $e->getMessage();
    }
}

// 2. PROSES RESTORE DATABASE
if (isset($_POST['action']) && $_POST['action'] === 'restore') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['backup_file']['tmp_name'];
        $file_name = $_FILES['backup_file']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'sql') {
            header("Location: db_backup?msg=invalid_format");
            exit;
        } else {
            try {
                // Matikan pengecekan Foreign Key
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

                // A. HAPUS SEMUA TABEL LAMA
                $existing_tables = [];
                $query = $pdo->query("SHOW TABLES");
                while ($row = $query->fetch(PDO::FETCH_NUM)) {
                    $existing_tables[] = "`" . $row[0] . "`";
                }

                if (!empty($existing_tables)) {
                    $drop_query = "DROP TABLE IF EXISTS " . implode(', ', $existing_tables) . ";";
                    $pdo->exec($drop_query);
                }

                // B. BACA DAN EKSEKUSI FILE SQL
                $sql_contents = file_get_contents($file_tmp);
                $queries = preg_split('/;(?=(?:[^\'"]*[\'"][^\'"]*[\'"])*[^\'"]*$)/', $sql_contents);

                foreach ($queries as $q) {
                    $q_trim = trim($q);
                    if (!empty($q_trim)) {
                        $pdo->exec($q_trim);
                    }
                }

                // Hidupkan kembali Foreign Key Check
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

                // REDIRECT KE GET (Mencegah alert resubmit saat refresh)
                header("Location: db_backup?msg=restore_success");
                exit;
            } catch (PDOException $e) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
                header("Location: db_backup?msg=restore_error");
                exit;
            }
        }
    } else {
        header("Location: db_backup?msg=no_file");
        exit;
    }
}

// PENANGANAN NOTIFIKASI DARI URL PARAMETER (GET)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'restore_success') {
        $toast_status = 'success';
        $toast_message = 'Database berhasil di-restore!';
    } elseif ($_GET['msg'] === 'restore_error') {
        $toast_status = 'error';
        $toast_message = 'Gagal melakukan restore database!';
    } elseif ($_GET['msg'] === 'invalid_format') {
        $toast_status = 'error';
        $toast_message = 'Format file harus berupa .sql!';
    } elseif ($_GET['msg'] === 'no_file') {
        $toast_status = 'error';
        $toast_message = 'Silakan pilih file .sql terlebih dahulu!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore Database - Admin</title>
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="host">← Kembali ke Dashboard Host</a>
        <span class="text-white">Pemeliharaan Database (Admin)</span>
    </nav>

    <div class="container" style="max-width: 800px;">
        <div class="row g-4">
            <!-- KARTU BACKUP -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 p-4">
                    <div class="text-center mb-3">
                        <div class="fs-1 text-primary mb-2">📥</div>
                        <h4 class="fw-bold">Backup Database</h4>
                        <p class="text-muted small">Unduh salinan data berupa file <code>.sql</code> untuk mengamankan data sistem.</p>
                    </div>
                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="action" value="backup">
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                            Unduh Backup (.sql)
                        </button>
                    </form>
                </div>
            </div>

            <!-- KARTU RESTORE -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 p-4">
                    <div class="text-center mb-3">
                        <div class="fs-1 text-warning mb-2">📤</div>
                        <h4 class="fw-bold">Restore Database</h4>
                        <p class="text-muted small">Unggah file <code>.sql</code> hasil backup untuk mengembalikan data seperti semula.</p>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="restoreForm" class="mt-auto">
                        <input type="hidden" name="action" value="restore">
                        <div class="mb-3">
                            <input type="file" name="backup_file" accept=".sql" class="form-control" required>
                        </div>
                        <button type="button" class="btn btn-warning text-white w-100 fw-bold py-2" onclick="confirmRestore()">
                            Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // SweetAlert Notifikasi
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        <?php if ($toast_status && $toast_message): ?>
            // 1. Tampilkan Notifikasi Toast
            Toast.fire({
                icon: '<?= $toast_status ?>',
                title: <?= json_encode($toast_message) ?>
            });

            // 2. Hapus parameter ?msg=... dari URL tanpa mereload halaman
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({
                    path: cleanUrl
                }, '', cleanUrl);
            }
        <?php endif; ?>

        // Konfirmasi sebelum melakukan restore
        function confirmRestore() {
            const fileInput = document.querySelector('input[name="backup_file"]');
            if (!fileInput.files.length) {
                Toast.fire({
                    icon: 'error',
                    title: 'Pilih file backup terlebih dahulu!'
                });
                return;
            }

            Swal.fire({
                title: 'Timpa Data Sekarang?',
                text: 'Proses ini akan menggantikan struktur & seluruh data lama dengan file backup!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Ya, Restore!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('restoreForm').submit();
                }
            });
        }
    </script>
</body>

</html>