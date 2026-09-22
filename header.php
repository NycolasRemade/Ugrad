<?php


$stmt_imagem = $pdo->prepare(
    'SELECT imagem_perfil
    FROM usuarios
    WHERE id = ?'
);

if (!isset($conta) || $conta){
    $stmt_imagem->execute([$_SESSION['usuario_id']]);
    $imagem = $stmt_imagem->fetch();
}
//
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$title?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<?php if(isset($href)): ?>
<body onLoad="window.scroll(0, 0)">


    <div id="navbar">
        <img src="Fotos/Polygon 2.png" alt="navbar">
        <a href="<?=$href?>">
            <h1 class="meringue">Ugrad</h1>
        </a>

        <?php if(isset($pdp)): ?>
        <a href="pesquisa_de_projetos.php">
            <h2 class="meringue">Pesquisa de Projetos</h2>
        </a>
        <?php endif;?>

        <?php if(!isset($conta)): ?>
        <a href="config_conta.php" class="conta">
            <h3>Conta</h3>
            <div style="background-image: url('data:image/webp;base64,<?= base64_encode($imagem['imagem_perfil']) ?>')" alt="Foto de Perfil"></div>
        </a>
        <?php endif;?>

    </div>
<?php endif;?>