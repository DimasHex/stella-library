```php
<?php
session_start();


$_SESSION = [];


if (ini_get('session.use_cookies')) {
    $parameter = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parameter['path'],
        $parameter['domain'],
        $parameter['secure'],
        $parameter['httponly']
    );
}

// Hancurkan sesi.
session_destroy();

// Kembali ke halaman login.
header('Location: login.php');
exit;
?>
```