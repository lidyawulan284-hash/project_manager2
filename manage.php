<?php
require 'config.php';

// Ambil semua data produk dari database
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok & Edit - POS App</title>
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
            --pastel-mint: #B5EAD7;   
            --pastel-peach: #FFDAC1;  
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

        /* Tombol Aksi di Tabel */
        .btn-action {
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
            border: none;
        }

        .btn-add-stock {
            background-color: var(--pastel-mint);
            color: var(--text-dark);
        }
        .btn-add-stock:hover {
            background-color: #A0DBC6;
            color: var(--text-dark);
        }

        .btn-edit {
            background-color: var(--pastel-peach);
            color: var(--text-dark);
        }
        .btn-edit:hover {
            background-color: #F5CBAE;
            color: var(--text-dark);
        }

        /* Badge Kategori */
        .cat-badge {
            background-color: #F3F4F6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- Header Halaman -->
        <div class="page-header">
            <h1 class="page-title">Kelola Stok & Edit Produk</h1>
            <a href="index.php" class="btn-back">Kembali ke Beranda</a>
        </div>

        <!-- Tabel Produk -->
        <div class="table-wrapper">
            <?php if(empty($products)): ?>
                <div class="alert text-center m-0" style="background-color: #FAF9F6; color: #9CA3AF; border-radius: 16px;">
                    Belum ada data produk.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
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
                                <td class="fw-bold text-capitalize">
                                    <?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?>
                                </td>
                                <td>
                                    <span class="cat-badge text-capitalize">
                                        <?= htmlspecialchars($product["category"], ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Menampilkan stok tebal jika stok aman, dan warna merah muda jika stok 0 -->
                                    <span style="<?= $product["stock"] <= 0 ? 'color: #D98A8A;' : 'font-weight: 700;' ?>">
                                        <?= htmlspecialchars($product["stock"], ENT_QUOTES, "UTF-8") ?> pcs
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Link menuju restock.php -->
                                        <a href="restock.php?id=<?= $product['id'] ?>" class="btn-action btn-add-stock">+ Tambah Stok</a>
                                        <!-- Link menuju edit.php -->
                                        <a href="edit.php?id=<?= $product['id'] ?>" class="btn-action btn-edit">Edit Data</a>
                                    </div>
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