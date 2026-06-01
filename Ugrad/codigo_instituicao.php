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
                $erro = 'Código inválido. Nenhuma instituição ativa foi encontrada com este código.';
            }
        } catch (\PDOException $e) {
            $erro = 'Ocorreu um erro ao processar sua solicitação. Tente novamente.';
        }
    } else {
        $erro = 'Por favor, digite um código válido.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vincular Instituição</title>
</head>
<body>

<div class="container">
    <h2>Vincular Instituição</h2>
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

        <button type="submit">Confirmar</button>
    </form>

</div>

</body>
</html>
