<?php
require_once 'koneksi.php';

$username = 'admin';
$passwordBaru = 'admin123';

$hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

echo "Tes hash yang baru dibuat: ";
echo password_verify($passwordBaru, $hash) ? "COCOK<br>" : "TIDAK COCOK<br>";

$stmt = mysqli_prepare(
    $conn,
    "UPDATE users SET password = ? WHERE username = ? AND role = 'admin'"
);

mysqli_stmt_bind_param($stmt, 'ss', $hash, $username);
mysqli_stmt_execute($stmt);

echo "Baris yang diperbarui: " . mysqli_stmt_affected_rows($stmt) . "<br>";

$stmt2 = mysqli_prepare(
    $conn,
    "SELECT password FROM users WHERE username = ? AND role = 'admin' LIMIT 1"
);

mysqli_stmt_bind_param($stmt2, 's', $username);
mysqli_stmt_execute($stmt2);

$result = mysqli_stmt_get_result($stmt2);
$row = mysqli_fetch_assoc($result);

echo "Tes hash dari database: ";
echo ($row && password_verify($passwordBaru, $row['password']))
    ? "COCOK"
    : "TIDAK COCOK";

mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt2);
?>