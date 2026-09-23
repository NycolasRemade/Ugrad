<?php
require __DIR__ . '/includes/data.php';
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/oi.php';
?>

<div class="dash-menu">
  <div class="dash-avatar"></div>
  <a class="dash-link" href="pesquisa.php?tab=usuarios">Ver Usuários</a>
  <a class="dash-link" href="pesquisa.php?tab=turmas">Ver Turmas</a>
  <a class="dash-link" href="pesquisa.php?tab=instituicoes">Ver Instituições</a>
  <a class="dash-link" href="pesquisa.php?tab=projetos">Ver Projetos</a>
  <a href="pesquisa.php"><button type="button" class="dash-search-btn">Pesquisar</button></a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
