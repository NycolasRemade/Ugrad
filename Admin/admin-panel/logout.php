<?php
/**
 * logout.php — encerra a sessão. Só aceita POST (botão "Sair" da página
 * Conta), pra um link/imagem de terceiros não conseguir deslogar o admin.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: conta.php');
    exit;
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ' . (file_exists(__DIR__ . '/../login.php') ? '../login.php' : 'login.php'));
exit;
