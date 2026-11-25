<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_inspect = $_GET['userid'];

$stmt_user = $pdo->prepare(
   'SELECT u.nome, u.email, u.descricao, u.imagem_perfil, t.nome AS tipo_nome 
    FROM usuarios u
    JOIN tipos_usuario t ON u.tipo = t.id
    WHERE u.id = ?'
);
$stmt_user->execute([$usuario_inspect]);
$usuario = $stmt_user->fetch();

$sucesso = (isset($_SESSION['sucesso'])) ? $_SESSION['sucesso'] : '';
$erro = (isset($_SESSION['erro'])) ? $_SESSION['erro'] : '';

//
//////////////////////////////////
$title = 'Perfil e Configurações';
$href = 'dashboard.php';
include 'header.php';
?>

<div class='config_container'>

    <?php if ($erro): ?>
        <p id='erro'><strong><?= htmlspecialchars($erro) ?></strong></p>

    <?php elseif ($sucesso): ?>
        <p id='message'><strong><?= htmlspecialchars($sucesso) ?></strong></p>

    <?php endif; ?>

    <div id='config_container'>

        <div class='img_container'>
            <div id="kirkle"><div class="config" style="background-image: url('<?= !empty($usuario['imagem_perfil']) ? 'data:image/webp;base64,' . base64_encode($usuario['imagem_perfil']) : 'Fotos/Ugrad logo cinza.png' ?>')" alt="Foto de Perfil"></div></div>
        <br>

        <div id='variables_container'>

        <form method="POST">
            <label for="nome">Nome</label><br>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
        </form>

        <br>

        <form method="POST">
            <label for="email">E-mail</label><br>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
        </form>

        <br>
        </div>

        <br>
        </div>
        
        <div id='descr_container'>
<br>
        <form method="POST">
            <label for="descricao">Descrição</label><br>
            <div class='textarea_fix'>
            <textarea id="descricao" name="descricao" rows="4" cols="50" placeholder="Descrição curta sobre você ou sua instituição"><?= htmlspecialchars($usuario['descricao'] ?? '') ?></textarea>
            </div>
            </div>
        </form>
            </div>

</body>
</html>