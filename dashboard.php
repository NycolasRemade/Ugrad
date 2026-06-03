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

$stmt = $pdo->prepare('
    SELECT * FROM projetos 
    INNER JOIN proj_membros membros
    ON membros.id_projeto = projetos.id
    WHERE membros.id_convidado = ?
');
$stmt->execute([$_SESSION['usuario_id']]);
$projetos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel - Ugrad</title>
    <link rel="stylesheet" href="styles.css"> </head>
<body>
    <header id="dashboard-header">
        <div class="logo-text">Ugrad</div>
        <div class="search-bar">Pesquisa de Projetos</div>
        <div class="user-menu">
            Conta
            <span class="notificacao"></span>
        </div>
    </header>

    <main class="dashboard-container">
        <div class="welcome-area">
            <h2>Olá, <?= htmlspecialchars($dados['nome']); ?>!</h2>
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>

        <section class="projetos-secao">
            <div class="secao-header">
                <h3>Meus projetos</h3>
                <button class="btn-novo">+ Novo projeto</button>
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
                    <div class="projeto-card box">
                        <p class="projeto-titulo"><?= htmlspecialchars($proj['nome']); ?></p>
                        <div class="projeto-membros">
                            <?php foreach ($membros as $m): ?>
                                <img class="membro-avatar" src="data:image/jpeg;base64,<?= base64_encode($m['imagem_perfil']); ?>" alt="Membro">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>