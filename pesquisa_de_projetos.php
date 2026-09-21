<?php
session_start();
require_once 'Servidor/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesquisa'])){
    
}


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

        <

    </div>

</div>

</body>
</html>