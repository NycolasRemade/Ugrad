<?php
session_start();
require_once 'Servidor/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo_instituicao = trim($_POST['codigo_instituicao'] ?? '');
    if ($codigo_instituicao) {

        try {

            // Checa se o código da instituição existe e não expirou (limite no 'INTERVAL 1 WEEK')
            $stmt = $pdo->prepare('
                    SELECT id_instituicao, tipo_usuario FROM codigo_instituicao
                    WHERE codigo = ?
                    AND CURRENT_DATE() < DATE_ADD(data_criacao, INTERVAL 1 WEEK)
                ');
            $stmt->execute([$codigo_instituicao]);
            $codigo = $stmt->fetch();

            if ($codigo) {

                // guarda o código temporariamente para ser utilizado na página
                // da criação da conta, onde o usuário é criado no banco de dados
                $_SESSION['usuario_id_instituicao'] = $codigo['id_instituicao'];
                $_SESSION['usuario_tipo']           = $codigo['tipo_usuario'];
                header('Location: criar_conta.php');
                exit;
                
            } else {
                $erro = 'Código inválido. Nenhuma instituição foi encontrada com este código.';
            }
        } catch (\PDOException $e) {
            $erro = 'Ocorreu um erro ao processar sua solicitação. Tente novamente.';
        }
    } else {
        $erro = 'Verifique o código e tente novamente.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
    <title>Seleção de Instituição</title>
</head>
<body>

<div></div>
    <div id="logo_1" style="scale: 70%; color: white; margin-top: 5em; margin-left: 4em">
        <img src="Fotos/Logo_alt1.png" alt="logo_alt1" style="margin-top: -4.5em; rotate: 3.96deg; scale: 90%; margin-left: -6em">
        <div>
            <h1 class="meringue">Ugrad</h1>
        </div>
    </div>

<svg xmlns="http://www.w3.org/2000/svg" width="952" height="520" viewBox="0 0 952 520" fill="none" id="papel_login">
<path d="M392 3.8147e-06L0 234.899V519.399H951.5V0L392 3.8147e-06Z" fill="white"/>
</svg>

<div class="container">
    <h2></h2>
    <p>Insira abaixo o código de identificação fornecido pela sua instituição.</p>

    <?php if (!empty($erro)): ?>
        <div>
            <?= htmlspecialchars($erro); ?>
        </div>
    <?php endif; ?>

    <form action="codigo_instituicao.php" method="POST">
        <div class="form-group">
            <label for="codigo_instituicao">Código da Instituição</label>
            <input id="codigo_instituicao" name="codigo_instituicao" required>
        </div>

        <button type="submit">→</button>
    </form>

</div>

</body>
</html>
