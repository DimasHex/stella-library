-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2026 at 03:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpus`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `id_anggota` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id_anggota`, `nim`, `nama`, `jurusan`, `alamat`, `no_hp`, `created_at`, `user_id`) VALUES
(2, '3337260022', 'febri', 'Teknik Mesin', 'Tangerang', '098765456790', '2026-11-02 13:00:45', 2),
(3, '3337250001', 'siti', 'informatika', 'cilegon', '085182002007', '2026-11-16 11:08:20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id_buku` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pengarang` varchar(50) NOT NULL,
  `penerbit` varchar(100) DEFAULT NULL,
  `tahun_terbit` year(4) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `ikon` varchar(10) DEFAULT NULL,
  `stok` int(11) DEFAULT 1,
  `sinopsis` text DEFAULT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id_buku`, `judul`, `pengarang`, `penerbit`, `tahun_terbit`, `kategori`, `ikon`, `stok`, `sinopsis`, `nama_file`, `created_at`) VALUES
(1, 'Algoritma dan Struktur Data', 'Ibnu Rasyid Mnthe, S.T., M.Kom', 'PT. JASA NIAGA DIGITAL INDONESIA', '2025', 'Pemrograman', '💻', 15, 'Dunia digital yang serba cepat, kemampuan berpikir algoritmik bukan lagi sekadar keahlian teknis—melainkan fondasi utama bagi setiap inovator.  \r\n    Disusun secara sistematis dan aplikatif, buku ini memandu pembaca menelusuri konsep dasar, menganalisis efisiensi, hingga mengimplementasikan algoritma dalam berbagai bahasa yakni Python, PHP, JavaScript, dan Java dengan pendekatan yang sederhana, jelas, dan relevan. ', 'Algoritma dan Struktur Data.pdf', '2026-11-16 11:04:35'),
(2, 'Belajar Pemrograman Python Dasar', 'Ridwan Fadjar Septian', 'POSS-UPI', '2013', 'Pemrograman', '🐍', 8, 'Buku ini hadir bagi yang ingin dasar – dasar Python. Buku ini ditujukan bagi programmer pemula atau yang sudah ahli tapi ingin mencoba bahasa pemrograman Python. Buku ini juga merupakan penghantar untuk manju ke teknologi Python lainnya. Selain itu, dengan menggunakan bahasa Indonesia diharapkan dapat lebih menjaring programmer lokal untuk berlajar bahasa pemrograman Python dan menambah khazanah dunia pemrograman Python di Indonesia. ', 'Belajar Pemrograman Python Dasar.pdf', '2026-11-16 11:04:35'),
(3, 'Belajar Tuntas Rumus & Fungsi Microsoft Excel', 'Sarwandi', '-', '2018', 'Pemrograman', '', 5, 'Ketika kita mendengar kata Excel, maka kita akan terbayang dengan sebuah aplikasi yang multi fungsi, bisa dipakai untuk perhitungan, membuat laporan, manganalisis data, dan masih banyak lagi kegunaan-nya. Aplikasi ini mempunyai banyak kelebihan, membuatnya semakin unggul dan banyak digunakan dalam segala bidang, salah satu kelebihannya adalah formula dan fungsi excel yang bisa mengelola data statistik, keuangan, informasi, dan masih banyak lagi. Formula dan fungsi excel adalah rumus-rumus yang terdapat di dalam excel untuk melakukan perhitungan menggunakan istilah-istilah statistik, matematika, dan logika. ', 'Belajar Tuntas Rumus Fungsi Microsoft Excel.pdf', '2026-11-16 11:04:35'),
(4, 'Jaringan Komputer Dasar', 'Danar, Ahmad, dan Risky', 'CV. Kasih Inovasi Teknologi', '2018', 'Pemrograman', '🖧', 3, 'Buku ini mengupas tentang dasar jaringan komputer mulai dari sejarah jaringan komputer, perangakat jaringan komputer, internet protokol, subnetting dan routing. Dengan memberikan penjelasan, pemahaman dan contoh kasus disertai pembahasanya diharapkan pembaca mudah untuk memahami dan menerapkannya. ', 'Jaringan Komputer Dasar.pdf', '2026-11-16 11:04:35'),
(5, 'Keamanan Siber (Cyber Security)', 'Fujiama Diapodo Silalahi, S.Kom, M.Kom.', 'Yayasan Prima Agus Teknik dengan Universitas STEKOM', '2022', 'Keamanan data', '🔐', 7, 'Cyber security (Keamanan Siber) merupakan suatu aktivitas dengan maksud untuk melindungi perangkat komputer, perangkat mobile, server, sistem elektronik, jaringan, dan data dari beraneka jenis serangan jahat digital. Serangan jahat digital itu biasa dikenal dengan sebutan cyber attack. Dampak dari cyber attack salah satunya yaitu kerugian global. Contohnya pada kasus WannaCry yang sempat mengacaukan dunia beberapa tahun lalu, WannaCry telah menginfeksi setidaknya 230.000 perangkat di 150 negara yang mengakibatkan kerugian kurang lebih US$4 Miliyar secara global. Cara yang biasa digunakan sebuah organisasi atau perusahaan untuk meminimalisir ancaman terhadap cyber security yakni dengan menerapkan standar cyber security. \r\n    Standar cyber security yang paling banyak dikenal saat ini adalah ISO/IEC 27001, NIST Cybersecurity Framework, dan PCI DSS. ISO/IEC 27001 pertama kali diterbitkan di tahun 2005 oleh ISO yang merupakan badan yang mengelola standar internasional dan IEC (International Electrotechnical Commission) yang mengelola standar untuk elektronik. NIST (National Institute of Standards and Technology) Cybersecurity Framework pertama kali diterbitkan pada tahun 2014 yang merupakan standar cyber security untuk organisasi swasta di Amerika Serikat yang pada awalnya hanya ditujukan untuk mengoperasikan infrastruktur vital seperti air dan listrik, namun sekarang sudah banyak digunakan oleh sejumlah negara lain. PCI DSS (Payment Card Industry Data Security Standard) pertama kali diterbitkan di tahun 2004 yang merupakan standar cyber security untuk organisasi yang mengurusi kartu pembayaran seperti kartu kredit.  ', 'Keamanan Siber.pdf', '2026-11-16 11:04:35'),
(6, 'Metode Penelitian Kualitatif & Kuantitatif', 'Hardani, S.Pd., M.Si., dkk.', 'CV. Pustaka Ilmu', '2020', 'Akademik', '📝', 26, 'Ketersediaan sumber belajar yang makin banyak sangat diperlukan oleh para mahasiswa pada semua jenjang (D3, S1, S2 dan S3). Dalam mengembangkan bahan kuliah, para dosen biasanya merujuk kepada berbagai sumber belajar yang relevan. Ketersediaan buku teks yang ditulis sendiri oleh dosen Pembina mata kuliah pada jenjang D3 atau S1 masih jarang. Sesungguhnya, ketersediaan buku teks mata kuliah yang ditulis sendiri oleh dosen pembina mata kuliah itu memiliki beberapa keuntungan. Pertama, dosen yang berpengalaman memiliki penguasaan yang baik mengenai struktur kajian bidang ilmu yang ditekuninya, sehingga buku tersebut akan memiliki keunggulan dibandingkan dengan buku yang ditulis oleh penulis lainnya. Kedua, buku teks jenis ini, akan memudahkan proses pembelajaran, karena baik dosen maupun mahasiswa, dalam proses perkuliahannya, dengan mudah dapat mengikuti struktur kajian keilmuan yang sedang dibahasnya.  ', 'Metode Penelitian Kualitatif Kuantitatif.pdf', '2026-11-16 11:04:35'),
(7, 'Pembelajaran Aplikasi Basis Data (SQL)', 'Agung Baitul dan Miftah Farid', '-', '2016', 'Database', '🗄️', 1, 'Basis Data Structured Query Language (SQL) ini dapat dipergunakan sebaik-baiknya dan dapat dijadikan referensi untuk mahasiswa umum yang ingin mempelajari dan memperdalam tentang database.', 'Pembelajaran Aplikasi Basis Data SQL.pdf', '2026-11-16 11:04:35'),
(8, 'Pengantar Basis Data', 'Arie Gunawan, S.Kom., M.M.S.I.', 'PT. Literasi Nusantara Abadi Grup', '2023', 'Database', '🗄️', 10, 'Basis data adalah komponen penting dalam dunia teknologi informasi, termasuk dalam pengembangan aplikasi dan sistem informasi kesehatan. Basis data digunakan untuk menyimpan dan mengelola informasi dalam jumlah besar dan kompleks, yang memungkinkan pengambilan keputusan yang lebih baik dalam berbagai bidang, termasuk bidang kesehatan. Oleh karena itu, pemahaman yang baik tentang basis data dan kemampuan untuk mengelolanya menjadi keterampilan penting bagi para profesional kesehatan.Buku \"Pengantar Basis Data\" ini ditulis untuk memberikan pemahaman dasar tentang basis data dan cara mengelolanya secara efektif. Buku ini dirancang untuk para pembaca yang ingin mempelajari konsep-konsep dasar dalam basis data, termasuk jenis-jenis basis data, struktur data, model relasional, dan bahasa SQL. Selain itu, buku ini juga membahas topik-topik terkait seperti normalisasi, integritas referensial, dan transaksi.', 'Pengantar Basis Data.pdf', '2026-11-16 11:04:35'),
(9, 'Pengantar ilmu Kriptografi', '-', 'Yayasan Kita Menulis', '2024', 'Keamanan Data', '🛡️', 5, 'Kriptografi, yang berasal dari bahasa Yunani \"kryptos\" (tersembunyi) dan \"graphein\" (menulis), adalah seni dan ilmu menyembunyikan informasi. Dari penggunaan kode rahasia dalam perang kuno hingga enkripsi digital yang melindungi transaksi online Anda hari ini, kriptografi telah berkembang menjadi bidang yang kompleks dan sangat penting dalam era informasi ini. \r\n    Dalam buku ini, kita akan memulai perjalanan kita dengan menelusuri sejarah kriptografi, melihat bagaimana teknik-teknik awal berkembang menjadi sistem yang canggih yang kita gunakan saat ini. Kita akan mendalami prinsip-prinsip dasar yang mendasari semua bentuk kriptografi, memberikan Anda fondasi yang kuat untuk memahami konsep-konsep yang lebih kompleks.', 'Pengantar Ilmu Kriptografi.pdf', '2026-11-16 11:04:35'),
(10, 'Introduction to Logic and Critical Thinking', 'Matthew J. Van Cleave', 'Jim-Zam Co', '2023', 'Akademik', '🎓', 3, 'Ini adalah buku teks pengantar dalam logika dan pemikiran kritis. Tujuan dari buku ini adalah untuk memberikan seperangkat alat dan keterampilan bagi para pembaca yang akan memungkinkan untuk mengidentifikasi dan mengevaluasi argumen. Buku ini dimaksudkan untuk perkuliahan pengantar yang mencakup logika formal dan informal. Dengan demikian, ini bukan buku logika formal, tetapi lebih dekat dengan apa yang akan dipasarkan sebagai “buku teks pemikiran kritis”. Logika formal pada bab 2 dimaksudkan untuk memberikan pengenalan dasar pada logika formal. Secara khusus, bab 2 memperkenalkan beberapa metode formal yang berbeda untuk menentukan apakah suatu argumen valid atau tidak valid (tabel kebenaran, bukti, diagram Venn). Saya membedakan metode formal dengan metode informal untuk menentukan validitas yang disajikan di bab 1. ', 'Pengantar Logika dan Berfikir Kritis.pdf', '2026-11-16 11:04:35'),
(11, 'Pengembangan Web', 'Andi Asari, dkk.', 'Media Nusa Creative', '2023', 'Pemrograman', '🌐', 4, 'Bagian mendasar dari web adalah presentasi informasi tekstual. Markup dokumen adalah proses memasukkan perintah dalam dokumen yang menjelaskan struktur dokumen atau format yang muncul saat dicetak. Markup dokumen adalah bentuk komunikasi yang telah ada selama bertahun-tahun. Pada tahun 1927, Vannevar Bush merancang komputer analog yang dapat menyelesaikan persamaan sederhana. Versi yang lebih besar disebut diferensial penganalisa dikembangkan pada tahun 1930 dan pada tahun 1935 versi mekanik diganti dengan versi elektro-mekanis. ', 'Pengembangan Web.pdf', '2026-11-16 11:04:35'),
(12, 'Sistem Administrasi Jaringan Linux', 'Jumadi M. Parenreng, dkk.', 'UBUNTU', '2019', 'Pemrograman', '🐧', 20, 'Linux pertama kali diciptakan oleh seorang mahasiswa asal Finlandia bernama linus Benedict Torvalds pada tahun 1991. Hasil karyanya dilisensikan secara bebas dan terbuka sehinggah siapa saja boleh mengembangkannya.\r\n    Linux merupakan kernel atau dasar dari sistem operasi tugasnya yaitu membuat komunikasi atau mediator antara aplikasi dan perangkat keras, yang menyediakan pelayanan sistem seperti pengaturan memori untuk proses yang sedang berjalan, pengaturan input- output dan masih banyak lainnya, jadi intinya adalah kernel merupakan suatu penghubung antara software dan hardware. ', 'Sistem Administrasi Linux.pdf', '2026-11-16 11:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_pinjam` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak','Dikembalikan','Terlambat','Dipinjam') NOT NULL DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','anggota') NOT NULL DEFAULT 'anggota'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `username`, `password`, `role`) VALUES
(1, 'siti', 'siti', '$2y$10$X16kiXWBmy872Ya8wFMABux.3xYks.26v7tAaMzxgO.uW8l5KKI4.', 'anggota');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id_anggota`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_pinjam`),
  ADD KEY `id_buku` (`id_buku`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id_anggota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_pinjam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
