<?php
session_start();
require_once 'koneksi.php';


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, nama_lengkap, username, password, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: user/dashboard.php');
                }

                exit;
            } else {
                $error = 'Username atau password salah.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Masuk — Stella Library</title>

    <link rel="stylesheet" href="style.css">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
            background: #f8f5ef;
            font-family: "DM Sans", Arial, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 36px;
            box-sizing: border-box;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 15px 45px rgba(32, 52, 77, 0.10);
        }

        .login-brand {
            margin-bottom: 28px;
            text-align: center;
        }

        .login-brand-icon {
            display: inline-flex;
            width: 54px;
            height: 54px;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            border-radius: 16px;
            background: #20344d;
            color: #fff8f0;
            font-family: Georgia, serif;
            font-size: 28px;
        }

        .login-brand h1 {
            margin: 0;
            color: #20344d;
            font-family: Georgia, serif;
            font-size: 28px;
        }

        .login-brand p {
            margin: 8px 0 0;
            color: #777;
            font-size: 14px;
        }

        .login-form label {
            display: block;
            margin: 18px 0 8px;
            color: #20344d;
            font-size: 13px;
            font-weight: 600;
        }

        .login-form input {
            width: 100%;
            padding: 13px 14px;
            box-sizing: border-box;
            border: 1px solid #e2dfd8;
            border-radius: 10px;
            background: #fff;
            font-size: 14px;
        }

        .login-form input:focus {
            outline: 2px solid rgba(32, 52, 77, 0.15);
            border-color: #20344d;
        }

        .login-button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #20344d;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .login-button:hover {
            background: #304c6c;
        }

        .login-error {
            margin-top: 18px;
            padding: 12px;
            border-radius: 9px;
            background: #fff0ef;
            color: #a13c3c;
            font-size: 13px;
        }

        .login-footer {
            margin-top: 22px;
            color: #777;
            font-size: 13px;
            text-align: center;
            line-height: 1.8;
        }

        .login-footer a {
            color: #20344d;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 26px 22px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="login-brand">
        <div class="login-brand-icon">S</div>
        <h1>Selamat Datang</h1>
        <p>Masuk ke akun Stella Library kamu.</p>
    </div>

    <form method="POST" action="login.php" class="login-form">

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
        >

        <?php if ($error !== ''): ?>
            <div class="login-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="login-button">
            Masuk ke Akun →
        </button>

    </form>

    <div class="login-footer">
        Belum punya akun?
        <a href="register.php">Daftar sekarang</a>
        <br>
        <a href="index.php">← Kembali ke Beranda</a>
    </div>

</div>

</body>
</html>