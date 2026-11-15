<?php
$conn = new mysqli('localhost', 'root', '', 'nama_database');
if ($conn->connect_error) {
    http_response_code(500);
    exit('Koneksi database gagal.');
}
$conn->set_charset('utf8mb4');

// Contoh akses: baca.php?buku=1
$bukuId = filter_input(INPUT_GET, 'buku', FILTER_VALIDATE_INT);

if (!$bukuId) {
    http_response_code(400);
    exit('Buku tidak valid.');
}

$stmt = $conn->prepare(
    'SELECT id_buku, judul, pengarang, penerbit, tahun_terbit, kategori, isi
     FROM buku WHERE id_buku = ?'
);
$stmt->bind_param('i', $bukuId);
$stmt->execute();
$buku = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$buku) {
    http_response_code(404);
    exit('Buku tidak ditemukan.');
}

// Pisahkan isi menjadi paragraf (dipisah baris kosong)
$isi = trim((string) ($buku['isi'] ?? ''));
$paragraf = $isi === '' ? [] : preg_split('/\R{2,}/', $isi);

function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($buku['judul']) ?></title>

    <!-- Terapkan pengaturan tersimpan sebelum halaman tampil (mencegah kedipan) -->
    <script>
        (function () {
            try {
                var s = JSON.parse(localStorage.getItem('modeBaca') || '{}');
                var root = document.documentElement;
                if (s.tema) root.setAttribute('data-theme', s.tema);
                if (s.font) root.style.setProperty('--font-baca', s.font);
                if (s.ukuran) root.style.setProperty('--ukuran-baca', s.ukuran + 'px');
            } catch (err) {}
        })();
    </script>

    <style>
        :root {
            --font-baca: Georgia, serif;
            --ukuran-baca: 18px;
        }
        [data-theme="light"] {
            --bg: #ffffff;
            --teks: #222222;
            --panel: #f3f3f3;
            --garis: #dddddd;
        }
        [data-theme="dark"] {
            --bg: #121212;
            --teks: #e0e0e0;
            --panel: #1e1e1e;
            --garis: #333333;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--teks);
            font-family: Arial, sans-serif;
            transition: background .2s, color .2s;
        }

        .toolbar {
            position: sticky;
            top: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background: var(--panel);
            border-bottom: 1px solid var(--garis);
            font-size: 14px;
        }
        .toolbar label { display: flex; align-items: center; gap: 6px; }
        .toolbar select, .toolbar button {
            padding: 6px 10px;
            border: 1px solid var(--garis);
            border-radius: 6px;
            background: var(--bg);
            color: var(--teks);
            cursor: pointer;
            font-size: 14px;
        }

        .kontainer {
            max-width: 720px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }
        .kontainer h1 { font-size: 1.4rem; margin-bottom: 4px; }
        .kontainer .meta { margin: 0 0 24px; opacity: .7; font-size: .9rem; }

        .isi {
            font-family: var(--font-baca);
            font-size: var(--ukuran-baca);
            line-height: 1.8;
        }
        .isi p { margin: 0 0 1.2em; }
        .kosong { opacity: .7; font-style: italic; }
    </style>
</head>
<body>

<div class="toolbar">
    <label>Font
        <select id="pilihFont">
            <option value="Georgia, serif">Georgia</option>
            <option value="'Times New Roman', Times, serif">Times New Roman</option>
            <option value="Arial, Helvetica, sans-serif">Arial</option>
            <option value="Verdana, Geneva, sans-serif">Verdana</option>
            <option value="Tahoma, sans-serif">Tahoma</option>
            <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
            <option value="'Courier New', Courier, monospace">Courier New</option>
        </select>
    </label>

    <label>Ukuran
        <button type="button" id="kecil">A−</button>
        <span id="labelUkuran">18</span>px
        <button type="button" id="besar">A+</button>
    </label>

    <button type="button" id="tombolTema">🌙 Mode Gelap</button>
</div>

<main class="kontainer">
    <h1><?= e($buku['judul']) ?></h1>
    <p class="meta">
        <?= e($buku['pengarang']) ?>
        &middot; <?= e($buku['penerbit']) ?> (<?= e($buku['tahun_terbit']) ?>)
        &middot; <?= e($buku['kategori']) ?>
    </p>

    <article class="isi">
        <?php if (!$paragraf): ?>
            <p class="kosong">Isi buku ini belum tersedia.</p>
        <?php else: ?>
            <?php foreach ($paragraf as $p): ?>
                <p><?= nl2br(e(trim($p))) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
    </article>
</main>

<script>
    var root = document.documentElement;
    var pilihFont = document.getElementById('pilihFont');
    var labelUkuran = document.getElementById('labelUkuran');
    var tombolTema = document.getElementById('tombolTema');

    var MIN_UKURAN = 14, MAX_UKURAN = 32;

    var setelan = { tema: 'light', font: 'Georgia, serif', ukuran: 18 };
    try {
        var tersimpan = JSON.parse(localStorage.getItem('modeBaca') || '{}');
        for (var k in tersimpan) setelan[k] = tersimpan[k];
    } catch (err) {}

    function simpan() {
        try { localStorage.setItem('modeBaca', JSON.stringify(setelan)); } catch (err) {}
    }

    function terapkan() {
        root.setAttribute('data-theme', setelan.tema);
        root.style.setProperty('--font-baca', setelan.font);
        root.style.setProperty('--ukuran-baca', setelan.ukuran + 'px');
        pilihFont.value = setelan.font;
        labelUkuran.textContent = setelan.ukuran;
        tombolTema.textContent = setelan.tema === 'dark' ? '☀️ Mode Terang' : '🌙 Mode Gelap';
    }

    pilihFont.addEventListener('change', function () {
        setelan.font = this.value;
        terapkan(); simpan();
    });

    document.getElementById('kecil').addEventListener('click', function () {
        if (setelan.ukuran > MIN_UKURAN) { setelan.ukuran -= 2; terapkan(); simpan(); }
    });

    document.getElementById('besar').addEventListener('click', function () {
        if (setelan.ukuran < MAX_UKURAN) { setelan.ukuran += 2; terapkan(); simpan(); }
    });

    tombolTema.addEventListener('click', function () {
        setelan.tema = setelan.tema === 'dark' ? 'light' : 'dark';
        terapkan(); simpan();
    });

    terapkan();
</script>

</body>
</html>