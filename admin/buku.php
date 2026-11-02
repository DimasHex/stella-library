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

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$error = '';
$modeEdit = false;

$form = [
    'id_buku' => '',
    'judul' => '',
    'pengarang' => '',
    'penerbit' => '',
    'tahun_terbit' => '',
    'kategori' => '',
    'ikon' => '📚',
    'stok' => 1
];


if (empty($_SESSION['csrf_buku'])) {
    $_SESSION['csrf_buku'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_buku'], $token)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {

        $aksi = $_POST['aksi'] ?? '';

        // TAMBAH ATAU EDIT BUKU
        if ($aksi === 'simpan') {

            $id = (int)($_POST['id_buku'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $pengarang = trim($_POST['pengarang'] ?? '');
            $penerbit = trim($_POST['penerbit'] ?? '');
            $tahun = trim($_POST['tahun_terbit'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $ikon = trim($_POST['ikon'] ?? '📚');
            $stok = filter_var(
                $_POST['stok'] ?? null,
                FILTER_VALIDATE_INT
            );

            $form = [
                'id_buku' => $id,
                'judul' => $judul,
                'pengarang' => $pengarang,
                'penerbit' => $penerbit,
                'tahun_terbit' => $tahun,
                'kategori' => $kategori,
                'ikon' => $ikon,
                'stok' => $stok === false ? '' : $stok
            ];

            $modeEdit = $id > 0;

            if ($judul === '' || $pengarang === '') {
                $error = 'Judul dan pengarang wajib diisi.';
            } elseif (
                $stok === false ||
                $stok === null ||
                $stok < 0
            ) {
                $error = 'Stok harus berupa angka nol atau lebih.';
            } elseif (
                $tahun !== '' &&
                (!ctype_digit($tahun) ||
                 (int)$tahun < 1000 ||
                 (int)$tahun > 9999)
            ) {
                $error = 'Tahun terbit harus berupa tahun yang valid.';
            } elseif (mb_strlen($judul) > 100) {
                $error = 'Judul maksimal 100 karakter.';
            } elseif (mb_strlen($pengarang) > 50) {
                $error = 'Nama pengarang maksimal 50 karakter.';
            } elseif (mb_strlen($penerbit) > 100) {
                $error = 'Nama penerbit maksimal 100 karakter.';
            } elseif (mb_strlen($kategori) > 50) {
                $error = 'Kategori maksimal 50 karakter.';
            } elseif (mb_strlen($ikon) > 10) {
                $error = 'Ikon maksimal 10 karakter.';
            } else {

                $penerbitDB = $penerbit === '' ? null : $penerbit;
                $tahunDB = $tahun === '' ? null : (int)$tahun;
                $kategoriDB = $kategori === '' ? null : $kategori;
                $ikonDB = $ikon === '' ? null : $ikon;

                if ($id > 0) {
                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE buku
                         SET judul = ?, pengarang = ?, penerbit = ?,
                             tahun_terbit = ?, kategori = ?, ikon = ?, stok = ?
                         WHERE id_buku = ?"
                    );

                    if ($stmt) {
                        mysqli_stmt_bind_param(
                            $stmt,
                            'sssissii',
                            $judul,
                            $pengarang,
                            $penerbitDB,
                            $tahunDB,
                            $kategoriDB,
                            $ikonDB,
                            $stok,
                            $id
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION['pesan_buku'] =
                                'Data buku berhasil diperbarui.';
                            header('Location: buku.php');
                            exit;
                        } else {
                            $error = 'Gagal memperbarui buku. Periksa struktur database.';
                        }

                        mysqli_stmt_close($stmt);
                    } else {
                        $error = 'Gagal menyiapkan perintah database.';
                    }

                } else {
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO buku
                         (judul, pengarang, penerbit, tahun_terbit,
                          kategori, ikon, stok)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {
                        mysqli_stmt_bind_param(
                            $stmt,
                            'sssissi',
                            $judul,
                            $pengarang,
                            $penerbitDB,
                            $tahunDB,
                            $kategoriDB,
                            $ikonDB,
                            $stok
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION['pesan_buku'] =
                                'Buku baru berhasil ditambahkan.';
                            header('Location: buku.php');
                            exit;
                        } else {
                            $error = 'Gagal menambahkan buku. Periksa struktur database.';
                        }

                        mysqli_stmt_close($stmt);
                    } else {
                        $error = 'Gagal menyiapkan perintah database.';
                    }
                }
            }

        // HAPUS BUKU
        } elseif ($aksi === 'hapus') {

            $id = filter_var(
                $_POST['id_buku'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id === null || $id < 1) {
                $error = 'ID buku tidak valid.';
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM buku WHERE id_buku = ?"
                );

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $id);

                    if (mysqli_stmt_execute($stmt)) {
                        if (mysqli_stmt_affected_rows($stmt) > 0) {
                            $_SESSION['pesan_buku'] =
                                'Buku berhasil dihapus.';
                        } else {
                            $_SESSION['pesan_buku'] =
                                'Buku tidak ditemukan.';
                        }

                        mysqli_stmt_close($stmt);
                        header('Location: buku.php');
                        exit;
                    } else {
                        $error =
                            'Buku tidak dapat dihapus. Mungkin sudah tercatat dalam transaksi peminjaman.';
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Gagal menyiapkan perintah penghapusan.';
                }
            }

        } else {
            $error = 'Aksi tidak dikenali.';
        }
    }
}

// Ambil pesan setelah operasi berhasil
if (isset($_SESSION['pesan_buku'])) {
    $pesan = $_SESSION['pesan_buku'];
    unset($_SESSION['pesan_buku']);
}

// Ambil data buku untuk diedit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {

    $idEdit = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($idEdit !== false && $idEdit !== null && $idEdit > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_buku, judul, pengarang, penerbit,
                    tahun_terbit, kategori, ikon, stok
             FROM buku
             WHERE id_buku = ?
             LIMIT 1"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idEdit);
            mysqli_stmt_execute($stmt);

            $hasilEdit = mysqli_stmt_get_result($stmt);
            $dataEdit = mysqli_fetch_assoc($hasilEdit);

            if ($dataEdit) {
                $form = $dataEdit;
                $modeEdit = true;
            } else {
                $error = 'Data buku tidak ditemukan.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Pencarian buku
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $kataCari = '%' . $cari . '%';

    $stmtDaftar = mysqli_prepare(
        $conn,
        "SELECT id_buku, judul, pengarang, penerbit,
                tahun_terbit, kategori, ikon, stok
         FROM buku
         WHERE judul LIKE ? OR pengarang LIKE ? OR kategori LIKE ?
         ORDER BY id_buku DESC"
    );

    mysqli_stmt_bind_param(
        $stmtDaftar,
        'sss',
        $kataCari,
        $kataCari,
        $kataCari
    );

    mysqli_stmt_execute($stmtDaftar);
    $daftarBuku = mysqli_stmt_get_result($stmtDaftar);
} else {
    $daftarBuku = mysqli_query(
        $conn,
        "SELECT id_buku, judul, pengarang, penerbit,
                tahun_terbit, kategori, ikon, stok
         FROM buku
         ORDER BY id_buku DESC"
    );
}

$totalBuku = mysqli_num_rows($daftarBuku);
$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Buku | Stella Library</title>

<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
    --navy:#17243a;
    --navy2:#253853;
    --gold:#e6c477;
    --cream:#f6f1e8;
    --white:#fff;
    --text:#243044;
    --muted:#7b8493;
    --border:#ece5d9;
}
body{font-family:Arial,sans-serif;background:var(--cream);color:var(--text)}
.sidebar{
    position:fixed;left:0;top:0;width:250px;height:100vh;
    overflow-y:auto;background:var(--navy);color:white;padding:28px 18px
}
.logo{font-size:24px;font-weight:bold;color:var(--gold);margin:0 10px 8px}
.subtitle{font-size:12px;color:#c5ccda;margin:0 10px 35px}
.menu-title{font-size:11px;color:#aeb8c9;letter-spacing:1px;margin:22px 10px 12px}
.menu a{
    display:block;text-decoration:none;color:#edf0f5;padding:13px 12px;
    margin-bottom:7px;border-radius:8px;font-size:14px
}
.menu a:hover,.menu a.active{background:var(--navy2);color:var(--gold)}
.main{margin-left:250px;padding:30px;min-height:100vh}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:25px}
.topbar h1{font-size:27px;margin-bottom:8px}
.topbar p{font-size:14px;color:var(--muted)}
.admin-badge{background:white;border:1px solid var(--border);padding:12px 16px;border-radius:10px;font-size:13px}
.card{
    background:white;border:1px solid var(--border);border-radius:14px;
    padding:23px;margin-bottom:24px;box-shadow:0 4px 15px #14233708
}
.card h2{font-size:19px;margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.field label{display:block;font-size:13px;font-weight:bold;margin-bottom:7px}
.field input,.field select{
    width:100%;padding:11px;border:1px solid #d9dce2;border-radius:8px;
    font-size:14px;background:white;color:var(--text)
}
.field input:focus,.field select:focus{outline:2px solid #e6c477}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
.btn{
    border:0;border-radius:8px;padding:11px 15px;cursor:pointer;
    font-size:13px;font-weight:bold;text-decoration:none;display:inline-block
}
.btn-primary{background:var(--navy);color:white}
.btn-primary:hover{background:var(--navy2)}
.btn-cancel{background:#eee;color:#333}
.btn-edit{background:#fff0c9;color:#765313}
.btn-delete{background:#ffe4e1;color:#a52f26}
.btn-delete:hover{background:#ffd3cf}
.notice{padding:13px 16px;border-radius:9px;margin-bottom:20px;font-size:14px}
.success{background:#e4f6e9;color:#236b37}
.error{background:#ffe8e5;color:#a52e24}
.search{display:flex;gap:10px;margin-bottom:18px}
.search input{flex:1;min-width:0;padding:11px;border:1px solid #d9dce2;border-radius:8px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:850px}
th,td{padding:13px 11px;border-bottom:1px solid #f0ece5;text-align:left;font-size:13px}
th{background:#faf7f1;color:#6c7481;white-space:nowrap}
tbody tr:hover{background:#fcfaf6}
.stock{font-weight:bold;color:var(--navy)}
.row-actions{display:flex;align-items:center;gap:7px}
.row-actions form{display:inline}
.empty{text-align:center;padding:25px;color:var(--muted)}
.footer{text-align:center;color:var(--muted);font-size:12px;padding:10px 0 25px}
@media(max-width:900px){
    .sidebar{position:relative;width:100%;height:auto;padding:20px 15px}
    .subtitle{margin-bottom:18px}
    .menu-title{display:none}
    .menu{display:flex;flex-wrap:wrap;gap:5px}
    .menu a{margin:0;padding:10px}
    .main{margin-left:0;padding:18px}
    .topbar{align-items:flex-start;flex-direction:column}
}
@media(max-width:550px){
    .form-grid{grid-template-columns:1fr}
    .card{padding:16px}
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">✦ Stella Library</div>
    <div class="subtitle">Sistem Informasi Perpustakaan</div>
    <div class="menu-title">Menu Utama</div>

    <nav class="menu">
        <a href="dashboard.php">⌂ Dashboard</a>
        <a href="buku.php" class="active">▤ Kelola Buku</a>
        <a href="peminjaman.php">⇄ Data Peminjaman</a>
        <a href="anggota.php">♙ Data Anggota</a>
        <a href="../index.php">⌂ Halaman Utama</a>
        <a href="../logout.php">⇥ Logout</a>
    </nav>
</aside>

<main class="main">

<header class="topbar">
    <div>
        <h1>Kelola Buku</h1>
        <p>Kelola koleksi dan persediaan buku Stella Library.</p>
    </div>

    <div class="admin-badge">
        Administrator: <strong><?= aman($namaAdmin) ?></strong>
    </div>
</header>

<?php if ($pesan !== ''): ?>
    <div class="notice success"><?= aman($pesan) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="notice error"><?= aman($error) ?></div>
<?php endif; ?>

<section class="card">
    <h2><?= $modeEdit ? '✏️ Edit Informasi Buku' : '➕ Tambah Buku Baru' ?></h2>

    <form method="POST" action="buku.php<?= $modeEdit ? '?edit=' . (int)$form['id_buku'] : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= aman($_SESSION['csrf_buku']) ?>">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id_buku" value="<?= (int)$form['id_buku'] ?>">

        <div class="form-grid">

            <div class="field">
                <label for="judul">Judul Buku *</label>
                <input id="judul" name="judul" maxlength="100"
                    value="<?= aman($form['judul']) ?>" required>
            </div>

            <div class="field">
                <label for="pengarang">Pengarang *</label>
                <input id="pengarang" name="pengarang" maxlength="50"
                    value="<?= aman($form['pengarang']) ?>" required>
            </div>

            <div class="field">
                <label for="penerbit">Penerbit</label>
                <input id="penerbit" name="penerbit" maxlength="100"
                    value="<?= aman($form['penerbit']) ?>">
            </div>

            <div class="field">
                <label for="tahun_terbit">Tahun Terbit</label>
                <input id="tahun_terbit" name="tahun_terbit"
                    type="number" min="1000" max="9999"
                    value="<?= aman($form['tahun_terbit']) ?>">
            </div>

            <div class="field">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">-- Pilih kategori --</option>
                    <?php
                    $kategoriList = [
                        'Pemrograman',
                        'Jaringan',
                        'Database',
                        'Cybersecurity',
                        'Akademik',
                        'Sistem Operasi',
                        'Komputer',
                        'Lainnya'
                    ];
                    foreach ($kategoriList as $kategoriPilihan):
                    ?>
                        <option value="<?= aman($kategoriPilihan) ?>"
                            <?= $form['kategori'] === $kategoriPilihan ? 'selected' : '' ?>>
                            <?= aman($kategoriPilihan) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="ikon">Ikon Buku (opsional)</label>
                <input id="ikon" name="ikon" maxlength="10"
                    value="<?= aman($form['ikon']) ?>" placeholder="📚">
            </div>

            <div class="field">
                <label for="stok">Jumlah Stok *</label>
                <input id="stok" name="stok" type="number" min="0"
                    value="<?= aman($form['stok']) ?>" required>
            </div>

        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">
                <?= $modeEdit ? 'Simpan Perubahan' : 'Tambah Buku' ?>
            </button>

            <?php if ($modeEdit): ?>
                <a href="buku.php" class="btn btn-cancel">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <h2>📚 Daftar Koleksi Buku (<?= $totalBuku ?>)</h2>

    <form method="GET" action="buku.php" class="search">
        <input type="text" name="cari" placeholder="Cari judul, pengarang, atau kategori..."
            value="<?= aman($cari) ?>">
        <button class="btn btn-primary" type="submit">Cari</button>
        <?php if ($cari !== ''): ?>
            <a href="buku.php" class="btn btn-cancel">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Judul Buku</th>
                    <th>Pengarang</th>
                    <th>Penerbit</th>
                    <th>Tahun</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($daftarBuku && mysqli_num_rows($daftarBuku) > 0): ?>
                <?php $no = 1; ?>
                <?php while ($buku = mysqli_fetch_assoc($daftarBuku)): ?>
                    <tr>
    <td><?= $no++ ?></td>
    <td>
        <?= aman($buku['ikon'] ?: '📚') ?>
        <strong><?= aman($buku['judul']) ?></strong>
    </td>
    <td><?= aman($buku['pengarang']) ?></td>
    <td><?= aman($buku['penerbit'] ?: '-') ?></td>
    <td><?= aman($buku['tahun_terbit'] ?: '-') ?></td>
    <td><?= aman($buku['kategori'] ?: '-') ?></td>
    <td class="stock"><?= (int)$buku['stok'] ?></td>
    <td>
        <div class="row-actions">
            <a class="btn btn-edit"
               href="buku.php?edit=<?= (int)$buku['id_buku'] ?>">
                Edit
            </a>

            <form method="POST" action="buku.php"
                  onsubmit="return confirm('Yakin ingin menghapus buku ini?');">
                <input type="hidden" name="csrf_token"
                       value="<?= aman($_SESSION['csrf_buku']) ?>">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id_buku"
                       value="<?= (int)$buku['id_buku'] ?>">

                <button class="btn btn-delete" type="submit">
                    Hapus
                </button>
            </form>
        </div>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="8" class="empty">
        Belum ada buku yang cocok dengan pencarianmu.
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

</main>
</body>
</html>
                    