<?php
require 'config.php';

// Ambil ID produk dari URL
$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int) $_POST['quantity'];

    if ($qty <= 0) {
        $errors[] = "Jumlah pesanan harus minimal 1.";
    } elseif ($qty > $product['stock']) {
        $errors[] = "Stok tidak cukup! Sisa stok saat ini hanya " . $product['stock'];
    }

    if (empty($errors)) {
        $total_price = $qty * $product['price'];

        // Gunakan Transaction agar jika satu gagal, semua dibatalkan
        $pdo->beginTransaction();
        try {
            // 1. Kurangi stok di tabel products
            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmtUpdate->execute([$qty, $id]);

            // 2. Simpan riwayat pesanan di tabel orders
            $stmtInsert = $pdo->prepare("INSERT INTO orders (product_id, quantity, total_price) VALUES (?, ?, ?)");
            $stmtInsert->execute([$id, $qty, $total_price]);

            $pdo->commit();
            
            // Kembali ke halaman utama setelah sukses
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Gagal memproses pesanan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesan Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <h2 class="mb-4">Pesan Produk</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                <p class="text-muted mb-4">
                    Harga: Rp <?= number_format($product['price'], 0, ',', '.') ?> <br>
                    Sisa Stok: <strong><?= $product['stock'] ?></strong>
                </p>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Berapa banyak yang ingin dipesan?</label>
                        <input type="number" name="quantity" class="form-control" required min="1" max="<?= $product['stock'] ?>" value="1">
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-2">Konfirmasi Pesanan</button>
                    <a href="index.php" class="btn btn-secondary w-100">Batal</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>