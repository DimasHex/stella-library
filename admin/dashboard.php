<?php
session_start();
require_once '../koneksi.php';


if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../login.php');
    exit;
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

function jumlahData($conn, $sql) {
    $hasil = mysqli_query($conn, $sql);

    if (!$hasil) {
        return 0;
    }

    $baris = mysqli_fetch_assoc($hasil);
    return (int)($baris['total'] ?? 0);
}


$totalBuku = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM buku"
);

$totalAnggota = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM anggota"
);

$totalPeminjaman = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman"
);

$bukuDipinjam = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE status = 'Dipinjam'"
);

$bukuTerlambat = jumlahData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE status = 'Terlambat'"
);


$bukuTerbaru = mysqli_query(
    $conn,
    "SELECT id_buku, judul, pengarang, kategori, stok
     FROM buku
     ORDER BY id_buku DESC
     LIMIT 5"
);


$peminjamanTerbaru = mysqli_query(
    $conn,
    "SELECT
        p.id_pinjam,
        p.tanggal_pinjam,
        p.tanggal_jatuh_tempo,
        p.status,
        b.judul,
        a.nama
     FROM peminjaman p
     LEFT JOIN buku b ON p.id_buku = b.id_buku
     LEFT JOIN anggota a ON p.id_anggota = a.id_anggota
     ORDER BY p.id_pinjam DESC
     LIMIT 5"
);
?><!DOCTYPE html><html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Dashboard Admin | Stella Library</title>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --navy: #17243a;
        --navy-light: #253853;
        --gold: #e6c477;
        --cream: #f6f1e8;
        --white: #ffffff;
        --text: #243044;
        --muted: #7b8493;
        --border: #ece5d9;
    }

    body {
        font-family: Arial, sans-serif;
        background: var(--cream);
        color: var(--text);
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        padding: 28px 18px;
        background: var(--navy);
        color: var(--white);
    }

    .logo {
        color: var(--gold);
        font-size: 24px;
        font-weight: bold;
        margin: 0 10px 8px;
    }

    .subtitle {
        color: #c5ccda;
        font-size: 12px;
        margin: 0 10px 35px;
    }

    .menu-title {
        color: #aeb8c9;
        font-size: 11px;
        letter-spacing: 1px;
        margin: 22px 10px 12px;
        text-transform: uppercase;
    }

    .menu a {
        display: block;
        color: #edf0f5;
        text-decoration: none;
        font-size: 14px;
        padding: 13px 12px;
        margin-bottom: 7px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .menu a:hover,
    .menu a.active {
        background: var(--navy-light);
        color: var(--gold);
    }

    .main {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 28px;
    }

    .topbar h1 {
        font-size: 27px;
        margin-bottom: 8px;
    }

    .topbar p {
        color: var(--muted);
        font-size: 14px;
    }

    .admin-badge {
        background: var(--white);
        border: 1px solid var(--border);
        padding: 12px 17px;
        border-radius: 10px;
        font-size: 13px;
    }

    .welcome {
        padding: 28px;
        margin-bottom: 25px;
        border-radius: 15px;
        background: linear-gradient(120deg, #17243a, #304664);
        color: var(--white);
    }

    .welcome h2 {
        color: var(--gold);
        margin-bottom: 12px;
        font-size: 23px;
    }

    .welcome p {
        color: #e2e7ef;
        line-height: 1.8;
        font-size: 14px;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 17px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 13px;
        padding: 22px;
        box-shadow: 0 4px 15px rgba(20, 35, 55, 0.04);
    }

    .stat-icon {
        font-size: 25px;
        margin-bottom: 15px;
    }

    .stat-label {
        font-size: 13px;
        color: var(--muted);
        margin-bottom: 10px;
    }

    .stat-number {
        font-size: 30px;
        font-weight: bold;
        color: var(--navy);
    }

    .stat-note {
        font-size: 11px;
        color: var(--muted);
        margin-top: 9px;
    }

    .section {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(20, 35, 55, 0.03);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .section-header h2 {
        font-size: 18px;
    }

    .section-header a {
        color: #8c6a24;
        text-decoration: none;
        font-size: 13px;
        font-weight: bold;
    }

    .table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 570px;
    }

    th, td {
        text-align: left;
        padding: 14px 12px;
        border-bottom: 1px solid #f0ece5;
        font-size: 13px;
    }

    th {
        background: #faf7f1;
        color: #6c7481;
        font-weight: bold;
        white-space: nowrap;
    }

    td {
        color: var(--text);
    }

    tbody tr:hover {
        background: #fcfaf6;
    }

    .badge {
        display: inline-block;
        padding: 6px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: bold;
        white-space: nowrap;
    }

    .dipinjam {
        background: #e5efff;
        color: #2858a5;
    }

    .dikembalikan {
        background: #e2f6e8;
        color: #24733b;
    }

    .terlambat {
        background: #ffe8e5;
        color: #b3372a;
    }

    .status-lain {
        background: #f0edf5;
        color: #63547d;
    }

    .empty {
        text-align: center;
        color: var(--muted);
        padding: 28px 12px;
    }

    .footer {
        text-align: center;
        color: var(--muted);
        font-size: 12px;
        padding: 12px 0 25px;
    }

    @media (max-width: 1100px) {
        .stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 750px) {
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
            padding: 20px 15px;
        }

        .subtitle {
            margin-bottom: 18px;
        }

        .menu-title {
            display: none;
        }

        .menu {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .menu a {
            margin-bottom: 0;
            padding: 10px;
        }

        .main {
            margin-left: 0;
            padding: 18px;
        }

        .topbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .stat-card {
            padding: 16px;
        }

        .stat-number {
            font-size: 25px;
        }

        .welcome {
            padding: 22px;
        }
    }

    @media (max-width: 380px) {
        .stats {
            grid-template-columns: 1fr;
        }
    }
</style>

</head><body><aside class="sidebar">
    <div class="logo">✦ Stella Library</div>
    <div class="subtitle">Sistem Informasi Perpustakaan</div><div class="menu-title">Menu Utama</div>

<nav class="menu">
    <a href="dashboard.php" class="active">⌂ Dashboard</a>
    <a href="buku.php">▤ Kelola Buku</a>
    <a href="peminjaman.php">⇄ Data Peminjaman</a>
    <a href="anggota.php">♙ Data Anggota</a>
    <a href="../index.php">⌂ Halaman Utama</a>
    <a href="../logout.php">⇥ Logout</a>
</nav>

</aside><main class="main"><header class="topbar">
    <div>
        <h1>Dashboard Admin</h1>
        <p>Ringkasan aktivitas Stella Library.</p>
    </div>

    <div class="admin-badge">
        Administrator:
        <strong><?= aman($namaAdmin) ?></strong>
    </div>
</header>

<section class="welcome">
    <h2>Selamat datang, <?= aman($namaAdmin) ?>! ✨</h2>
    <p>
        Selamat datang di panel pengelolaan Stella Library.
        Pantau koleksi buku, data anggota, dan transaksi
        peminjaman melalui halaman ini.
    </p>
</section>

<section class="stats">

    <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-label">Total Buku</div>
        <div class="stat-number"><?= $totalBuku ?></div>
        <div class="stat-note">Jumlah judul dalam katalog</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Total Anggota</div>
        <div class="stat-number"><?= $totalAnggota ?></div>
        <div class="stat-note">Anggota yang terdaftar</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-label">Total Peminjaman</div>
        <div class="stat-number"><?= $totalPeminjaman ?></div>
        <div class="stat-note">Seluruh transaksi tercatat</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📖</div>
        <div class="stat-label">Sedang Dipinjam</div>
        <div class="stat-number"><?= $bukuDipinjam ?></div>
        <div class="stat-note">
            <?= $bukuTerlambat ?> transaksi berstatus terlambat
        </div>
    </div>

</section>

<section class="section">
    <div class="section-header">
        <h2>📚 Buku Terbaru</h2>
        <a href="buku.php">Kelola Buku →</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Judul Buku</th>
                    <th>Pengarang</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($bukuTerbaru && mysqli_num_rows($bukuTerbaru) > 0): ?>
                <?php $no = 1; ?>
                <?php while ($buku = mysqli_fetch_assoc($bukuTerbaru)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= aman($buku['judul']) ?></strong></td>
                        <td><?= aman($buku['pengarang']) ?></td>
                        <td><?= aman($buku['kategori'] ?: 'Belum diisi') ?></td>
                        <td><?= (int)$buku['stok'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty">
                        Belum ada data buku yang dapat ditampilkan.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>🔄 Peminjaman Terbaru</h2>
        <a href="peminjaman.php">Lihat Peminjaman →</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Anggota</th>
                    <th>Judul Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($peminjamanTerbaru && mysqli_num_rows($peminjamanTerbaru) > 0): ?>
                <?php while ($pinjam = mysqli_fetch_assoc($peminjamanTerbaru)): ?>
                    <?php
                        $status = $pinjam['status'] ?? '';
                        $kelasStatus = 'status-lain';

                        if ($status === 'Dipinjam') {
                            $kelasStatus = 'dipinjam';
                        } elseif ($status === 'Dikembalikan') {
                            $kelasStatus = 'dikembalikan';
                        } elseif ($status === 'Terlambat') {
                            $kelasStatus = 'terlambat';
                        }
                    ?>
                    <tr>
                        <td>#<?= (int)$pinjam['id_pinjam'] ?></td>
                        <td><?= aman($pinjam['nama'] ?? 'Anggota tidak ditemukan') ?></td>
                        <td><?= aman($pinjam['judul'] ?? 'Buku tidak ditemukan') ?></td>
                        <td><?= aman($pinjam['tanggal_pinjam']) ?></td>
                        <td><?= aman($pinjam['tanggal_jatuh_tempo']) ?></td>
                        <td>
                            <span class="badge <?= $kelasStatus ?>">
                                <?= aman($status ?: 'Belum ada status') ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty">
                        Belum ada transaksi peminjaman.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<footer class="footer">
    Stella Library &copy; <?= date('Y') ?> — Sistem Informasi Perpustakaan
</footer>

</main></body>
</html>