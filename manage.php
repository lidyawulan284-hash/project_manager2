<?php
require 'config.php';

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Stok & Edit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-4">
            <h2>Kelola Stok & Edit Produk</h2>
            <a href="index.php" class="btn btn-secondary">Kembali ke Beranda</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Sisa Stok</th>
                            <th class="text-center">Aksi (Tambah Stok / Edit)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="text-capitalize fw-bold"><?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?></td>
                                <td class="text-capitalize"><?= htmlspecialchars($product["category"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><strong><?= $product["stock"] ?> pcs</strong></td>
                                <td class="text-center">
                                    <!-- Tombol menuju Restock dan Edit yang membawa ID masing-masing produk -->
                                    <a href="restock.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary me-2">+ Tambah Stok</a>
                                    <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-warning">Edit Data</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>