<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pilih Login — Stella Library</title>

    <link rel="stylesheet" href="style.css">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            box-sizing: border-box;
            background: #f8f5ef;
            font-family: "DM Sans", Arial, sans-serif;
        }

        .pilihan-container {
            width: 100%;
            max-width: 800px;
            text-align: center;
        }

        .logo {
            color: #20344d;
            font-family: Georgia, serif;
            font-size: 30px;
            font-weight: bold;
        }

        .logo span {
            color: #a16e50;
        }

        .subtitle {
            margin: 12px 0 32px;
            color: #777;
        }

        .pilihan-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .pilihan-card {
            padding: 35px 25px;
            border: 1px solid #e8e3dc;
            border-radius: 20px;
            background: white;
            color: #20344d;
            text-decoration: none;
            transition: transform .25s, box-shadow .25s;
        }

        .pilihan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(32, 52, 77, .12);
        }

        .ikon {
            width: 65px;
            height: 65px;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: #20344d;
            color: white;
            font-size: 28px;
        }

        .pilihan-card h2 {
            margin: 0 0 12px;
            font-family: Georgia, serif;
        }

        .pilihan-card p {
            min-height: 42px;
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }

        .label {
            display: inline-block;
            margin-top: 15px;
            padding: 11px 18px;
            border-radius: 9px;
            background: #20344d;
            color: white;
            font-size: 13px;
            font-weight: bold;
        }

        .kembali {
            display: inline-block;
            margin-top: 28px;
            color: #20344d;
            text-decoration: none;
            font-size: 14px;
        }

        @media (max-width: 560px) {
            .pilihan-grid {
                grid-template-columns: 1fr;
            }

            .pilihan-card {
                padding: 26px 20px;
            }
        }
    </style>
</head>

<body>

<div class="pilihan-container">

    <div class="logo">STELLA<span>.</span></div>

    <h1>Pilih Jenis Login</h1>

    <p class="subtitle">
        Silakan pilih akses sesuai dengan akun kamu.
    </p>

    <div class="pilihan-grid">

        <a href="login.php?jenis=admin" class="pilihan-card">
            <div class="ikon">♙</div>

            <h2>Login Admin</h2>

            <p>
                Kelola koleksi buku, anggota, dan permintaan peminjaman.
            </p>

            <span class="label">Masuk sebagai Admin →</span>
        </a>

        <a href="tamu.php" class="pilihan-card">
            <div class="ikon">▤</div>

            <h2>Login Tamu</h2>

            <p>
                Jelajahi koleksi buku dan ajukan peminjaman.
            </p>

            <span class="label">Masuk sebagai Tamu →</span>
        </a>

    </div>

    <a href="index.php" class="kembali">← Kembali ke Beranda</a>

</div>

</body>
</html>