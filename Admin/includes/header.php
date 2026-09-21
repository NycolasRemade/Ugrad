<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · Ugrad Admin</title>
<?php $cssPath = __DIR__ . '/../css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
<link rel="stylesheet" href="<?= isset($basePath) ? $basePath : '' ?>css/style.css?v=<?= $cssVersion ?>">
</head>
<body>
<div class="app-shell">

  <header class="topbar">
    <a class="logo" href="index.php">Ugrad</a>
    <nav class="topnav">
      <a href="pesquisa.php" class="topnav-pill">Pesquisar de Projeto</a>
    </nav>
    <a class="account" href="#">
      <span>Conta</span>
      <span class="avatar-wrap">
        <span class="avatar-dot" aria-hidden="true"></span>
        <?php $pendentes = function_exists('total_reportagens') ? total_reportagens() : 0; ?>
        <?php if ($pendentes > 0): ?>
          <span class="avatar-badge"><?= $pendentes > 9 ? '9+' : $pendentes ?></span>
        <?php endif; ?>
      </span>
    </a>
  </header>

  <main class="content">
    <?php if ($msg = flash_get()): ?>
      <div class="flash"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
