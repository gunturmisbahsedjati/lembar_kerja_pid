<?php
require 'config.php';

// Generate hash baru
$new_hash1 = password_hash('admin123', PASSWORD_BCRYPT);
$new_hash = password_hash('pid2026', PASSWORD_BCRYPT);
echo $new_hash1 . "<br>";
echo $new_hash;

// Hapus user lama & masukkan user admin baru
$pdo->exec("DELETE FROM users WHERE username = 'admin'");
$stmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES ('admin', ?, 'Administrator')");

if ($stmt->execute([$new_hash])) {
    echo "<h2 style='color:green;'>Password Admin Berhasil Direset!</h2>";
    echo "<p>Username: <b>admin</b></p>";
    echo "<p>Password: <b>pid2026</b></p>";
    echo "<a href='login'>Klik di sini untuk Login</a>";
} else {
    echo "<h2 style='color:red;'>Gagal mengupdate database.</h2>";
}
