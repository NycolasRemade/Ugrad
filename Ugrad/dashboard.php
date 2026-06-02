<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}


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
        <div class="logo">Ugrad</div>
        <div class="pesquisa">Pesquisa de Projetos</div>
        <div class="conta">
            Conta
            <span class="notificacao">1</span>
        </div>
    </header>

    <main>
        <h2>Olá, <?php echo htmlspecialchars($usuario); ?>!</h2>

        <section class="projetos">
            <h3>Meus projetos</h3>
            <?php foreach ($projetos as $proj): ?>
                <div class="projeto">
                    <div class="icones">
                        <span>⚪</span><span>⚪</span><span>⚪</span>
                    </div>
                    <p><?php echo htmlspecialchars($proj['nome']); ?></p>
                </div>
            <?php endforeach; ?>
            <button class="btn">+ Novo projeto</button>
        </section>
    </main>
</body>
</html>
