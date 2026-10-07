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
    <link rel="stylesheet" href="<?= $baseUrl ?? '' ?>styles.css">
<!-- Esses códigos eram pra colocar o ícone do site, porém só funciona quando estiver em servidor real -->
    <link rel="icon" type="image/png" href="/Fotos/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/Fotos/favicon.svg" />
    <link rel="shortcut icon" href="/Fotos/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/Fotos/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Ugrad" />
    <link rel="manifest" href="/Fotos/site.webmanifest" />
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