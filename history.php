<?php
require 'config.php';

// Ambil data riwayat pesanan dari database (Sesuaikan nama tabel 'orders' jika berbeda)
$stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - POS App</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* =========================================
           TEMA SOFT PASTEL MODERN (MACARON PALETTE)
           ========================================= */
        :root {
            --bg-body: #FAF9F6;       
            --bg-card: #FFFFFF;       
            --pastel-pink: #FADADD;   
            --text-dark: #4A4A4A;     
            --text-muted: #9CA3AF;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            padding-bottom: 50px;
        }

        /* Header Halaman */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.8rem;
            margin: 0;
        }

        .btn-back {
            background-color: transparent;
            color: var(--text-dark);
            border: 2px solid var(--pastel-pink);
            border-radius: 14px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background-color: var(--pastel-pink);
            color: var(--text-dark);
        }

        /* Container Tabel */
        .table-wrapper {
            background-color: var(--bg-card);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid #FFF0F2;
            overflow-x: auto;
        }

        /* Kustomisasi Tabel */
        .table {
            margin-bottom: 0;
            color: var(--text-dark);
        }

        .table thead th {
            background-color: var(--pastel-pink) !important;
            color: var(--text-dark) !important;
            font-weight: 600;
            border-bottom: none;
            padding: 15px;
            font-size: 0.95rem;
        }

        /* Membuat sudut header tabel membulat */
        .table thead tr th:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
        .table thead tr th:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #F3F4F6;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover td {
            background-color: #FCFAFA;
        }

        /* Angka Nominal */
        .price-text {
            font-weight: 600;
            color: var(--text-dark);
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- Header Halaman -->
        <div class="page-header">
            <h1 class="page-title">Riwayat Pemesanan</h1>
            <!-- Tombol Kembali yang sebelumnya abu-abu kaku -->
            <a href="index.php" class="btn-back">Kembali ke Daftar Produk</a>
        </div>

        <!-- Tabel Riwayat -->
        <div class="table-wrapper">
            <?php if(empty($orders)): ?>
                <div class="alert text-center m-0" style="background-color: #FAF9F6; color: #9CA3AF; border-radius: 16px;">
                    Belum ada riwayat pesanan.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="25%">Tanggal Pemesanan</th>
                            <th width="35%">Nama Produk</th>
                            <th width="15%">Jumlah</th>
                            <th width="20%">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($orders as $order): 
                            // Format tanggal (Opsional: sesuaikan nama kolom order_date dengan database Anda)
                            $tanggal = isset($order['order_date']) ? date('d M Y, H:i', strtotime($order['order_date'])) : '-';
                        ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($tanggal, ENT_QUOTES, "UTF-8") ?></td>
                                <td class="text-capitalize fw-semibold">
                                    <?= htmlspecialchars($order['product_name'] ?? 'Nama Produk', ENT_QUOTES, "UTF-8") ?>
                                </td>
                                <td><?= htmlspecialchars($order['quantity'] ?? '0', ENT_QUOTES, "UTF-8") ?> pcs</td>
                                <td class="price-text">
                                    Rp <?= number_format($order['total_price'] ?? 0, 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>