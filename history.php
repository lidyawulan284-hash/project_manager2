<?php
require 'config.php';

// Mengambil data pesanan dan menggabungkannya dengan tabel produk untuk mendapatkan nama produk
$stmt = $pdo->query("
    SELECT orders.*, products.name AS product_name 
    FROM orders 
    JOIN products ON orders.product_id = products.id 
    ORDER BY orders.order_date DESC
");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between mb-4">
            <h2>Riwayat Pemesanan</h2>
            <a href="index.php" class="btn btn-secondary">Kembali ke Daftar Produk</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <?php if(empty($orders)): ?>
                    <div class="alert alert-info mb-0">Belum ada riwayat pesanan.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal Pemesanan</th>
                                    <th>Nama Produk</th>
                                    <th>Jumlah</th>
                                    <th>Total Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d M Y, H:i', strtotime($order['order_date'])) ?></td>
                                        <td><?= htmlspecialchars($order['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= $order['quantity'] ?> pcs</td>
                                        <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>