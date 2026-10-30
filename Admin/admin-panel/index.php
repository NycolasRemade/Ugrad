<?php
require __DIR__ . '/includes/auth_admin.php';
require __DIR__ . '/includes/data.php';
$pageTitle = 'Dashboard';
$stats = db_estatisticas_contas();
require __DIR__ . '/includes/header.php';
?>

<div class="stats-row">
  <div class="stat-card">
    <span class="stat-value"><?= $stats['total'] ?></span>
    <span class="stat-label">Contas cadastradas</span>
  </div>
  <div class="stat-card">
    <span class="stat-value stat-ok"><?= $stats['ativas'] ?></span>
    <span class="stat-label">Ativas</span>
  </div>
  <div class="stat-card">
    <span class="stat-value stat-off"><?= $stats['desativadas'] ?></span>
    <span class="stat-label">Desativadas</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= $stats['instituicoes'] ?></span>
    <span class="stat-label">Instituições</span>
  </div>
  <?php if ($stats['logaram_24h'] !== null): ?>
  <div class="stat-card">
    <span class="stat-value"><?= $stats['logaram_1h'] ?></span>
    <span class="stat-label">Logaram na última hora</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= $stats['logaram_24h'] ?></span>
    <span class="stat-label">Logaram nas últimas 24h</span>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($stats['por_tipo'])): ?>
<div class="stats-breakdown">
  <?php foreach ($stats['por_tipo'] as $t): ?>
    <span class="stats-chip"><?= htmlspecialchars(tipo_label($t['tipo_nome'])) ?>: <strong><?= $t['total'] ?></strong></span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="dash-wrap">
  <div class="dash-menu">
      <a class="dash-link" href="pesquisa.php?tab=usuarios">Ver Usuários</a>
      <a class="dash-link" href="pesquisa.php?tab=turmas">Ver Turmas</a>
      <a class="dash-link" href="pesquisa.php?tab=instituicoes">Ver Instituições</a>
      <a class="dash-link" href="pesquisa.php?tab=projetos">Ver Projetos</a>
      <a href="pesquisa.php"><button type="button" class="dash-search-btn">Pesquisar</button></a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
