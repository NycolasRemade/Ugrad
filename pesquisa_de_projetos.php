<?php
session_start();
require_once 'Servidor/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$projetos_qtd = $pdo->query(
    'SELECT COUNT(*) from projetos
    WHERE estado = 3'
)->fetch();

$projetos_id = array();


for ($i=0; $i <= 20; $i++) { 
    $p = rand(1,$projetos_qtd);
    $projetos_id[$i] = $p;
}





$usuario_id = $_SESSION['usuario_id'];
$usuario_id_instituicao = $_SESSION['usuario_id_instituicao'] ?? null;

//////////////////////////////////
$title = 'Pesquisa de Projetos';
$href = 'dashboard.php';
$pdp = true;
include 'header.php'
?>

<div class='centrao'>

<div id='title_paper'>
    <h1 class='meringue'>Pesquisa de Projetos</h1>
    <img src="Fotos/Polygon 3.png" alt="title">
</div>

<div id='main_paper'>

    <?php if (!empty($erro)): ?>
        <p style="color: red; padding: 0 20px;"><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    

</div>

</div>

</body>
</html>