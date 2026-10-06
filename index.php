<?php
session_start();
require 'config.php';

// CEK KEAMANAN: Harus login dulu
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // Hanya admin yang boleh menghapus (opsional, sebagai pengaman)
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
    }
    header("Location: index.php"); 
    exit;
}

// Ambil data produk DIGABUNG dengan nama kategorinya
$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

// Ambil data kategori untuk tombol filter
$stmtCat = $pdo->query("SELECT * FROM categories");
$categories = $stmtCat->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Rindu - Kasir POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #FAF9F6;       
            --bg-card: #FFFFFF;       
            --pastel-pink: #FADADD;   
            --pastel-mint: #B5EAD7;   
            --pastel-blue: #C7CEEA;   
            --pastel-peach: #FFDAC1;  
            --pastel-yellow: #FFF5BA; 
            --text-dark: #4A4A4A;     
            --text-muted: #9CA3AF;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body); color: var(--text-dark); padding-bottom: 50px; }
        .pos-header { background-color: var(--pastel-pink); padding: 25px 20px 35px 20px; color: var(--text-dark); border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); }
        .cafe-brand { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); letter-spacing: 1px; margin: 0; }
        .cafe-brand span { color: #D98A8A; }
        .pos-tabs { display: flex; gap: 25px; font-size: 1.05rem; font-weight: 600; }
        .pos-tab-link { color: #A88B8E; text-decoration: none; padding-bottom: 5px; position: relative; transition: 0.2s; }
        .pos-tab-link.active { color: var(--text-dark); }
        .pos-tab-link.active::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 100%; height: 3px; background-color: var(--text-dark); border-radius: 3px; }
        .btn-add { color: var(--text-dark); font-size: 1.8rem; line-height: 1; text-decoration: none; font-weight: 500; transition: transform 0.2s; background: rgba(255,255,255,0.4); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .btn-add:hover { transform: scale(1.1); background: rgba(255,255,255,0.7); }
        .search-bar { background-color: var(--bg-card); border-radius: 16px; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; margin-top: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); border: 2px solid #FFF0F2; }
        .search-input { border: none; outline: none; width: 100%; margin-left: 10px; font-family: 'Poppins', sans-serif; color: var(--text-dark); background: transparent; font-size: 0.95rem; }
        .category-scroll { display: flex; flex-direction: row; flex-wrap: nowrap; gap: 12px; padding: 20px; margin-bottom: 5px; overflow-x: auto; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none; }
        .category-scroll::-webkit-scrollbar { display: none; }
        .pill-btn { background-color: var(--bg-card); color: var(--text-muted); border-radius: 30px; padding: 10px 24px; font-size: 0.9rem; font-weight: 600; cursor: pointer; border: 2px solid #F3F4F6; transition: all 0.3s ease; white-space: nowrap; }
        .pill-btn.active { background-color: var(--pastel-mint); color: var(--text-dark); border-color: var(--pastel-mint); box-shadow: 0 4px 10px rgba(181, 234, 215, 0.4); }
        .product-card { border: none; border-radius: 20px; overflow: hidden; background-color: var(--bg-card); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02); transition: all 0.3s ease; position: relative; }
        .product-card:hover { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06); transform: translateY(-5px); }
        .product-img { width: 100%; height: 150px; object-fit: cover; }
        .card-body-pos { padding: 18px; }
        .product-title { font-weight: 700; font-size: 1rem; color: var(--text-dark); margin-bottom: 4px; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-price { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; }
        .stock-badge { position: absolute; top: 12px; right: 12px; background: var(--pastel-yellow); color: var(--text-dark); font-size: 0.75rem; font-weight: 700; padding: 5px 12px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .btn-action-pos { font-size: 0.85rem; font-weight: 600; padding: 10px; border-radius: 12px; transition: 0.2s; border: none; }
        .btn-pesan { background-color: var(--pastel-blue); color: var(--text-dark); }
        .btn-pesan:hover { filter: brightness(0.95); }
        .btn-hapus { background-color: transparent; color: #D98A8A; border: 2px solid #FDE4E4; }
        .btn-hapus:hover { background-color: var(--pastel-peach); border-color: var(--pastel-peach); color: var(--text-dark); }
    </style>
</head>
<body>

    <div class="pos-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="cafe-brand">☕ RUANG <span>RINDU</span></h1>
            <div class="d-flex gap-2 align-items-center">
                <!-- Tambah Menu Hanya Muncul Kalau Admin -->
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <a href="create.php" class="btn-add" title="Tambah Menu">+</a>
                <?php endif; ?>
                <!-- Tombol ke Dashboard -->
                <a href="dashboard.php" class="btn btn-sm" style="background:#FFFFFF; color:#4A4A4A; border-radius:10px; font-weight:600;">Dashboard</a>
            </div>
        </div>

        <div class="pos-tabs">
            <a href="index.php" class="pos-tab-link active">Kasir</a>
            <a href="history.php" class="pos-tab-link">Order</a>
            <a href="manage.php" class="pos-tab-link">Kelola</a>
        </div>

        <div class="search-bar">
            <div class="d-flex align-items-center w-100">
                <span style="font-size: 1.2rem; color: #FADADD;">🔍</span>
                <input type="text" id="searchInput" class="search-input" placeholder="Cari menu favorit...">
            </div>
        </div>
    </div>

    <!-- Kategori Filter (Berubah Otomatis Sesuai Database) -->
    <div class="category-scroll">
        <div class="pill-btn filter-btn active">Semua</div>
        <?php foreach ($categories as $cat): ?>
            <div class="pill-btn filter-btn"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>

    <div class="container-fluid px-3">
        <?php if(empty($products)): ?>
            <div class="alert text-center mt-4" style="background-color: var(--bg-card); color: var(--text-muted); border-radius: 16px; border: 2px dashed var(--pastel-pink);">
                Menu masih kosong. Silakan tambah produk baru.
            </div>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3" id="productContainer">
                <?php foreach ($products as $product): ?>
                    <div class="col product-item" data-category="<?= htmlspecialchars(strtolower(trim($product['category_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="product-card h-100 d-flex flex-column">
                            
                            <?php 
                                if (!empty($product['image'])) {
                                    $imgSrc = 'uploads/' . htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8');
                                } else {
                                    $imgSrc = "https://ui-avatars.com/api/?name=" . urlencode($product['name']) . "&background=FADADD&color=4A4A4A&size=300&font-size=0.3";
                                }
                            ?>
                            <img src="<?= $imgSrc ?>" class="product-img" alt="Foto Produk">
                            
                            <span class="stock-badge">Stok: <?= htmlspecialchars($product["stock"], ENT_QUOTES, "UTF-8") ?></span>
                            
                            <div class="card-body-pos d-flex flex-column flex-grow-1">
                                <h2 class="product-title text-capitalize"><?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?></h2>
                                <div class="product-price mb-4">Rp <?= number_format($product["price"], 0, ',', '.') ?></div>
                                
                                <div class="mt-auto d-flex gap-2">
                                    <a href="order.php?id=<?= $product['id'] ?>" class="btn btn-action-pos btn-pesan flex-grow-1 text-center text-decoration-none">Pesan</a>
                                    
                                    <?php if($_SESSION['role'] === 'admin'): ?>
                                    <form method="POST" action="" onsubmit="return confirm('Hapus produk ini?');" class="m-0 flex-grow-1">
                                        <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn btn-action-pos btn-hapus w-100">Hapus</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="noResultMsg" class="alert text-center mt-4 d-none" style="background-color: #FFF0F2; color: #D98A8A; border-radius: 16px;">
                Produk tidak ditemukan.
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterBtns = document.querySelectorAll('.filter-btn');
            const productItems = document.querySelectorAll('.product-item');
            const noResultMsg = document.getElementById('noResultMsg');
            
            let currentCategory = 'Semua';

            function filterProducts() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                productItems.forEach(item => {
                    const title = item.querySelector('.product-title').innerText.toLowerCase();
                    const category = item.getAttribute('data-category'); 
                    const btnCategory = currentCategory.toLowerCase(); 
                    
                    const matchesSearch = title.includes(searchTerm);
                    let matchesCategory = false;
                    
                    if (btnCategory === 'semua') {
                        matchesCategory = true;
                    } else if (category === btnCategory) {
                        matchesCategory = true;
                    }

                    if (matchesSearch && matchesCategory) {
                        item.style.display = ''; 
                        visibleCount++;
                    } else {
                        item.style.display = 'none'; 
                    }
                });

                if (visibleCount === 0 && productItems.length > 0) {
                    noResultMsg.classList.remove('d-none');
                } else {
                    noResultMsg.classList.add('d-none');
                }
            }

            if (searchInput) searchInput.addEventListener('keyup', filterProducts);

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentCategory = this.innerText.trim();
                    filterProducts();
                });
            });
        });
    </script>
</body>
</html>