<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$pesan = "";
$jenis_pesan = "success";
$edit = null;

// Hapus anggota
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $cek = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM peminjaman WHERE id_anggota = ?"
    );
    mysqli_stmt_bind_param($cek, "i", $id);
    mysqli_stmt_execute($cek);
    $hasil = mysqli_stmt_get_result($cek);
    $data_cek = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($cek);

    if ((int)$data_cek['total'] > 0) {
        $pesan = "Anggota tidak dapat dihapus karena memiliki riwayat peminjaman.";
        $jenis_pesan = "error";
    } else {
        $stmtHapus = mysqli_prepare(
            $conn,
            "DELETE FROM anggota WHERE id_anggota = ?"
        );
        mysqli_stmt_bind_param($stmtHapus, "i", $id);

        if (
            mysqli_stmt_execute($stmtHapus) &&
            mysqli_stmt_affected_rows($stmtHapus) > 0
        ) {
            $pesan = "Data anggota berhasil dihapus.";
        } else {
            $pesan = "Data anggota gagal dihapus atau tidak ditemukan.";
            $jenis_pesan = "error";
        }

        mysqli_stmt_close($stmtHapus);
    }
}

// Tambah dan edit anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $nim = trim($_POST['nim'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $id = (int)($_POST['id_anggota'] ?? 0);

    if ($nim === '' || $nama === '') {
        $pesan = "NIM dan nama wajib diisi.";
        $jenis_pesan = "error";
    } else {
        $cek = mysqli_prepare(
            $conn,
            "SELECT id_anggota
             FROM anggota
             WHERE nim = ? AND id_anggota != ?"
        );

        mysqli_stmt_bind_param($cek, "si", $nim, $id);
        mysqli_stmt_execute($cek);
        $hasil = mysqli_stmt_get_result($cek);
        $duplikat = mysqli_num_rows($hasil) > 0;
        mysqli_stmt_close($cek);

        if ($duplikat) {
            $pesan = "NIM tersebut sudah terdaftar.";
            $jenis_pesan = "error";

        } elseif ($aksi === 'tambah') {
            $stmtTambah = mysqli_prepare(
                $conn,
                "INSERT INTO anggota (nim, nama, jurusan, alamat, no_hp)
                 VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmtTambah,
                "sssss",
                $nim,
                $nama,
                $jurusan,
                $alamat,
                $no_hp
            );

            if (mysqli_stmt_execute($stmtTambah)) {
                $pesan = "Anggota berhasil ditambahkan.";
            } else {
                $pesan = "Gagal menambahkan anggota.";
                $jenis_pesan = "error";
            }

            mysqli_stmt_close($stmtTambah);

        } elseif ($aksi === 'edit' && $id > 0) {
            $stmtEdit = mysqli_prepare(
                $conn,
                "UPDATE anggota
                 SET nim = ?, nama = ?, jurusan = ?, alamat = ?, no_hp = ?
                 WHERE id_anggota = ?"
            );

            mysqli_stmt_bind_param(
                $stmtEdit,
                "sssssi",
                $nim,
                $nama,
                $jurusan,
                $alamat,
                $no_hp,
                $id
            );

            if (mysqli_stmt_execute($stmtEdit)) {
                $pesan = "Data anggota berhasil diperbarui.";
            } else {
                $pesan = "Gagal memperbarui data anggota.";
                $jenis_pesan = "error";
            }

            mysqli_stmt_close($stmtEdit);
        }
    }
}

// Ambil data anggota untuk diedit
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmtEditData = mysqli_prepare(
        $conn,
        "SELECT * FROM anggota WHERE id_anggota = ?"
    );

    mysqli_stmt_bind_param($stmtEditData, "i", $id);
    mysqli_stmt_execute($stmtEditData);
    $hasil = mysqli_stmt_get_result($stmtEditData);
    $edit = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmtEditData);

    if (!$edit) {
        $pesan = "Data anggota tidak ditemukan.";
        $jenis_pesan = "error";
    }
}

// Pencarian anggota
$cari = trim($_GET['cari'] ?? '');
$kata = "%" . $cari . "%";

$stmtCari = mysqli_prepare(
    $conn,
    "SELECT *
     FROM anggota
     WHERE nim LIKE ? OR nama LIKE ? OR jurusan LIKE ?
     ORDER BY id_anggota DESC"
);

mysqli_stmt_bind_param($stmtCari, "sss", $kata, $kata, $kata);
mysqli_stmt_execute($stmtCari);
$anggota = mysqli_stmt_get_result($stmtCari);

// Hitung jumlah anggota
$hitung = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM anggota"
);

$total_anggota = mysqli_fetch_assoc($hitung)['total'];

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Anggota | Stella Library</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --navy: #17243a;
    --navy2: #253853;
    --gold: #e6c477;
    --cream: #f6f1e8;
    --white: #ffffff;
    --text: #243044;
    --muted: #7b8493;
    --border: #ece5d9;
    --green: #236b37;
    --red: #a52e24;
    --blue: #285a91;
}

body {
    font-family: Arial, sans-serif;
    background: var(--cream);
    color: var(--text);
}

/* SIDEBAR - DISESUAIKAN DENGAN DATA PEMINJAMAN */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    overflow-y: auto;
    background: linear-gradient(180deg, #17243a 0%, #253853 100%);
    color: white;
    padding: 28px 18px;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: var(--gold);
    margin: 0 10px 8px;
}

.subtitle-brand {
    font-size: 12px;
    color: #c5ccda;
    margin: 0 10px 35px;
}

.menu-title {
    font-size: 11px;
    color: #aeb8c9;
    letter-spacing: 1px;
    margin: 22px 10px 12px;
}

.menu a {
    display: block;
    text-decoration: none;
    color: #edf0f5;
    padding: 13px 12px;
    margin-bottom: 7px;
    border-radius: 8px;
    font-size: 14px;
    transition: background .2s, color .2s;
}

.menu a:hover,
.menu a.active {
    background: var(--navy2);
    color: var(--gold);
}

/* KONTEN */
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
    margin-bottom: 25px;
}

.topbar h1 {
    font-size: 27px;
    color: var(--navy);
    margin-bottom: 8px;
}

.topbar p {
    font-size: 14px;
    color: var(--muted);
}

.admin-badge {
    background: white;
    border: 1px solid var(--border);
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    white-space: nowrap;
}

/* KARTU */
.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 23px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px #14233708;
}

.card h2 {
    font-size: 19px;
    color: var(--navy);
    margin-bottom: 20px;
}

/* STATISTIK */
.stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.stats-label {
    color: var(--muted);
    font-size: 13px;
}

.stats strong {
    display: block;
    font-size: 30px;
    color: var(--navy);
    margin-top: 8px;
}

/* FORM */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.field label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: bold;
    color: var(--text);
}

.field input,
.field textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d9dce2;
    border-radius: 8px;
    background: #fff;
    color: var(--text);
    font-family: inherit;
    font-size: 14px;
    transition: border-color .2s, box-shadow .2s;
}

.field input:focus,
.field textarea:focus,
.search-form input:focus {
    outline: none;
    border-color: #c49a4a;
    box-shadow: 0 0 0 3px #e6c47730;
}

.field textarea {
    min-height: 90px;
    resize: vertical;
}

.full {
    grid-column: 1 / -1;
}

/* TOMBOL */
.btn {
    display: inline-block;
    border: 0;
    border-radius: 8px;
    padding: 11px 15px;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    transition: transform .15s, opacity .15s;
}

.btn:hover {
    opacity: .86;
    transform: translateY(-1px);
}

.btn-primary {
    background: var(--navy);
    color: white;
}

.btn-gold {
    background: var(--gold);
    color: var(--navy);
}

.btn-secondary {
    background: #eee9df;
    color: var(--navy);
}

.btn-danger {
    background: #fbe8e8;
    color: var(--red);
}

/* PENCARIAN */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
}

.search-form input {
    flex: 1;
    min-width: 0;
    padding: 12px;
    border: 1px solid #d9dce2;
    border-radius: 8px;
    font-size: 14px;
}

/* TABEL */
.table-wrap {
    width: 100%;
    overflow-x: auto;
    border-radius: 9px;
}

table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
}

th {
    padding: 15px 12px;
    background: var(--navy);
    color: white;
    text-align: left;
    font-size: 12px;
    white-space: nowrap;
}

td {
    padding: 15px 12px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

tbody tr:hover {
    background: #fcfaf6;
}

tbody tr:last-child td {
    border-bottom: none;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.actions .btn {
    padding: 9px 11px;
    font-size: 12px;
}

.empty {
    padding: 30px;
    color: var(--muted);
    text-align: center;
}

/* NOTIFIKASI */
.notice {
    padding: 14px 16px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 14px;
}

.notice.success {
    background: #e2f3e8;
    color: var(--green);
}

.notice.error {
    background: #fbe8e8;
    color: var(--red);
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 20px 15px;
    }

    .subtitle-brand {
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
        margin: 0;
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

    .admin-badge {
        white-space: normal;
    }

    .card {
        padding: 18px;
    }
}

@media (max-width: 550px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .search-form {
        flex-wrap: wrap;
    }

    .search-form input {
        flex-basis: 100%;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">✦ Stella Library</div>
    <div class="subtitle-brand">Sistem Informasi Perpustakaan</div>

    <div class="menu-title">MENU UTAMA</div>

    <nav class="menu">
        <a href="dashboard.php">⌂ Dashboard</a>
        <a href="buku.php">▤ Kelola Buku</a>
        <a href="peminjaman.php">⇄ Data Peminjaman</a>
        <a href="anggota.php" class="active">♙ Data Anggota</a>
        <a href="../index.php">⌂ Halaman Utama</a>
        <a href="../logout.php">⇥ Logout</a>
    </nav>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Data Anggota</h1>
            <p>Kelola data anggota Stella Library.</p>
        </div>

        <div class="admin-badge">
            🛡️ Administrator:
            <strong><?= e($namaAdmin) ?></strong>
        </div>
    </header>

    <?php if ($pesan !== ''): ?>
        <div class="notice <?= $jenis_pesan === 'error' ? 'error' : 'success' ?>">
            <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <section class="card stats">
        <div>
            <div class="stats-label">Total anggota terdaftar</div>
            <strong><?= (int)$total_anggota ?></strong>
        </div>
        <div>✦ Data anggota perpustakaan</div>
    </section>

    <section class="card" id="form-anggota">
        <h2><?= $edit ? '✎ Edit Anggota' : '+ Tambah Anggota Baru' ?></h2>

        <form method="POST" action="anggota.php<?= $edit ? '?edit=' . (int)$edit['id_anggota'] : '' ?>">
            <input type="hidden" name="aksi" value="<?= $edit ? 'edit' : 'tambah' ?>">

            <?php if ($edit): ?>
                <input type="hidden" name="id_anggota" value="<?= (int)$edit['id_anggota'] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label>NIM *</label>
                    <input
                        type="text"
                        name="nim"
                        maxlength="20"
                        required
                        value="<?= e($edit['nim'] ?? '') ?>"
                        placeholder="Masukkan NIM"
                    >
                </div>

                <div class="field">
                    <label>Nama Lengkap *</label>
                    <input
                        type="text"
                        name="nama"
                        maxlength="50"
                        required
                        value="<?= e($edit['nama'] ?? '') ?>"
                        placeholder="Nama lengkap anggota"
                    >
                </div>

                <div class="field">
                    <label>Jurusan</label>
                    <input
                        type="text"
                        name="jurusan"
                        maxlength="50"
                        value="<?= e($edit['jurusan'] ?? '') ?>"
                        placeholder="Contoh: Teknik Informatika"
                    >
                </div>

                <div class="field">
                    <label>Nomor HP</label>
                    <input
                        type="text"
                        name="no_hp"
                        maxlength="15"
                        value="<?= e($edit['no_hp'] ?? '') ?>"
                        placeholder="Contoh: 08xxxxxxxxxx"
                    >
                </div>

                <div class="field full">
                    <label>Alamat</label>
                    <textarea
                        name="alamat"
                        placeholder="Alamat anggota"
                    ><?= e($edit['alamat'] ?? '') ?></textarea>
                </div>

                <div class="full">
                    <button class="btn btn-gold" type="submit">
                        <?= $edit ? 'Simpan Perubahan' : '+ Tambah Anggota' ?>
                    </button>

                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="anggota.php">Batal</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>♙ Daftar Anggota</h2>

        <form method="GET" class="search-form" action="anggota.php">
            <input
                type="text"
                name="cari"
                value="<?= e($cari) ?>"
                placeholder="Cari berdasarkan NIM, nama, atau jurusan..."
            >

            <button class="btn btn-primary" type="submit">Cari</button>

            <?php if ($cari !== ''): ?>
                <a href="anggota.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>Jurusan</th>
                        <th>Nomor HP</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($anggota) > 0): ?>
                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($anggota)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($row['nim']) ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td><?= e($row['jurusan'] ?: '-') ?></td>
                            <td><?= e($row['no_hp'] ?: '-') ?></td>
                            <td><?= e($row['alamat'] ?: '-') ?></td>
                            <td>
                                <div class="actions">
                                    <a
                                        class="btn btn-gold"
                                        href="anggota.php?edit=<?= (int)$row['id_anggota'] ?>#form-anggota"
                                    >Edit</a>

                                    <a
                                        class="btn btn-danger"
                                        href="anggota.php?hapus=<?= (int)$row['id_anggota'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus anggota ini?')"
                                    >Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty">
                            Belum ada data anggota yang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer style="text-align:center;color:var(--muted);font-size:12px;padding:10px 0 25px;">
        Stella Library &copy; <?= date('Y') ?> — Sistem Informasi Perpustakaan
    </footer>

</main>

</body>
</html>

<?php
mysqli_stmt_close($stmtCari);
?>
