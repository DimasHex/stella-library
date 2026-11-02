<?php
session_start();
require_once '../koneksi.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'anggota'
) {
    header('Location: ../login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

/*
 * Cari data anggota berdasarkan user_id.
 * Kolom user_id ditambahkan ke tabel anggota sebelumnya.
 */
$stmt = $conn->prepare(
    "SELECT id_anggota, nama, nim
     FROM anggota
     WHERE user_id = ?
     LIMIT 1"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$anggota = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$anggota) {
    exit(
        'Data anggota tidak ditemukan. Silakan hubungi admin '
        . 'untuk memeriksa hubungan akun dengan data anggota.'
    );
}

$idAnggota = (int) $anggota['id_anggota'];

$idBuku = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idBuku = filter_input(INPUT_POST, 'id_buku', FILTER_VALIDATE_INT);
    $tanggalPinjam = $_POST['tanggal_pinjam'] ?? '';
    $tanggalKembali = $_POST['tanggal_jatuh_tempo'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !isset($_SESSION['csrf_pinjam']) ||
        !hash_equals($_SESSION['csrf_pinjam'], $csrf)
    ) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif (!$idBuku) {
        $error = 'Buku yang dipilih tidak valid.';
    } elseif (!$tanggalPinjam || !$tanggalKembali) {
        $error = 'Tanggal pinjam dan tanggal pengembalian wajib diisi.';
    } else {
        $pinjamDate = DateTime::createFromFormat('!Y-m-d', $tanggalPinjam);
        $kembaliDate = DateTime::createFromFormat('!Y-m-d', $tanggalKembali);
        $today = new DateTime('today');

        if (
            !$pinjamDate ||
            $pinjamDate->format('Y-m-d') !== $tanggalPinjam ||
            !$kembaliDate ||
            $kembaliDate->format('Y-m-d') !== $tanggalKembali
        ) {
            $error = 'Format tanggal tidak valid.';
        } elseif ($pinjamDate < $today) {
            $error = 'Tanggal pinjam tidak boleh sebelum hari ini.';
        } elseif ($kembaliDate <= $pinjamDate) {
            $error = 'Tanggal pengembalian harus setelah tanggal pinjam.';
        } else {
            $conn->begin_transaction();

            try {
                $cekBuku = $conn->prepare(
                    "SELECT id_buku, judul, stok
                     FROM buku
                     WHERE id_buku = ?
                     FOR UPDATE"
                );
                $cekBuku->bind_param('i', $idBuku);
                $cekBuku->execute();
                $buku = $cekBuku->get_result()->fetch_assoc();
                $cekBuku->close();

                if (!$buku) {
                    throw new RuntimeException('Buku tidak ditemukan.');
                }

                if ((int)$buku['stok'] < 1) {
                    throw new RuntimeException('Stok buku sedang habis.');
                }

                /*
                 * Cegah anggota mengajukan buku yang sama
                 * berkali-kali saat permintaan sebelumnya masih aktif.
                 */
                $cekPengajuan = $conn->prepare(
                    "SELECT id_pinjam
                     FROM peminjaman
                     WHERE id_anggota = ?
                       AND id_buku = ?
                       AND status IN ('Menunggu', 'Dipinjam')
                     LIMIT 1"
                );
                $cekPengajuan->bind_param('ii', $idAnggota, $idBuku);
                $cekPengajuan->execute();
                $sudahAda = $cekPengajuan->get_result()->num_rows > 0;
                $cekPengajuan->close();

                if ($sudahAda) {
                    throw new RuntimeException(
                        'Kamu masih memiliki pengajuan atau peminjaman aktif untuk buku ini.'
                    );
                }

                /*
                 * Stok belum dikurangi di tahap pengajuan.
                 * Admin akan memutuskan persetujuannya.
                 */
                $status = 'Menunggu';

                $insert = $conn->prepare(
                    "INSERT INTO peminjaman
                     (id_anggota, id_buku, tanggal_pinjam,
                      tanggal_jatuh_tempo, status)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $insert->bind_param(
                    'iisss',
                    $idAnggota,
                    $idBuku,
                    $tanggalPinjam,
                    $tanggalKembali,
                    $status
                );

                $insert->execute();
                $insert->close();

                $conn->commit();

                $success = 'Pengajuan peminjaman berhasil dikirim. Silakan tunggu persetujuan admin.';
                unset($_SESSION['csrf_pinjam']);
            } catch (Throwable $ex) {
                $conn->rollback();
                $error = $ex instanceof RuntimeException
                    ? $ex->getMessage()
                    : 'Pengajuan gagal disimpan. Periksa struktur database dan coba lagi.';
            }
        }
    }
}

if (!isset($_SESSION['csrf_pinjam'])) {
    $_SESSION['csrf_pinjam'] = bin2hex(random_bytes(32));
}

$buku = null;

if ($idBuku) {
    $stmt = $conn->prepare(
        "SELECT id_buku, judul, pengarang, kategori, stok, ikon
         FROM buku
         WHERE id_buku = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $idBuku);
    $stmt->execute();
    $buku = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$buku && !$success) {
    $error = 'Buku tidak ditemukan. Silakan kembali ke katalog dan pilih buku.';
}

$today = date('Y-m-d');
$defaultKembali = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Peminjaman | Stella Library</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px 16px;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #f6f1e8;
            color: #243044;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #8c6a24;
            font-weight: bold;
            text-decoration: none;
        }

        .card {
            background: white;
            border: 1px solid #ece5d9;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(20, 35, 55, .06);
        }

        .brand {
            color: #a47a28;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        h1 {
            margin: 10px 0;
            color: #17243a;
            font-size: 28px;
        }

        .subtitle {
            color: #7b8493;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .book {
            padding: 18px;
            margin-bottom: 25px;
            border-radius: 12px;
            background: #f8f5ee;
            border: 1px solid #ece5d9;
        }

        .book h2 {
            margin: 8px 0;
            color: #17243a;
            font-size: 20px;
        }

        .book p {
            margin: 5px 0;
            color: #7b8493;
            font-size: 14px;
        }

        .label {
            display: block;
            margin: 17px 0 8px;
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcd7ce;
            border-radius: 8px;
            font: inherit;
        }

        input:focus {
            outline: 2px solid #e6c477;
            border-color: #b58b3b;
        }

        .hint {
            margin-top: 7px;
            color: #7b8493;
            font-size: 12px;
        }

        .notice {
            padding: 13px;
            margin: 18px 0;
            border-radius: 8px;
            background: #fff6df;
            color: #75551a;
            font-size: 13px;
            line-height: 1.6;
        }

        .message {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 8px;
            line-height: 1.6;
        }

        .error {
            color: #a12622;
            background: #fde8e7;
        }

        .success {
            color: #216e39;
            background: #e5f6e9;
        }

        button {
            width: 100%;
            margin-top: 15px;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #17243a;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #304664; }

        .footer {
            margin-top: 20px;
            text-align: center;
        }

        .footer a {
            color: #8c6a24;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .card { padding: 22px 18px; }
        }
    </style>
</head>
<body>

<div class="container">

    <a class="back" href="../index.php#katalog">← Kembali ke Katalog</a>

    <div class="card">
        <div class="brand">STELLA LIBRARY</div>
        <h1>Ajukan Peminjaman</h1>

        <p class="subtitle">
            Isi tanggal peminjaman dan pengembalian yang kamu rencanakan.
            Pengajuan akan diperiksa oleh admin.
        </p>

        <?php if ($error !== ''): ?>
            <div class="message error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>

            <div class="message success">
                <?= e($success) ?>
            </div>

            <a class="back" href="../index.php#katalog">← Kembali ke Katalog</a>

        <?php elseif ($buku): ?>

            <div class="book">
                <div><?= e($buku['ikon'] ?: '📚') ?></div>
                <h2><?= e($buku['judul']) ?></h2>
                <p>Pengarang: <?= e($buku['pengarang']) ?></p>
                <p>Kategori: <?= e($buku['kategori']) ?></p>
                <p>Stok tersedia: <?= (int)$buku['stok'] ?></p>
                <p>Anggota: <?= e($anggota['nama']) ?></p>
            </div>

            <?php if ((int)$buku['stok'] > 0): ?>

                <form method="POST">
                    <input
                        type="hidden"
                        name="id_buku"
                        value="<?= (int)$buku['id_buku'] ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_pinjam']) ?>"
                    >

                    <label class="label" for="tanggal_pinjam">
                        Tanggal Pinjam
                    </label>

                    <input
                        type="date"
                        id="tanggal_pinjam"
                        name="tanggal_pinjam"
                        min="<?= e($today) ?>"
                        value="<?= e($_POST['tanggal_pinjam'] ?? $today) ?>"
                        required
                    >

                    <label class="label" for="tanggal_jatuh_tempo">
                        Tanggal Pengembalian
                    </label>

                    <input
                        type="date"
                        id="tanggal_jatuh_tempo"
                        name="tanggal_jatuh_tempo"
                        min="<?= e($today) ?>"
                        value="<?= e($_POST['tanggal_jatuh_tempo'] ?? $defaultKembali) ?>"
                        required
                    >

                    <p class="hint">
                        Saran awal: rencanakan pengembalian dalam 7 hari.
                        Pastikan tanggal pengembalian setelah tanggal pinjam.
                    </p>

                    <div class="notice">
                        <strong>Perhatian:</strong> pengajuan ini belum berarti
                        peminjaman disetujui. Tunggu keputusan admin sebelum
                        mengambil buku. Stok baru dikurangi setelah admin menyetujui.
                    </div>

                    <button type="submit">Ajukan Peminjaman</button>
                </form>

            <?php else: ?>

                <div class="notice">
                    Maaf, stok buku ini sedang habis.
                </div>

            <?php endif; ?>

        <?php endif; ?>

        <div class="footer">
            <a href="dashboard.php">Lihat Dashboard Anggota</a>
        </div>
    </div>

</div>

<script>
const tanggalPinjam = document.getElementById('tanggal_pinjam');
const tanggalKembali = document.getElementById('tanggal_jatuh_tempo');

if (tanggalPinjam && tanggalKembali) {
    tanggalPinjam.addEventListener('change', function () {
        tanggalKembali.min = this.value;

        if (tanggalKembali.value && tanggalKembali.value <= this.value) {
            const date = new Date(this.value + 'T00:00:00');
            date.setDate(date.getDate() + 7);

            const tahun = date.getFullYear();
            const bulan = String(date.getMonth() + 1).padStart(2, '0');
            const hari = String(date.getDate()).padStart(2, '0');

            tanggalKembali.value = `${tahun}-${bulan}-${hari}`;
        }
    });
}
</script>

</body>
</html>
