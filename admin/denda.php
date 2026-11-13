
<?php
session_start();
require_once '../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Cek akses admin
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../login.php');
    exit;
}

// Membuat token keamanan form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pesan = '';
$jenisPesan = '';

// Proses penyimpanan tarif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $pesan = 'Permintaan tidak valid. Muat ulang halaman.';
        $jenisPesan = 'error';

    } else {

        $inputTarif = $_POST['tarif_per_hari'] ?? '';

        if (
            !is_string($inputTarif) ||
            !ctype_digit($inputTarif) ||
            (float)$inputTarif > 100000000
        ) {
            $pesan = 'Masukkan tarif antara Rp0 sampai Rp100.000.000.';
            $jenisPesan = 'error';

        } else {

            $tarif = (int)$inputTarif;

            $stmt = $conn->prepare(
                "UPDATE pengaturan_denda
                 SET tarif_per_hari = ?
                 WHERE id = 1"
            );

            if (!$stmt) {
                $pesan = 'Gagal menyiapkan penyimpanan tarif.';
                $jenisPesan = 'error';

            } else {

                $stmt->bind_param("i", $tarif);

                if ($stmt->execute() && $stmt->affected_rows >= 0) {

                    $pesan = 'Tarif denda berhasil disimpan.';
                    $jenisPesan = 'success';

                } else {

                    $pesan = 'Tarif gagal disimpan.';
                    $jenisPesan = 'error';

                }

                $stmt->close();
            }
        }
    }
}

// Ambil tarif saat ini
$result = $conn->query(
    "SELECT tarif_per_hari
     FROM pengaturan_denda
     WHERE id = 1"
);

if (!$result || $result->num_rows === 0) {
    die('Data pengaturan denda belum tersedia.');
}

$data = $result->fetch_assoc();
$tarifSekarang = (int)$data['tarif_per_hari'];

function rupiah($angka) {
    return 'Rp' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pengaturan Denda - Stella Library</title>

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
}

nav strong {
    font-size: 20px;
}

nav a {
    color: white;
    text-decoration: none;
}

.container {
    max-width: 850px;
    margin: 40px auto;
    padding: 0 20px;
}

.card {
    background: white;
    padding: 28px;
    border-radius: 14px;
    margin-bottom: 22px;
    box-shadow: 0 4px 15px #0000000d;
}

h1, h2 {
    color: #172554;
}

.subtitle {
    color: #64748b;
}

.current-rate {
    background: #101d3a;
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin: 20px 0;
}

.current-rate small {
    color: #dedede;
}

.current-rate h2 {
    color: #e6bd67;
    font-size: 32px;
    margin: 12px 0;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 10px;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    margin-bottom: 18px;
}

input:focus {
    outline: none;
    border-color: #c59b45;
}

.btn {
    border: none;
    background: #c59b45;
    color: #101d3a;
    padding: 13px 20px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

.btn:hover {
    background: #b58a35;
}

.alert {
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.success {
    background: #eaf8ed;
    color: #216b36;
}

.error {
    background: #feecec;
    color: #a12626;
}

.information {
    background: #fff8e7;
    border-left: 4px solid #c59b45;
    padding: 16px;
    border-radius: 7px;
    line-height: 1.6;
}

footer {
    text-align: center;
    padding: 25px;
    color: #777;
}

@media (max-width: 600px) {
    .container {
        margin: 25px auto;
        padding: 0 14px;
    }

    .card {
        padding: 20px;
    }
}
</style>
</head>

<body>

<nav>
    <strong>Stella Library</strong>
    <a href="../logout.php">Keluar</a>
</nav>

<div class="container">

    <h1>Pengaturan Denda</h1>
    <p class="subtitle">
        Kelola tarif keterlambatan pengembalian buku.
    </p>

    <?php if ($pesan !== ''): ?>
        <div class="alert <?= htmlspecialchars($jenisPesan) ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <div class="current-rate">
            <small>Tarif denda per buku / hari</small>

            <h2><?= rupiah($tarifSekarang) ?></h2>

            <small>Tarif yang sedang berlaku.</small>
        </div>

        <h2>Ubah Tarif Denda</h2>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <label for="tarif_per_hari">
                Tarif Denda per Hari (Rupiah)
            </label>

            <input
                type="number"
                id="tarif_per_hari"
                name="tarif_per_hari"
                min="0"
                max="100000000"
                step="1"
                value="<?= $tarifSekarang ?>"
                required
            >

            <button type="submit" class="btn">
                Simpan Pengaturan
            </button>

        </form>

    </div>

    <div class="information">
        <strong>Informasi Perhitungan</strong>

        <p>
            Denda dihitung dari jumlah hari keterlambatan
            dikalikan tarif per hari untuk setiap buku.
        </p>

        <p>
            Contoh: tarif Rp2.000 dan terlambat 3 hari
            menghasilkan denda Rp6.000 untuk satu buku.
        </p>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> Stella Library
</footer>

</body>
</html>
