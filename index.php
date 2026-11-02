<?php
session_start();
require_once 'koneksi.php';

$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama'] ?? ($_SESSION['nama_lengkap'] ?? '');

$isLogin = isset($_SESSION['user_id']);
$isTamu = ($role === 'tamu');
$isAdmin = ($isLogin && $role === 'admin');
$isAnggota = ($isLogin && $role === 'anggota');

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$keyword = trim($_GET['cari'] ?? '');
$bukuList = [];
$errorBuku = false;

try {
    if ($keyword !== '') {
        $sql = "SELECT id_buku, judul, pengarang, kategori, ikon, stok
                FROM buku
                WHERE judul LIKE ?
                   OR pengarang LIKE ?
                   OR kategori LIKE ?
                ORDER BY judul ASC";

        $stmt = mysqli_prepare($conn, $sql);
        $cari = '%' . $keyword . '%';

        mysqli_stmt_bind_param(
            $stmt,
            'sss',
            $cari,
            $cari,
            $cari
        );

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $bukuList[] = $row;
        }

        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query(
            $conn,
            "SELECT id_buku, judul, pengarang, kategori, ikon, stok
             FROM buku
             ORDER BY judul ASC"
        );

        while ($row = mysqli_fetch_assoc($result)) {
            $bukuList[] = $row;
        }
    }
} catch (Throwable $error) {
    $errorBuku = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Stella Library — Temukan Dunia Baru</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="navbar">

    <a href="index.php" class="brand">
        <span class="brand-icon">S</span>

        <span>
            <strong>STELLA LIBRARY</strong>
            <small>DISCOVER YOUR NEXT STORY</small>
        </span>
    </a>

    <nav class="nav-links">
        <a href="#beranda" class="active">Beranda</a>
        <a href="#katalog">Katalog Buku</a>
        <a href="#tentang">Tentang</a>
    </nav>

    <div class="nav-actions">

        <?php if ($isTamu): ?>

            <span class="btn-login">Tamu</span>

            <a href="login.php" class="btn-login">
                Login
            </a>

            <a href="register.php" class="btn-primary btn-small">
                Daftar
            </a>

        <?php elseif ($isLogin): ?>

            <span class="btn-login">
                <?= e($nama !== '' ? $nama : 'Pengguna') ?>
            </span>

            <?php if ($isAdmin): ?>

                <a href="admin/dashboard.php" class="btn-primary btn-small">
                    Dashboard
                </a>

            <?php elseif ($isAnggota): ?>

                <a href="user/dashboard.php" class="btn-primary btn-small">
                    Akun Saya
                </a>

            <?php else: ?>

                <a href="user/dashboard.php" class="btn-primary btn-small">
                    Akun Saya
                </a>

            <?php endif; ?>

            <a href="logout.php" class="btn-login">
                Logout
            </a>

        <?php else: ?>

            <a href="login.php" class="btn-login">
                Login
            </a>

            <a href="register.php" class="btn-primary btn-small">
                Daftar
            </a>

        <?php endif; ?>

    </div>

</header>


<section class="hero" id="beranda">

    <div class="hero-content">

        <div class="eyebrow">
            <span class="status-dot"></span>
            SELAMAT DATANG DI STELLA LIBRARY
        </div>

        <h1>
            Temukan Dunia Baru<br>
            <span>Melalui Buku.</span>
        </h1>

        <p class="hero-description">
            Jelajahi berbagai koleksi buku dan temukan bacaan
            yang sesuai dengan minatmu. Pengetahuan baru
            dimulai dari satu halaman.
        </p>

        <form action="index.php#katalog" method="GET" class="search-box">
            <span class="search-icon">⌕</span>

            <input
                type="text"
                name="cari"
                placeholder="Cari judul, pengarang, atau kategori..."
                value="<?= e($keyword) ?>"
                aria-label="Cari buku"
            >

            <button type="submit">Cari Buku</button>
        </form>

        <div class="hero-note">
            <span class="note-icon">✓</span>
            Temukan bacaan favoritmu dengan mudah.
        </div>

    </div>


    <div class="hero-visual">

        <span class="decor decor-one"></span>
        <span class="decor decor-two"></span>

        <div class="visual-card">

            <span class="visual-label">YOUR NEXT ADVENTURE</span>

            <div class="book-stack">

                <div class="book book-one">
                    <span>STELLA EDITION</span>
                    <strong>Little<br>Discoveries</strong>
                    <small>VOL. 01</small>
                </div>

                <div class="book book-two">
                    <span>THE LIBRARY</span>
                    <strong>Worlds<br>Within</strong>
                    <small>VOL. 02</small>
                </div>

                <div class="book book-three">
                    <span>NEW STORIES</span>
                    <strong>Beyond<br>Pages</strong>
                    <small>VOL. 03</small>
                </div>

            </div>

            <div class="visual-caption">
                <div>
                    <strong>Read More, Discover More.</strong>
                    <p>Setiap buku membawa cerita baru.</p>
                </div>

                <span class="caption-arrow">↗</span>
            </div>

        </div>

        <div class="floating-badge">
            <span class="badge-icon">✦</span>

            <span>
                <strong>Discover Something New</strong>
                <small>Mulai petualangan membaca</small>
            </span>
        </div>

    </div>

</section>



<section class="feature-strip">

    <div class="feature-item">
        <span class="feature-icon">▤</span>

        <div>
            <strong>Koleksi Buku</strong>
            <p>Beragam buku dalam satu katalog.</p>
        </div>
    </div>

    <div class="feature-item">
        <span class="feature-icon">⌕</span>

        <div>
            <strong>Pencarian Mudah</strong>
            <p>Temukan buku berdasarkan judul.</p>
        </div>
    </div>

    <div class="feature-item">
        <span class="feature-icon">✧</span>

        <div>
            <strong>Jelajahi Cerita</strong>
            <p>Temukan pengetahuan baru setiap hari.</p>
        </div>
    </div>

</section>



<section class="catalog-section" id="katalog">

    <div class="section-heading">

        <div>
            <span class="section-kicker">KOLEKSI KAMI</span>

            <h2>
                Jelajahi <span>Katalog Buku.</span>
            </h2>

            <p>
                Temukan buku yang sesuai dengan minat dan kebutuhanmu.
            </p>
        </div>

        <a href="index.php#katalog" class="text-link">
            Semua Koleksi ↓
        </a>

    </div>


    <?php if ($errorBuku): ?>

        <div class="empty-state">
            <div class="empty-icon">!</div>
            <h3>Data buku belum dapat ditampilkan</h3>
            <p>Periksa kembali koneksi database dan tabel buku.</p>
        </div>

    <?php elseif (count($bukuList) > 0): ?>

        <div class="book-grid">

            <?php foreach ($bukuList as $buku): ?>

                <?php
                $stok = (int) ($buku['stok'] ?? 0);
                $judul = $buku['judul'] ?? 'Tanpa Judul';
                $pengarang = $buku['pengarang'] ?? 'Tidak diketahui';
                $kategori = $buku['kategori'] ?? 'Umum';
                $ikon = trim((string) ($buku['ikon'] ?? ''));

                if ($ikon === '') {
                    $ikon = '📚';
                }

                $linkPinjam = 'user/pinjam.php?id=' .
                    urlencode((string) $buku['id_buku']);
                ?>

                <article class="book-card">

                    <div class="book-cover">

                        <div class="cover-placeholder">

                            <span>STELLA LIBRARY</span>

                            <strong><?= e($ikon) ?><br><?= e($judul) ?></strong>

                            <small><?= e($pengarang) ?></small>

                        </div>

                        <span class="availability <?= $stok > 0 ? 'available' : 'unavailable' ?>">
                            <?= $stok > 0 ? 'Tersedia' : 'Stok Habis' ?>
                        </span>

                    </div>


                    <div class="book-info">

                        <span class="book-category">
                            <?= e(ucwords(str_replace(['_', '-'], ' ', $kategori))) ?>
                        </span>

                        <h3><?= e($judul) ?></h3>

                        <p class="book-author">
                            Oleh <?= e($pengarang) ?>
                        </p>

                        <div class="book-card-footer">

                            <span class="stock-info">
                                Stok: <?= $stok ?>
                            </span>

                            <?php if ($isAdmin): ?>

                                <a
                                    href="admin/dashboard.php"
                                    class="book-action"
                                    title="Kelola buku"
                                >↗</a>

                            <?php elseif ($isLogin && $isAnggota): ?>

                                <?php if ($stok > 0): ?>

                                    <a
                                        href="<?= e($linkPinjam) ?>"
                                        class="book-action"
                                        title="Ajukan peminjaman"
                                    >↗</a>

                                <?php else: ?>

                                    <span
                                        class="book-action disabled"
                                        title="Stok habis"
                                    >×</span>

                                <?php endif; ?>

                            <?php else: ?>

                                <a
                                    href="login.php?redirect=<?= urlencode($linkPinjam) ?>"
                                    class="book-action"
                                    title="Login untuk meminjam"
                                >↗</a>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <div class="empty-icon">⌕</div>

            <h3>Buku tidak ditemukan</h3>

            <p>
                <?php if ($keyword !== ''): ?>
                    Tidak ada buku yang cocok dengan pencarian
                    "<?= e($keyword) ?>".
                <?php else: ?>
                    Belum ada buku yang ditambahkan ke katalog.
                <?php endif; ?>
            </p>

            <?php if ($keyword !== ''): ?>

                <a href="index.php#katalog" class="btn-primary">
                    Tampilkan Semua Buku
                </a>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</section>



<section class="about-section" id="tentang">

    <div class="about-symbol">S.</div>

    <div class="about-content">

        <span class="section-kicker">TENTANG STELLA</span>

        <h2>Membuka Jendela Pengetahuan.</h2>

        <p>
            Stella Library adalah website perpustakaan yang membantu
            pengunjung menjelajahi koleksi buku dengan mudah.
            Pengunjung dapat melihat katalog, sedangkan anggota
            yang telah login dapat mengajukan peminjaman sesuai
            ketentuan perpustakaan.
        </p>

    </div>

</section>



<footer class="footer">

    <a href="index.php" class="footer-brand">
        STELLA<span>.</span>
    </a>

    <p>© <?= date('Y') ?> Stella Library. Semua hak dilindungi.</p>

</footer>

</body>
</html>
