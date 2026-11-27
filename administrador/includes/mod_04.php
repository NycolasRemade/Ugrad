<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Admin';
require_once __DIR__ . '/mod_10.php';

// Evita que o navegador guarde a página em cache — sem isso, uma
// atualização no PHP/CSS/JS pode não aparecer até um hard-refresh.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · Ugrad Admin</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php $cssPath = __DIR__ . '/../css/painel_estilo.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
<link rel="stylesheet" href="<?= isset($basePath) ? $basePath : '' ?>css/painel_estilo.css?v=<?= $cssVersion ?>">
<?php acessibilidade_head(); ?>
</head>
<body>
<?php acessibilidade_widget(); ?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<div class="app-shell">

  <header class="topbar">
    <a class="logo" href="<?= file_exists(__DIR__ . '/../../dashboard.php') ? '../dashboard.php' : 'index.php' ?>">Ugrad</a>
    <nav class="topnav" aria-label="Principal">
      <a href="arq_01.php?foco=1" class="topnav-pill">Pesquisa Geral</a>
    </nav>
    <?php $pendentes = function_exists('total_reportagens') ? total_reportagens() : 0; ?>
    <a class="account" aria-label="Conta<?= $pendentes > 0 ? ', ' . $pendentes . ' reportagem(ns) pendente(s)' : '' ?>" href="<?= file_exists(__DIR__ . '/../../config_conta.php') ? '../config_conta.php' : 'arq_07.php' ?>">
      <span>Conta</span>
      <span class="avatar-wrap">
        <span class="avatar-dot" aria-hidden="true"></span>
        <?php if ($pendentes > 0): ?>
          <span class="avatar-badge" aria-hidden="true"><?= $pendentes > 9 ? '9+' : $pendentes ?></span>
        <?php endif; ?>
      </span>
    </a>
  </header>

  <main class="content" id="conteudo" tabindex="-1">
    <h1 class="sr-only"><?= htmlspecialchars($pageTitle) ?></h1>
    <?php if ($msg = flash_get()): ?>
      <div class="flash" role="status"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
