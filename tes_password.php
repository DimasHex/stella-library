<?php
require_once 'koneksi.php';

$username = 'admin';
$passwordTes = 'admin123';

$stmt = mysqli_prepare(
    $conn,
    "SELECT password FROM users WHERE username = ? and role = 'admin' LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "Akun admin tidak ditemukan.";
} elseif (password_verify($passwordTes, $user['password'])) {
    echo "Password cocok. Masalahnya kemungkinan ada di alur login.";
} else {
    echo "Password tidak cocok dengan hash di database.";
}

mysqli_stmt_close($stmt);
?>