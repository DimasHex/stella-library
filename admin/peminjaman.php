```php
<?php
session_start();
require_once "../koneksi.php";

// Cek hak akses admin.
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$error = '';

// Token keamanan formulir.
if (empty($_SESSION['csrf_peminjaman'])) {
    $_SESSION['csrf_peminjaman'] = bin2hex(random_bytes(32));
}

// Proses aksi peminjaman.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_peminjaman'], $token)) {

        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';

    } else {
        $id_pinjam = filter_input(
            INPUT_POST,
            'id_pinjam',
            FILTER_VALIDATE_INT
        );

        $aksi = $_POST['aksi'] ?? '';

        if (
            !$id_pinjam ||
            !in_array($aksi, ['setujui', 'tolak', 'kembalikan'], true)
        ) {
            $error = 'Data permintaan tidak valid.';

        } else {
            mysqli_begin_transaction($conn);

            try {
                // Ambil data peminjaman dan kunci barisnya.
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id_buku, status
                     FROM peminjaman
                     WHERE id_pinjam = ?
                     FOR UPDATE"
                );

                if (!$stmt) {
                    throw new Exception('Gagal membaca data peminjaman.');
                }

                mysqli_stmt_bind_param($stmt, 'i', $id_pinjam);
                mysqli_stmt_execute($stmt);

                $hasil = mysqli_stmt_get_result($stmt);
                $pinjam = mysqli_fetch_assoc($hasil);

                mysqli_stmt_close($stmt);

                if (!$pinjam) {
                    throw new Exception('Data peminjaman tidak ditemukan.');
                }

                $status = strtolower(trim($pinjam['status']));
                $id_buku = (int)$pinjam['id_buku'];

                // SETUJUI PEMINJAMAN.
                if ($aksi === 'setujui') {
                    if ($status !== 'menunggu') {
                        throw new Exception(
                            'Hanya peminjaman berstatus menunggu yang bisa disetujui.'
                        );
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE buku
                         SET stok = stok - 1
                         WHERE id_buku = ? AND stok > 0"
                    );

                    if (!$stmt) {
                        throw new Exception('Gagal memproses stok buku.');
                    }

                    mysqli_stmt_bind_param($stmt, 'i', $id_buku);
                    mysqli_stmt_execute($stmt);

                    $berhasil = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);

                    if ($berhasil !== 1) {
                        throw new Exception(
                            'Stok buku habis atau data buku tidak ditemukan.'
                        );
                    }

                    $status_baru = 'disetujui';

                // TOLAK PEMINJAMAN.
                } elseif ($aksi === 'tolak') {
                    if ($status !== 'menunggu') {
                        throw new Exception(
                            'Hanya peminjaman berstatus menunggu yang bisa ditolak.'
                        );
                    }

                    $status_baru = 'ditolak';

                // TANDAI BUKU SUDAH DIKEMBALIKAN.
                } else {
                    if ($status !== 'disetujui') {
                        throw new Exception(
                            'Hanya peminjaman yang disetujui yang bisa dikembalikan.'
                        );
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE buku
                         SET stok = stok + 1
                         WHERE id_buku = ?"
                    );

                    if (!$stmt) {
                        throw new Exception('Gagal memperbarui stok buku.');
                    }

                    mysqli_stmt_bind_param($stmt, 'i', $id_buku);
                    mysqli_stmt_execute($stmt);

                    $berhasil = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);

                    if ($berhasil !== 1) {
                        throw new Exception('Gagal memperbarui stok buku.');
                    }

                    $status_baru = 'dikembalikan';
                }

                // Simpan status peminjaman.
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE peminjaman
                     SET status = ?
                     WHERE id_pinjam = ?"
                );

                if (!$stmt) {
                    throw new Exception('Gagal memperbarui status peminjaman.');
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    'si',
                    $status_baru,
                    $id_pinjam
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);

                header(
                    'Location: peminjaman.php?berhasil=' .
                    urlencode($status_baru)
                );
                exit;

            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
}

// Pesan keberhasilan.
if (isset($_GET['berhasil'])) {
    $daftar_pesan = [
        'disetujui' => 'Peminjaman berhasil disetujui.',
        'ditolak' => 'Permintaan peminjaman berhasil ditolak.',
        'dikembalikan' => 'Buku berhasil ditandai sudah dikembalikan.'
    ];

    $status_pesan = $_GET['berhasil'];

    if (isset($daftar_pesan[$status_pesan])) {
        $pesan = $daftar_pesan[$status_pesan];
    }
}

// Pencarian data peminjaman.
$cari = trim($_GET['cari'] ?? '');

$sql = "SELECT
            p.id_pinjam,
            p.id_anggota,
            p.id_buku,
            p.tanggal_pinjam,
            p.tanggal_jatuh_tempo,
            p.status,
            a.nama AS nama_anggota,
            b.judul AS judul_buku
        FROM peminjaman p
        LEFT JOIN anggota a
            ON p.id_anggota = a.id_anggota
        LEFT JOIN buku b
            ON p.id_buku = b.id_buku";

if ($cari !== '') {
    $sql .= " WHERE
                a.nama LIKE ?
                OR b.judul LIKE ?
                OR p.status LIKE ?";
}

$sql .= " ORDER BY p.id_pinjam DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die('Query data peminjaman gagal. Periksa struktur tabel database.');
}

if ($cari !== '') {
    $kata = '%' . $cari . '%';

    mysqli_stmt_bind_param(
        $stmt,
        'sss',
        $kata,
        $kata,
        $kata
    );
}

mysqli_stmt_execute($stmt);
$data_peminjaman = mysqli_stmt_get_result($stmt);

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Peminjaman | Stella Library</title>

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

/* SIDEBAR */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    overflow-y: auto;
    background: var(--navy);
    color: white;
    padding: 28px 18px;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: var(--gold);
    margin: 0 10px 8px;
}

.subtitle {
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
}

.menu a:hover,
.menu a.active {
    background: var(--navy2);
    color: var(--gold);
}

/* KONTEN UTAMA */
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

/* PENCARIAN */
.search-form {
    display: flex;
    gap: 10px;
}

.search-form input {
    flex: 1;
    min-width: 0;
    padding: 12px;
    border: 1px solid #d9dce2;
    border-radius: 8px;
    font-size: 14px;
}

.search-form input:focus {
    outline: 2px solid var(--gold);
}

/* TOMBOL */
.btn {
    display: inline-block;
    border: 0;
    border-radius: 8px;
    padding: 10px 13px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
}

.btn-search {
    background: var(--navy);
    color: white;
}

.btn-reset {
    background: #eee9df;
    color: var(--navy);
}

.btn-approve {
    background: #e2f3e8;
    color: var(--green);
}

.btn-reject {
    background: #fbe8e8;
    color: var(--red);
}

.btn-return {
    background: #e4edf9;
    color: var(--blue);
}

.btn:hover {
    opacity: 0.82;
}

/* TABEL */
.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
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
}

tbody tr:hover {
    background: #fcfaf6;
}

/* STATUS */
.status {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}

.status.menunggu {
    background: #fff2d5;
    color: #936615;
}

.status.disetujui {
    background: #e2f3e8;
    color: var(--green);
}

.status.ditolak {
    background: #fbe8e8;
    color: var(--red);
}

.status.dikembalikan {
    background: #e4edf9;
    color: var(--blue);
}

/* AKSI */
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.actions form {
    margin: 0;
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

.success {
    background: #e2f3e8;
    color: var(--green);
}

.danger {
    background: #fbe8e8;
    color: var(--red);
}

/* FOOTER */
.footer {
    text-align: center;
    color: var(--muted);
    font-size: 12px;
    padding: 10px 0 25px;
}

/* RESPONSIVE */
@media (max-width: 900px) {
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

    .card {
        padding: 16px;
    }
}

@media (max-width: 550px) {
    .search-form {
        flex-wrap: wrap;
    }

    .search-form input {
        flex-basis: 100%;
    }

    .admin-badge {
        white-space: normal;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">✦ Stella Library</div>
    <div class="subtitle">Sistem Informasi Perpustakaan</div>

    <div class="menu-title">MENU UTAMA</div>

    <nav class="menu">
        <a href="dashboard.php">⌂ Dashboard</a>
        <a href="buku.php">▤ Kelola Buku</a>
        <a href="peminjaman.php" class="active">⇄ Data Peminjaman</a>
        <a href="anggota.php">♙ Data Anggota</a>
        <a href="../index.php">⌂ Halaman Utama</a>
        <a href="../logout.php">⇥ Logout</a>
    </nav>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Data Peminjaman</h1>
            <p>Kelola permintaan peminjaman dan pengembalian buku.</p>
        </div>

        <div class="admin-badge">
            🛡️ Administrator:
            <strong><?= aman($namaAdmin) ?></strong>
        </div>
    </header>

    <?php if ($pesan !== ''): ?>
        <div class="notice success">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice danger">
            <?= aman($error) ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>🔍 Cari Peminjaman</h2>

        <form class="search-form" method="GET" action="peminjaman.php">
            <input
                type="text"
                name="cari"
                placeholder="Cari nama anggota, judul buku, atau status..."
                value="<?= aman($cari) ?>"
            >

            <button class="btn btn-search" type="submit">
                Cari
            </button>

            <a class="btn btn-reset" href="peminjaman.php">
                Reset
            </a>
        </form>
    </section>

    <section class="card">
        <h2>📋 Daftar Peminjaman Buku</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Anggota</th>
                        <th>Judul Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($data_peminjaman) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($data_peminjaman)): ?>

                        <?php
                        $status_asli = strtolower(
                            trim($row['status'] ?? '')
                        );

                        $kelas_status = preg_replace(
                            '/[^a-z]/',
                            '',
                            $status_asli
                        );

                        $nama_status = ucfirst($status_asli);
                        ?>

                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?= aman(
                                    $row['nama_anggota']
                                    ?: 'Anggota tidak ditemukan'
                                ) ?>
                            </td>

                            <td>
                                <?= aman(
                                    $row['judul_buku']
                                    ?: 'Buku tidak ditemukan'
                                ) ?>
                            </td>

                            <td>
                                <?= aman($row['tanggal_pinjam']) ?>
                            </td>

                            <td>
                                <?= aman($row['tanggal_jatuh_tempo']) ?>
                            </td>

                            <td>
                                <span class="status <?= aman($kelas_status) ?>">
                                    <?= aman($nama_status) ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions">

                                    <?php if ($status_asli === 'menunggu'): ?>

                                        <form method="POST" action="peminjaman.php">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= aman($_SESSION['csrf_peminjaman']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id_pinjam"
                                                value="<?= (int)$row['id_pinjam'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="setujui"
                                            >

                                            <button
                                                class="btn btn-approve"
                                                type="submit"
                                                onclick="return confirm('Setujui peminjaman buku ini?')"
                                            >
                                                ✓ Setujui
                                            </button>
                                        </form>

                                        <form method="POST" action="peminjaman.php">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= aman($_SESSION['csrf_peminjaman']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id_pinjam"
                                                value="<?= (int)$row['id_pinjam'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="tolak"
                                            >

                                            <button
                                                class="btn btn-reject"
                                                type="submit"
                                                onclick="return confirm('Tolak permintaan peminjaman ini?')"
                                            >
                                                ✕ Tolak
                                            </button>
                                        </form>

                                    <?php elseif ($status_asli === 'disetujui'): ?>

                                        <form method="POST" action="peminjaman.php">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= aman($_SESSION['csrf_peminjaman']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id_pinjam"
                                                value="<?= (int)$row['id_pinjam'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="kembalikan"
                                            >

                                            <button
                                                class="btn btn-return"
                                                type="submit"
                                                onclick="return confirm('Tandai buku ini sudah dikembalikan?')"
                                            >
                                                📚 Dikembalikan
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <span>—</span>

                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            Belum ada data peminjaman yang ditemukan.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </section>

    <footer class="footer">
        Stella Library &copy; <?= date('Y') ?>
        — Sistem Informasi Perpustakaan
    </footer>

</main>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
```