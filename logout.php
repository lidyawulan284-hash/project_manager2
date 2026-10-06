<?php
// Mulai sesi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua data sesi (membersihkan data login)
session_unset();

// Hancurkan sesi sepenuhnya
session_destroy();

// Arahkan (lempar) pengguna kembali ke halaman login.php
header("Location: login.php");
exit;
?>