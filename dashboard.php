<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalStock = $pdo->query("SELECT SUM(stock) FROM products")->fetchColumn();

$dailyRevenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE DATE(order_date) = CURDATE()")->fetchColumn();
if (!$dailyRevenue) {
    $dailyRevenue = 0; 
}

$lowStock = $pdo->query("SELECT name, stock FROM products WHERE stock <= 20 ORDER BY stock ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Ruang Rindu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #FAF9F6; color: #4A4A4A; }
        .navbar { background-color: #FADADD; padding: 15px 30px; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); }
        
        /* Animasi Kartu Statistik */
        .stat-card { 
            background: #FFFFFF; 
            border-radius: 20px; 
            padding: 25px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
            border: 2px solid #FFF0F2; 
            transition: all 0.3s ease; /* Membuat animasi perpindahan halus */
        }
        .stat-card:hover { 
            transform: translateY(-6px); /* Efek kartu naik sedikit saat disentuh kursor */
            box-shadow: 0 10px 25px rgba(0,0,0,0.06); 
        }
        
        /* Animasi Tombol Atas */
        .btn-nav-custom {
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-nav-custom:hover {
            transform: scale(1.05); /* Efek tombol membesar sedikit saat ditekan/disorot */
            filter: brightness(0.95);
        }

        .stat-title { color: #9CA3AF; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #4A4A4A; }
        .warning-card { background: #FFF5F5; border-color: #FDE4E4; }
    </style>
</head>
<body>
    <nav class="navbar d-flex justify-content-between align-items-center mb-5">
        <div style="font-size: 1.5rem; font-weight: 700; color: #4A4A4A;">☕ Dashboard <span style="color:#D98A8A;">Admin</span></div>
        <div>
            <span class="me-3 fw-bold" style="color:#4A4A4A;">Halo, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
            <a href="index.php" class="btn btn-sm btn-nav-custom" style="background: #B5EAD7; color: #4A4A4A;">Ke Kasir</a>
            <a href="logout.php" class="btn btn-sm btn-nav-custom" style="background: #FFDAC1; color: #4A4A4A;">Keluar</a>
        </div>
    </nav>

    <div class="container">
        <div class="alert alert-info mb-4" style="border-radius: 16px; background-color: #EBF8FF; border: none; color: #2B6CB0; font-weight: 500;">
            💡 <strong>Info:</strong> Pendapatan Hari Ini akan bertambah secara <strong>otomatis</strong> setiap kali ada pelanggan yang berhasil memesan menu di halaman Kasir.
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card" style="border-color: #C7CEEA;">
                    <div class="stat-title">Total Jenis Menu</div>
                    <div class="stat-value"><?= $totalProducts ?? 0 ?> Item</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="border-color: #B5EAD7;">
                    <div class="stat-title">Total Stok Fisik</div>
                    <div class="stat-value"><?= $totalStock ?? 0 ?> Pcs</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="border-color: #FADADD;">
                    <div class="stat-title">Pendapatan Hari Ini</div>
                    <div class="stat-value" style="color: #D98A8A;">Rp <?= number_format($dailyRevenue, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($lowStock) && count($lowStock) > 0): ?>
            <div class="stat-card warning-card">
                <h4 class="fw-bold mb-4" style="color:#D98A8A;">⚠️ Peringatan Stok Menipis</h4>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="background-color: transparent; border-bottom-color: #FDE4E4;">Nama Menu</th>
                            <th style="background-color: transparent; border-bottom-color: #FDE4E4;">Sisa Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lowStock as $item): ?>
                            <tr>
                                <td class="fw-semibold" style="background-color: transparent; border-bottom-color: #FDE4E4;"><?= htmlspecialchars($item['name']) ?></td>
                                <td style="color: #D98A8A; font-weight: 700; background-color: transparent; border-bottom-color: #FDE4E4;">Sisa <?= $item['stock'] ?> pcs</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>