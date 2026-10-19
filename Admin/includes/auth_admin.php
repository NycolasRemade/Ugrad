<?php
/**
 * includes/auth_admin.php
 * Garante que só um administrador logado (tipo 5) acesse as páginas
 * do painel. Inclua isso ANTES de qualquer outra coisa no topo de
 * cada arquivo de entrada (index.php, pesquisa.php, usuario.php,
 * instituicao.php, reportagem.php).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ((int)($_SESSION['usuario_tipo'] ?? 0) !== 5) {
    header('Location: ../dashboard.php');
    exit;
}
