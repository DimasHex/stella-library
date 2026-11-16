<?php
session_start();
require_once 'koneksi.php';

$folder_ebook = 'e-book/';

$id_buku = 0;
if (isset($_GET['id_buku'])) {
    $id_buku = (int)$_GET['id_buku'];
} elseif (isset($_GET['id'])) {
    $id_buku = (int)$_GET['id'];
}

$aksi = $_GET['aksi'] ?? 'sinopsis';

$stmt = $conn->prepare("SELECT id_buku, judul, pengarang, penerbit, tahun_terbit, kategori, ikon, stok, sinopsis, nama_file FROM buku WHERE id_buku = ?");
$stmt->bind_param("i", $id_buku);
$stmt->execute();
$buku = $stmt->get_result()->fetch_assoc();

if (!$buku) {
    http_response_code(404);
    die('Data buku tidak ditemukan.');
}

$judul_buku    = htmlspecialchars($buku['judul'], ENT_QUOTES, 'UTF-8');
$pengarang     = htmlspecialchars($buku['pengarang'], ENT_QUOTES, 'UTF-8');
$kategori      = htmlspecialchars($buku['kategori'] ?? 'Umum', ENT_QUOTES, 'UTF-8');
$ikon          = htmlspecialchars($buku['ikon'] ?? '📘', ENT_QUOTES, 'UTF-8');
$penerbit      = htmlspecialchars($buku['penerbit'] ?? '-', ENT_QUOTES, 'UTF-8');
$tahun_terbit  = htmlspecialchars($buku['tahun_terbit'] ?? '-', ENT_QUOTES, 'UTF-8');
$sinopsis_buku = !empty($buku['sinopsis'])
    ? nl2br(htmlspecialchars($buku['sinopsis'], ENT_QUOTES, 'UTF-8'))
    : '<em>Sinopsis atau abstrak untuk buku ini belum tersedia.</em>';

$sudah_login = isset($_SESSION['user_id']);
$nama_user   = htmlspecialchars($_SESSION['nama'] ?? 'Pembaca', ENT_QUOTES, 'UTF-8');

// =========================================================================
// TAHAP 2: MEMBUKA FILE PDF LAYAR PENUH (WAJIB LOGIN)
// =========================================================================
if ($aksi === 'baca') {
    if (!$sudah_login) {
        $tujuan = urlencode("mode_baca.php?id_buku=" . $id_buku . "&aksi=baca");
        header("Location: login.php?pesan=wajib_login&redirect=" . $tujuan);
        exit;
    }

    if (empty($buku['nama_file'])) {
        http_response_code(404);
        die('File PDF untuk buku ini belum tersedia.');
    }

    $nama_file = basename($buku['nama_file']);
    $path_file = $folder_ebook . $nama_file;

    if (!file_exists($path_file)) {
        http_response_code(404);
        die('File fisik PDF (' . htmlspecialchars($nama_file, ENT_QUOTES, 'UTF-8') . ') tidak ditemukan di folder e-book/.');
    }

    $url_pdf = $folder_ebook . rawurlencode($nama_file);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Baca: <?= $judul_buku ?> — Stella Library</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: "DM Sans", Arial, sans-serif;
                background: #162336;
                color: #fff8f0;
                display: flex;
                flex-direction: column;
                height: 100vh;
                overflow: hidden;
            }
            .toolbar {
                height: 56px;
                background: #20344d;
                padding: 0 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            }
            .toolbar-title {
                font-family: Georgia, serif;
                font-size: 16px;
                font-weight: bold;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 50%;
            }
            .toolbar-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .btn {
                text-decoration: none;
                color: #fff8f0;
                background: rgba(255, 255, 255, 0.12);
                padding: 8px 14px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
            }
            .btn:hover { background: rgba(255, 255, 255, 0.2); }
            .btn-gold { background: #b58b3b; color: #ffffff; }
            .btn-gold:hover { background: #9e772f; }
            .btn-logout { background: #a13c3c; }
            .pdf-container { flex: 1; width: 100%; height: calc(100vh - 56px); }
            object, iframe { width: 100%; height: 100%; border: none; }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <a href="mode_baca.php?id_buku=<?= $id_buku ?>" class="btn">&larr; Sinopsis</a>
            <div class="toolbar-title"><?= $ikon ?> <?= $judul_buku ?></div>
            <div class="toolbar-actions">
                <a href="<?= $url_pdf ?>" download class="btn btn-gold">Unduh PDF</a>
                <a href="logout.php" class="btn btn-logout">Keluar</a>
            </div>
        </div>
        <div class="pdf-container">
            <object data="<?= $url_pdf ?>#toolbar=1&navpanes=0" type="application/pdf">
                <iframe src="<?= $url_pdf ?>">
                    <p style="padding: 20px; text-align: center;">
                        Browser tidak mendukung pratinjau PDF. <a href="<?= $url_pdf ?>" style="color: #e6cfa3;">Unduh PDF di sini</a>.
                    </p>
                </iframe>
            </object>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================================================================
// TAHAP 1: TAMPILAN SINOPSIS / ABSTRAK (TERBUKA SEBELUM LOGIN)
// =========================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinopsis: <?= $judul_buku ?> — Stella Library</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "DM Sans", Arial, sans-serif;
            background: #f8f5ef;
            color: #20344d;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px 16px;
        }
        .card {
            width: 100%;
            max-width: 740px;
            background: #ffffff;
            padding: 36px;
            border-radius: 20px;
            box-shadow: 0 15px 45px rgba(32, 52, 77, 0.10);
        }
        .brand {
            color: #b58b3b;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 16px;
        }
        .header-top {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 18px;
        }
        .ikon-buku {
            font-size: 44px;
            background: #f8f5ef;
            border: 1px solid #e2dfd8;
            width: 76px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            flex-shrink: 0;
        }
        .badge {
            display: inline-block;
            background: #fdf6e7;
            color: #9e772f;
            border: 1px solid #e6cfa3;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        h1 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 26px;
            color: #20344d;
        }
        .meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid #ece8df;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }
        .sinopsis-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            color: #20344d;
        }
        .sinopsis-isi {
            line-height: 1.75;
            font-size: 15px;
            color: #3d4856;
            background: #f8f5ef;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #b58b3b;
            margin-bottom: 24px;
        }
        .info-login {
            font-size: 13px;
            color: #7a5818;
            background: #fdf6e7;
            border: 1px solid #e6cfa3;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 22px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            text-decoration: none;
            padding: 13px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-secondary {
            background: #ece8df;
            color: #20344d;
        }
        .btn-secondary:hover { background: #dfd9cc; }
        .btn-primary {
            background: #20344d;
            color: #ffffff;
        }
        .btn-primary:hover { background: #304c6c; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">STELLA LIBRARY • PRATINJAU E-BOOK</div>

        <div class="header-top">
            <div class="ikon-buku"><?= $ikon ?></div>
            <div>
                <span class="badge"><?= $kategori ?></span>
                <h1><?= $judul_buku ?></h1>
            </div>
        </div>

        <div class="meta">
            <span>Pengarang: <strong><?= $pengarang ?></strong></span>
            <span>Penerbit: <strong><?= $penerbit ?></strong></span>
            <span>Tahun: <strong><?= $tahun_terbit ?></strong></span>
            <span>Stok Fisik: <strong><?= (int)$buku['stok'] ?></strong></span>
        </div>

        <div class="sinopsis-label">Sinopsis / Abstrak Buku:</div>
        <div class="sinopsis-isi"><?= $sinopsis_buku ?></div>

        <?php if (!$sudah_login): ?>
            <div class="info-login">
                Kamu mengakses sebagai tamu. Silakan <strong>masuk ke akun</strong> terlebih dahulu untuk membaca dokumen PDF secara penuh.
            </div>
        <?php else: ?>
            <div class="info-login" style="background: #e5f6e9; border-color: #b7e1c1; color: #216e39;">
                Halo, <strong><?= $nama_user ?></strong>! Akunmu aktif dan dapat langsung membaca buku ini.
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Katalog</a>
            <a href="mode_baca.php?id_buku=<?= $id_buku ?>&aksi=baca" class="btn btn-primary">
                <?= $sudah_login ? 'Baca Selengkapnya &rarr;' : 'Login untuk Baca Selengkapnya &rarr;' ?>
            </a>
        </div>
    </div>
</body>
</html>