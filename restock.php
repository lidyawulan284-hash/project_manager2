<?php
require 'config.php';

// Ambil ID produk
$id = $_GET['id'] ?? 0;

// Cari data produk
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan.");
}

$errors = [];

// Proses form saat tombol Submit ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tambahan_stok = (int) $_POST['add_stock'];

    if ($tambahan_stok <= 0) {
        $errors[] = "Jumlah tambahan stok minimal 1.";
    }

    if (empty($errors)) {
        // Update database: Stok lama + Stok tambahan
        $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmtUpdate->execute([$tambahan_stok, $id]);

        // Beri tahu sistem untuk kembali ke halaman utama setelah sukses
        header("Location: index.php?restock_success=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Stok (Restock)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5 d-flex flex-column align-items-center">
        <h2 class="mb-4">Tambah Stok (Restock)</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger w-100" style="max-width: 400px;">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm w-100" style="max-width: 400px;">
            <div class="card-body">
                <h5 class="card-title text-primary fw-bold"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                <p class="text-muted mb-4">
                    Sisa stok saat ini: <strong><?= $product['stock'] ?> pcs</strong>
                </p>

                <!-- AWAL FORM YANG DIPERBAIKI -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label text-secondary" style="font-size: 14px;">Masukkan jumlah stok yang ditambahkan:</label>
                        <input type="number" name="add_stock" class="form-control" required min="1" value="1">
                    </div>
                    
                    <!-- Tombol ini sekarang DI DALAM tag <form> -->
                    <button type="submit" class="btn btn-primary w-100 mb-2">Simpan Stok Tambahan</button>
                    <a href="index.php" class="btn btn-secondary w-100">Batal</a>
                </form>
                <!-- AKHIR FORM -->
                
            </div>
        </div>
    </div>
</body>
</html>