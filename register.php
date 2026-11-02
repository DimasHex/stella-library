<?php
session_start();
require_once "koneksi.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nim = trim($_POST["nim"] ?? "");
    $nama = trim($_POST["nama"] ?? "");
    $jurusan = trim($_POST["jurusan"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";

    if (
        $nim === "" || $nama === "" || $jurusan === "" ||
        $username === "" || $password === "" || $konfirmasi === ""
    ) {
        $error = "Mohon lengkapi semua kolom yang wajib diisi.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } else {
        $cek = $conn->prepare(
            "SELECT id FROM users WHERE username = ?
             UNION
             SELECT id_anggota FROM anggota WHERE nim = ?"
        );

        $cek->bind_param("ss", $username, $nim);
        $cek->execute();
        $hasil = $cek->get_result();

        if ($hasil->num_rows > 0) {
            $error = "Username atau NIM sudah terdaftar.";
        } else {
            $conn->begin_transaction();

            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = "anggota";

                $stmtUser = $conn->prepare(
                    "INSERT INTO users (username, password, nama_lengkap, role)
                     VALUES (?, ?, ?, ?)"
                );
                $stmtUser->bind_param("ssss", $username, $hash, $nama, $role);
                $stmtUser->execute();

                $userId = $conn->insert_id;

                $stmtAnggota = $conn->prepare(
                    "INSERT INTO anggota
                     (user_id, nim, nama, jurusan, alamat, no_hp)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmtAnggota->bind_param(
                    "isssss",
                    $userId, $nim, $nama, $jurusan, $alamat, $no_hp
                );
                $stmtAnggota->execute();

                $conn->commit();
                $success = "Pendaftaran berhasil! Silakan login menggunakan akun kamu.";
            } catch (Throwable $e) {
                $conn->rollback();
                $error = "Pendaftaran gagal. Silakan periksa data dan struktur database.";
            }
        }

        $cek->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota | Stella Library</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #f7f3e9;
            color: #17233d;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 16px;
        }

        .container {
            width: 100%;
            max-width: 560px;
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(23, 35, 61, 0.12);
        }

        .brand {
            text-align: center;
            color: #b58b3b;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 3px;
        }

        h1 {
            text-align: center;
            margin: 10px 0;
            color: #17233d;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .field { margin-bottom: 15px; }
        .full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd8cc;
            border-radius: 8px;
            font: inherit;
            font-size: 14px;
        }

        input:focus, textarea:focus {
            outline: 2px solid #d7bd7a;
            border-color: #b58b3b;
        }

        textarea {
            resize: vertical;
            min-height: 75px;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #17233d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 5px;
        }

        button:hover { background: #263b62; }

        .message {
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 18px;
        }

        .error { background: #fde8e7; color: #a12622; }
        .success { background: #e5f6e9; color: #216e39; }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        a { color: #a47a28; font-weight: bold; text-decoration: none; }
        a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .container { padding: 24px 18px; }
            .grid { grid-template-columns: 1fr; gap: 0; }
            .full { grid-column: auto; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="brand">STELLA LIBRARY</div>
    <h1>Daftar Anggota</h1>
    <p class="subtitle">Buat akun untuk mulai meminjam buku.</p>

    <?php if ($error !== ""): ?>
        <div class="message error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="message success">
            <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
            <br><br><a href="login.php">Login sekarang</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="grid">
                <div class="field">
                    <label for="nim">NIM *</label>
                    <input id="nim" name="nim" required maxlength="20"
                           value="<?= htmlspecialchars($_POST['nim'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="nama">Nama lengkap *</label>
                    <input id="nama" name="nama" required maxlength="50"
                           value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field full">
                    <label for="jurusan">Jurusan *</label>
                    <input id="jurusan" name="jurusan" required maxlength="50"
                           value="<?= htmlspecialchars($_POST['jurusan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field full">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat"><?= htmlspecialchars($_POST['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="field full">
                    <label for="no_hp">Nomor HP</label>
                    <input id="no_hp" name="no_hp" maxlength="15"
                           value="<?= htmlspecialchars($_POST['no_hp'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="username">Username *</label>
                    <input id="username" name="username" required maxlength="50"
                           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="password">Password *</label>
                    <input id="password" name="password" type="password"
                           required minlength="8" autocomplete="new-password">
                </div>

                <div class="field full">
                    <label for="konfirmasi">Konfirmasi password *</label>
                    <input id="konfirmasi" name="konfirmasi" type="password"
                           required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <button type="submit">Buat Akun</button>
        </form>
    <?php endif; ?>

    <div class="footer">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>
</body>
</html>
```