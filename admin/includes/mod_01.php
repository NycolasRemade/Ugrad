<?php
/**
 * includes/mod_01.php
 * Garante que só um administrador logado (tipo 5) acesse as páginas
 * do painel. Inclua isso ANTES de qualquer outra coisa no topo de
 * cada arquivo de entrada (index.php, arq_01.php, arq_02.php,
 * arq_03.php, arq_06.php).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Login "normal" do site: fica uma pasta acima deste painel (../login.php).
// Este painel não tem tela de login própria.
$siteCompleto = file_exists(__DIR__ . '/../../login.php');
$loginUrl = $siteCompleto ? '../login.php' : 'login.php';

// Modo local: se o painel está sozinho (sem o login do site ao lado) e é
// aberto do próprio computador (localhost), entra direto como o primeiro
// administrador. Dentro do site completo, ou acessado de outra máquina,
// isso NÃO vale e o login normal continua obrigatório.
$ehLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (!isset($_SESSION['usuario_id']) && !$siteCompleto && $ehLocal) {
    require_once __DIR__ . '/mod_03.php';
    $adm = $pdo->query('SELECT id FROM usuarios WHERE tipo = 5 AND ativada = 1 ORDER BY id LIMIT 1')->fetchColumn();
    if ($adm) {
        $_SESSION['usuario_id'] = (int)$adm;
        $_SESSION['usuario_tipo'] = 5;
    }
}

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
