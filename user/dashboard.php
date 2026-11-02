<?php
session_start();
require_once '../koneksi.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'anggota'
) {
    header('Location: ../login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Ambil data anggota
$stmt = $conn->prepare(
    "SELECT id_anggota, nim, nama, jurusan
     FROM anggota
     WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$anggota = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$anggota) {
    die("Data anggota belum ditemukan. Silakan hubungi admin.");
}


$stmt = $conn->prepare(
    "SELECT p.tanggal_pinjam,
            p.tanggal_jatuh_tempo,
            p.status,
            b.judul
     FROM peminjaman p
     JOIN buku b ON p.id_buku = b.id_buku
     WHERE p.id_anggota = ?
     ORDER BY p.id_pinjam DESC"
);
$stmt->bind_param("i", $anggota['id_anggota']);
$stmt->execute();
$peminjaman = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya - Stella Library</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8f5ed;
            color: #172554;
        }

        nav {
            background: #101d3a;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 16px;
        }

        .container {
            max-width: 1000px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 14px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px #0000000d;
        }

        h1, h2 {
            color: #172554;
        }

        .info p {
            margin: 12px 0;
        }

        .btn {
            display: inline-block;
            background: #c59b45;
            color: #101d3a;
            padding: 11px 17px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #101d3a;
            color: white;
        }

        .status {
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #666;
        }
    </style>
</head>

<body>

<nav>
    <strong>Stella Library</strong>

    <div>
        <a href="../index.php">Beranda</a>
        <a href="pinjam.php">Pinjam Buku</a>
        <a href="../logout.php">Keluar</a>
    </div>
</nav>

<div class="container">

    <h1>Akun Saya</h1>
    <p>Selamat datang, <?= htmlspecialchars($anggota['nama']) ?>!</p>

    <div class="card info">
        <h2>Informasi Anggota</h2>

        <p>
            <strong>Nama:</strong>
            <?= htmlspecialchars($anggota['nama']) ?>
        </p>

        <p>
            <strong>NIM:</strong>
            <?= htmlspecialchars($anggota['nim']) ?>
        </p>

        <p>
            <strong>Jurusan:</strong>
            <?= htmlspecialchars($anggota['jurusan']) ?>
        </p>

        <br>
        <a class="btn" href="../index.php">Cari Buku</a>
    </div>

    <div class="card">
        <h2>Riwayat Peminjaman</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Judul Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Pengembalian</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($peminjaman->num_rows > 0): ?>
                    <?php while ($row = $peminjaman->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['judul']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['tanggal_pinjam']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['tanggal_jatuh_tempo']) ?>
                            </td>

                            <td class="status">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            Kamu belum memiliki riwayat peminjaman buku.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> Stella Library
</footer>

</body>
</html>

<?php
$stmt->close();
?>
