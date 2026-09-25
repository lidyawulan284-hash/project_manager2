<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Validasi CSRF gagal.");
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: index.php"); 
    exit;
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        
        <!-- HEADER UTAMA (Baris Pertama Tombol) -->
        <div class="d-flex justify-content-between mb-2 align-items-center">
            <h2 class="mb-0">Daftar Produk</h2>
            <div>
                <a href="history.php" class="btn btn-info text-white me-2">Riwayat Pemesanan</a>
                <a href="create.php" class="btn btn-primary">Tambah Produk</a>
            </div>
        </div>
        
        <!-- BARIS KEDUA (Tombol Tambah Stok & Edit Terpisah) -->
        <div class="d-flex justify-content-end mb-4">
            <!-- Tombol Tambah Stok (Mengarah ke manage.php) -->
            <a href="manage.php" class="btn btn-secondary me-2 text-white">📦 Tambah Stok</a>
            <!-- Tombol Edit Produk (Mengarah ke manage.php) -->
            <a href="manage.php" class="btn btn-warning text-dark">✏️ Edit Produk</a>
        </div>

        <?php if(empty($products)): ?>
            <div class="alert alert-info">Belum ada produk. Silakan tambah produk baru.</div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title text-capitalize fw-bold"><?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted text-capitalize"><?= htmlspecialchars($product["category"], ENT_QUOTES, "UTF-8") ?></h6>
                                <p class="card-text mt-3">
                                    Harga: Rp <?= number_format($product["price"], 0, ',', '.') ?><br>
                                    Stok: <strong><?= htmlspecialchars($product["stock"], ENT_QUOTES, "UTF-8") ?></strong>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-top-0 d-flex justify-content-between pb-3">
                                <a href="order.php?id=<?= $product['id'] ?>" class="btn btn-success w-50 me-2">Pesan</a>
                                <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus produk ini?');" class="m-0 w-50">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-danger w-100">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>