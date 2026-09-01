<?php
session_start();
require 'config.php';

// Hak akses: Hanya ADMIN yang boleh mengelola user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: host");
    exit;
}

$toast_status = null;
$toast_message = '';

// 1. TOGGLE STATUS (AKTIF / NONAKTIF)
if (isset($_GET['toggle_id'])) {
    $toggle_id = intval($_GET['toggle_id']);

    // Mencegah admin me-nonaktifkan akunnya sendiri
    if ($toggle_id === $_SESSION['user_id']) {
        header("Location: users?msg=self_toggle");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$toggle_id]);
    header("Location: users?msg=toggled");
    exit;
}

// 2. TAMBAH USER (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = trim($_POST['role']);
    $status   = trim($_POST['status'] ?? 'active');

    if (!empty($name) && !empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $hashed_password, $role, $status]);
            header("Location: users?msg=created");
            exit;
        } catch (PDOException $e) {
            header("Location: users?msg=exists");
            exit;
        }
    }
}

// 3. EDIT USER (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id       = intval($_POST['user_id']);
    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $role     = trim($_POST['role']);
    $status   = trim($_POST['status']);
    $password = trim($_POST['password']);

    // Mencegah admin me-nonaktifkan diri sendiri lewat edit
    if ($id === $_SESSION['user_id'] && $status === 'inactive') {
        header("Location: users?msg=self_toggle");
        exit;
    }

    try {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $username, $role, $status, $hashed_password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $username, $role, $status, $id]);
        }
        header("Location: users?msg=updated");
        exit;
    } catch (PDOException $e) {
        header("Location: users?msg=exists");
        exit;
    }
}

// 4. HAPUS USER (DELETE)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    if ($delete_id === $_SESSION['user_id']) {
        header("Location: users?msg=self_delete");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: users?msg=deleted");
    exit;
}

// Notifikasi SweetAlert Toast
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $toast_status = 'success';
        $toast_message = "User baru berhasil ditambahkan!";
    } elseif ($_GET['msg'] === 'updated') {
        $toast_status = 'success';
        $toast_message = "Data user berhasil diperbarui!";
    } elseif ($_GET['msg'] === 'toggled') {
        $toast_status = 'success';
        $toast_message = "Status akun berhasil diubah!";
    } elseif ($_GET['msg'] === 'deleted') {
        $toast_status = 'success';
        $toast_message = "User berhasil dihapus!";
    } elseif ($_GET['msg'] === 'exists') {
        $toast_status = 'error';
        $toast_message = "Username sudah digunakan oleh user lain!";
    } elseif ($_GET['msg'] === 'self_delete') {
        $toast_status = 'warning';
        $toast_message = "Anda tidak dapat menghapus akun Anda sendiri!";
    } elseif ($_GET['msg'] === 'self_toggle') {
        $toast_status = 'warning';
        $toast_message = "Anda tidak dapat me-nonaktifkan akun Anda sendiri!";
    }
}

// READ (Ambil seluruh data user)
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
$no = 1;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark px-4 mb-4">
        <a class="navbar-brand fw-bold" href="host">← Kembali ke Dashboard Host</a>
        <span class="text-white">Manajemen User (Admin)</span>
    </nav>

    <div class="container" style="max-width: 1050px;">
        <div class="card shadow-sm border-0 p-4 mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">Daftar Akun User</h4>
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    + Tambah User Baru
                </button>
            </div>

            <div class="table-responsive">
                <table id="usersTable" class="table table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status Akun</th>
                            <th>Dibuat Pada</th>
                            <th class="text-center" data-orderable="false">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                        <?= strtoupper($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $u['status'] === 'active' ? 'AKTIF' : 'NONAKTIF' ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($u['created_at'])) ?></td>
                                <td class="text-center text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                            <a href="users?toggle_id=<?= $u['id'] ?>"
                                                class="btn <?= $u['status'] === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                                <?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-warning fw-bold text-white"
                                            onclick='openEditModal(<?= json_encode($u) ?>)'>
                                            Edit
                                        </button>

                                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-danger fw-bold"
                                                onclick="confirmDelete('<?= $u['id'] ?>', '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                                                Hapus
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH USER -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Tambah User Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Pengajar A" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: host_a" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Role Hak Akses</label>
                                <select name="role" class="form-select" required>
                                    <option value="host">Host / Pengajar</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status Akun</label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT USER -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin diubah">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Role Hak Akses</label>
                                <select name="role" id="edit_role" class="form-select" required>
                                    <option value="host">Host / Pengajar</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status Akun</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning text-white fw-bold">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));

        $(document).ready(function() {
            $('#usersTable').DataTable({
                pageLength: 10
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

        // Fungsi Buka Modal Edit & Isi Data
        function openEditModal(userData) {
            document.getElementById('edit_user_id').value = userData.id;
            document.getElementById('edit_name').value = userData.name;
            document.getElementById('edit_username').value = userData.username;
            document.getElementById('edit_role').value = userData.role;
            document.getElementById('edit_status').value = userData.status;
            editModal.show();
        }

        // Konfirmasi Hapus Data
        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Hapus User?',
                text: `Apakah Anda yakin ingin menghapus user "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `users?delete_id=${id}`;
                }
            });
        }
    </script>
</body>

</html>