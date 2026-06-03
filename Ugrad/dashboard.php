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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Painel - Ugrad</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div>Ugrad</div>
        <div>Pesquisa de Projetos</div>
        <div>
            Conta
            <span class="notificacao"></span>
        </div>
    </header>

    <main>
        <h2><a href="logout.php">Olá, <?= htmlspecialchars($dados['nome']); ?>!</a></h2>

        <section class="projetos">
            <h3>Meus projetos</h3>
            <?php
            foreach ($projetos as $proj): 
                $membros = $pdo->prepare('SELECT usuarios.imagem_perfil FROM usuarios INNER JOIN proj_membros WHERE proj_membros.id_projeto = ?');
            ?>
                <div>
                    <div>
                        <?php foreach ($membros as $membro): ?>
                            <?= $membro['imagem_perfil']; ?>
                        <?php endforeach; ?>
                    </div>
                    <p><?= htmlspecialchars($proj['nome']); ?></p>
                </div>
            <?php endforeach; ?>
            <button class="btn">+ Novo projeto</button>
        </section>
    </main>
</body>
</html>
