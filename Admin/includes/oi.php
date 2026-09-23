<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Admin';

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php $cssPath = __DIR__ . '/../css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
<link rel="stylesheet" href="<?= isset($basePath) ? $basePath : '' ?>css/style.css?v=<?= $cssVersion ?>">
</head>
<body>
<div class="app-shell">

  <main class="content">
    <?php if ($msg = flash_get()): ?>
      <div class="flash"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
