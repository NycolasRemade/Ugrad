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
            $stmt = $pdo->prepare(
                   'SELECT id_instituicao, tipo_usuario FROM codigo_instituicao
                    WHERE codigo = ?
                   AND CURRENT_DATE() < DATE_ADD(data_criacao, INTERVAL 1 WEEK)'
                );
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
    <title>Seleção de Instituição - Ugrad</title>
</head>
<body class="centrao">

    <div id="logo_1">
        <img src="Fotos/Logo_alt1.png" alt="logo_alt1">
        <div>
            <h1 class="meringue">Ugrad</h1>
        </div>
    </div>

    <div id="papel_login" class="box">
        
        <h2>Acessar Instituição</h2>
        <p style="margin-bottom: 25px; line-height: 1.4; font-size: 14px; color: #555;">
            Insira abaixo o código de identificação fornecido pela sua instituição para prosseguir com o cadastro.
        </p>

        <?php if (!empty($erro)): ?>
            <div id="erro">
                <?= htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form action="codigo_instituicao.php" method="POST">
            <div class="form-group">
                <label for="codigo_instituicao">Código da Instituição</label>
                <input type="text" id="codigo_instituicao" name="codigo_instituicao" required>
                <button type="submit">→</button>
            </div>
        </form>
    </div>

</body>
</html>