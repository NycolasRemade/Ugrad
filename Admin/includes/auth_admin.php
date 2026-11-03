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

// Login "normal" do site: fica uma pasta acima deste painel (../login.php).
// Este painel não tem tela de login própria.
$loginUrl = file_exists(__DIR__ . '/../../login.php') ? '../login.php' : 'login.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . $loginUrl);
    exit;
}

if ((int)($_SESSION['usuario_tipo'] ?? 0) !== 5) {
    // Não é administrador: volta pro dashboard do site (se este painel estiver
    // dentro do site completo); senão, pro login.
    $destino = file_exists(__DIR__ . '/../../dashboard.php') ? '../dashboard.php' : $loginUrl;
    header('Location: ' . $destino);
    exit;
}
