
<?php
session_start();
require_once '../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// ========================================
// 1. CEK LOGIN
// ========================================

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'anggota'
) {
    header('Location: ../login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];


// ========================================
// 2. AMBIL DATA ANGGOTA
// ========================================

$stmt = $conn->prepare(
    "SELECT id_anggota, nim, nama, jurusan
     FROM anggota
     WHERE user_id = ?"
);

if (!$stmt) {
    die("Gagal menyiapkan query anggota.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$anggota = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$anggota) {
    die("Data anggota belum ditemukan. Silakan hubungi admin.");
}

$id_anggota = (int) $anggota['id_anggota'];


// ========================================
// 3. AMBIL RIWAYAT PEMINJAMAN
// ========================================

$stmt = $conn->prepare(
    "SELECT
        p.id_pinjam,
        p.tanggal_pinjam,
        p.tanggal_jatuh_tempo,
        p.tanggal_kembali,
        p.status,
        b.judul
     FROM peminjaman p
     JOIN buku b ON p.id_buku = b.id_buku
     WHERE p.id_anggota = ?
     ORDER BY p.id_pinjam DESC"
);

if (!$stmt) {
    die("Gagal mengambil data peminjaman.");
}

$stmt->bind_param("i", $id_anggota);
$stmt->execute();

$result = $stmt->get_result();

$peminjaman = [];
$daftarReminder = [];


// ========================================
// 4. LOGIKA SMART RETURN REMINDER
// ========================================

$zonaWaktu = new DateTimeZone('Asia/Jakarta');
$hariIni = new DateTimeImmutable('today', $zonaWaktu);

while ($row = $result->fetch_assoc()) {

    // Simpan semua data untuk riwayat
    $peminjaman[] = $row;

    // Status yang dianggap masih meminjam
    $statusAktif = in_array(
        strtolower(trim((string) $row['status'])),
        ['disetujui', 'dipinjam', 'terlambat'],
        true
    );

    // Lewati jika buku sudah dikembalikan
    if (
        !$statusAktif ||
        !empty($row['tanggal_kembali']) ||
        empty($row['tanggal_jatuh_tempo'])
    ) {
        continue;
    }

    // Periksa tanggal jatuh tempo
    $deadline = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $row['tanggal_jatuh_tempo'],
        $zonaWaktu
    );

    if (!$deadline) {
        continue;
    }

    // Selisih hari antara hari ini dan deadline
    $selisihHari = (int) $hariIni
        ->diff($deadline)
        ->format('%r%a');

    $judulReminder = '';
    $pesanReminder = '';
    $jenisReminder = '';

    // H-3
    if ($selisihHari === 3) {

        $judulReminder = 'Pengembalian tinggal 3 hari';
        $pesanReminder = 'Segera persiapkan pengembalian buku.';
        $jenisReminder = 'warning';

    }

    // H-1
    elseif ($selisihHari === 1) {

        $judulReminder = 'Besok batas pengembalian!';
        $pesanReminder = 'Jangan lupa mengembalikan buku besok.';
        $jenisReminder = 'warning';

    }

    // Hari H
    elseif ($selisihHari === 0) {

        $judulReminder = 'Hari ini batas pengembalian!';
        $pesanReminder = 'Mohon kembalikan buku hari ini.';
        $jenisReminder = 'today';

    }

    // Terlambat
    elseif ($selisihHari < 0) {

        $hariTerlambat = abs($selisihHari);

        $judulReminder = 'Buku terlambat dikembalikan';
        $pesanReminder = 'Terlambat selama ' .
            $hariTerlambat . ' hari. Segera hubungi petugas perpustakaan.';
        $jenisReminder = 'danger';

    }

    // Masukkan ke daftar pengingat
    if ($judulReminder !== '') {

        $daftarReminder[] = [
            'judul' => $row['judul'],
            'deadline' => $row['tanggal_jatuh_tempo'],
            'judul_reminder' => $judulReminder,
            'pesan' => $pesanReminder,
            'jenis' => $jenisReminder
        ];

    }
}

$stmt->close();


// ========================================
// 5. FUNGSI FORMAT TANGGAL
// ========================================

function formatTanggal($tanggal) {

    if (empty($tanggal)) {
        return '-';
    }

    $tanggalObj = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $tanggal,
        new DateTimeZone('Asia/Jakarta')
    );

    if (!$tanggalObj) {
        return htmlspecialchars((string) $tanggal);
    }

    return $tanggalObj->format('d-m-Y');
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

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

nav a:hover {
    color: #c59b45;
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

.btn:hover {
    background: #b58a35;
}

/* SMART RETURN REMINDER */

.reminder-card {
    border-left: 5px solid #c59b45;
}

.reminder-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.reminder-heading h2 {
    margin: 0;
}

.reminder-icon {
    font-size: 28px;
}

.reminder-item {
    padding: 17px;
    border-radius: 10px;
    margin-bottom: 13px;
    border-left: 5px solid;
}

.reminder-item:last-child {
    margin-bottom: 0;
}

.reminder-warning {
    background: #fff8e6;
    border-color: #e6a23c;
}

.reminder-today {
    background: #eaf3ff;
    border-color: #3984d6;
}

.reminder-danger {
    background: #fff0f0;
    border-color: #dc4545;
}

.reminder-item h3 {
    margin: 0 0 8px;
    font-size: 16px;
}

.reminder-item p {
    margin: 6px 0;
    line-height: 1.5;
}

.reminder-date {
    font-size: 13px;
    color: #666;
}

.no-reminder {
    background: #edf8ee;
    padding: 18px;
    border-radius: 10px;
    color: #28643b;
}

/* TABLE */

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

@media (max-width: 600px) {

    nav {
        padding: 16px 5%;
    }

    nav a {
        margin-left: 8px;
        font-size: 13px;
    }

    .container {
        margin: 22px auto;
        padding: 0 14px;
    }

    .card {
        padding: 18px;
    }

}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav>

    <strong>Stella Library</strong>

    <div>
        <a href="../index.php">Beranda</a>
        <a href="pinjam.php">Pinjam Buku</a>
        <a href="../logout.php">Keluar</a>
    </div>

</nav>


<div class="container">

    <!-- INFORMASI ANGGOTA -->

    <h1>Akun Saya</h1>

    <p>
        Selamat datang,
        <?= htmlspecialchars($anggota['nama']) ?>!
    </p>

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

        <a class="btn" href="../index.php">
            Cari Buku
        </a>

    </div>


    <!-- SMART RETURN REMINDER -->

    <div class="card reminder-card">

        <div class="reminder-heading">

            <span class="reminder-icon">🔔</span>

            <h2>Pengingat Pengembalian Buku</h2>

        </div>


        <?php if (count($daftarReminder) > 0): ?>

            <?php foreach ($daftarReminder as $reminder): ?>

                <div class="reminder-item reminder-<?= htmlspecialchars($reminder['jenis']) ?>">

                    <h3>
                        <?= htmlspecialchars($reminder['judul_reminder']) ?>
                    </h3>

                    <p>
                        <strong>
                            <?= htmlspecialchars($reminder['judul']) ?>
                        </strong>
                    </p>

                    <p>
                        <?= htmlspecialchars($reminder['pesan']) ?>
                    </p>

                    <div class="reminder-date">
                        Batas pengembalian:
                        <?= formatTanggal($reminder['deadline']) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="no-reminder">

                <strong>Tidak ada pengingat saat ini.</strong>

                <p>
                    Semua peminjaman kamu aman atau belum
                    mendekati batas pengembalian.
                </p>

            </div>

        <?php endif; ?>

    </div>


    <!-- RIWAYAT PEMINJAMAN -->

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

                <?php if (count($peminjaman) > 0): ?>

                    <?php foreach ($peminjaman as $row): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($row['judul']) ?>
                            </td>

                            <td>
                                <?= formatTanggal($row['tanggal_pinjam']) ?>
                            </td>

                            <td>
                                <?= formatTanggal($row['tanggal_jatuh_tempo']) ?>
                            </td>

                            <td class="status">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

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
