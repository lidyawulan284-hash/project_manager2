<?php
// Mencegah error "session already active" yang merusak tampilan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// --- SCRIPT OTOMATIS BUAT/UPDATE AKUN ADMIN ---
$targetUsername = 'LIDYA WULAN CAHYA';
$targetPassword = password_hash('ruangrindu', PASSWORD_DEFAULT);

$checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$checkUser->execute([$targetUsername]);

if (!$checkUser->fetch()) {
    // Jika belum ada, buat akun baru
    $insertUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $insertUser->execute([$targetUsername, $targetPassword]);
} else {
    // Jika sudah ada, pastikan passwordnya diupdate menjadi 'ruangrindu'
    $updateUser = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $updateUser->execute([$targetPassword, $targetUsername]);
}
// ----------------------------------------------

// JIKA SUDAH LOGIN, LANGSUNG LEMPAR KE KASIR (INDEX.PHP) BUKAN DASHBOARD
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        
        // JIKA LOGIN BERHASIL, ARAHKAN KE KASIR (INDEX.PHP)
        header("Location: index.php");
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ruang Rindu</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        /* Menghilangkan margin bawaan agar tampilan dijamin di tengah */
        body, html { 
            margin: 0; 
            padding: 0; 
            height: 100%; 
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #FADADD; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .login-card { 
            background: #FFFFFF; 
            border-radius: 24px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 400px; 
        }
        .brand { 
            font-size: 1.8rem; 
            font-weight: 800; 
            color: #4A4A4A; 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .form-control { 
            border-radius: 12px; 
            padding: 12px; 
            border: 2px solid #FFF0F2; 
        }
        .form-control:focus { 
            border-color: #B5EAD7; 
            box-shadow: none; 
        }
        .btn-login { 
            background-color: #C7CEEA; 
            color: #4A4A4A; 
            font-weight: 600; 
            border-radius: 12px; 
            padding: 12px; 
            width: 100%; 
            border: none; 
            margin-top: 10px; 
        }
        .btn-login:hover { 
            filter: brightness(0.95); 
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">☕ RUANG <span style="color: #D98A8A;">RINDU</span></div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="border-radius: 12px; font-size: 0.9rem; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label" style="font-weight: 600; color:#4A4A4A;">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label class="form-label" style="font-weight: 600; color:#4A4A4A;">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Masuk Sistem</button>
        </form>
    </div>
</body>
</html>