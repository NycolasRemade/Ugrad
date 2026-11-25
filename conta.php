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

    <div id='config_container' style='margin-top:-100px'>

        <div class='img_container'>
            <div id="kirkle"><div class="config" style="background-image: url('<?= !empty($usuario['imagem_perfil']) ? 'data:image/webp;base64,' . base64_encode($usuario['imagem_perfil']) : 'Fotos/Ugrad logo cinza.png' ?>')" alt="Foto de Perfil"></div></div>
    </div>
            <br>

        <div id='variables_container' style='margin-left: 100px; margin-top:50px'>

            <h1><?= htmlspecialchars($usuario['nome'] ?? '') ?></h1>

        <br>

            <h2><?= htmlspecialchars($usuario['email'] ?? '') ?></h2>

        <br>
        
            <div class='textarea_fix box' style='padding:30px; margin-left:-30px'>
            <p id="descricao"><?= htmlspecialchars($usuario['descricao'] ?? 'Sem descrição') ?></p>
            </div>


</body>
</html>