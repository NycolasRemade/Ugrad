<?php
session_start();
require_once '../Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['usuario_tipo'] !== 5) {
    header('Location: ../dashboard.php');
    exit;
}

require 'includes/data.php';
$pageTitle = 'Dashboard';
?>

<div class="dash-menu">
    <div class="dash-avatar"></div>
    <a class="dash-link" href="pesquisa.php?tab=usuarios">Ver Usuários</a>
    <a class="dash-link" href="pesquisa.php?tab=turmas">Ver Turmas</a>
    <a class="dash-link" href="pesquisa.php?tab=instituicoes">Ver Instituições</a>
    <a class="dash-link" href="pesquisa.php?tab=projetos">Ver Projetos</a>
    <a href="pesquisa.php"><button type="button" class="dash-search-btn">Pesquisar</button></a>
</div>
</main>
</div>
</body>
</html>