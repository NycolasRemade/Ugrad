<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT nome, email FROM usuarios WHERE id = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$dados = $stmt->fetch();

$stmt = $pdo->prepare(
   'SELECT * FROM projetos 
    INNER JOIN proj_membros membros 
    ON membros.id_projeto = projetos.id 
    WHERE membros.id_convidado = ?'
);
$stmt->execute([$_SESSION['usuario_id']]);
$projetos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Painel - Ugrad</title>
    <link rel="stylesheet" href="styles.css"> </head>
<body onLoad="window.scroll(0, 0)">
    
    <div id="navbar">
        <img src="Fotos/Polygon 2.png" alt="navbar">
        <a href="#logo_1">
            <h1 class="meringue">Ugrad</h1>
        </a>
    </div>

    <main class="dashboard-container">
        <div class="welcome-area">
            <h2>Olá, <a href="config_conta.php" style="text-decoration: underline;"><?= htmlspecialchars($dados['nome']); ?></a>!</h2>
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>

        <section class="projetos-secao">
            <div class="secao-header">
                <h3>Meus projetos</h3>
                <a class="btn-novo" href="projetos/criar_projeto.php">+ Novo projeto</a>
            </div>
            
            <div class="projetos-grid">
                <?php
                foreach ($projetos as $proj): 
                    $stmt = $pdo->prepare(
                       'SELECT u.imagem_perfil 
                        FROM usuarios u INNER JOIN proj_membros m
                        ON u.id = m.id_convidado
                        WHERE m.id_projeto = ?'
                    );
                    $stmt->execute([$proj['id']]);
                    $membros = $stmt->fetchAll();
                ?>
                    <a class="projeto-card box" href="editar_projeto">
                        <p class="projeto-titulo"><?= htmlspecialchars($proj['nome']); ?></p>
                        <div class="projeto-membros">
                            <?php foreach ($membros as $m): ?>
                                <img class="membro-avatar" src="data:image/jpeg;base64,<?= base64_encode($m['imagem_perfil']); ?>" alt="">
                            <?php endforeach; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>