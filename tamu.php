<?php
session_start();


if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}


$_SESSION['role'] = 'tamu';
$_SESSION['nama'] = 'Tamu';

header('Location: index.php');
exit;