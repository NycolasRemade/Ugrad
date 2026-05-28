<?php
session_start();
require_once 'config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo_instituicao = trim($_POST['codigo_instituicao'] ?? '');
    if ($codigo_instituicao) {

        try {

            //Checa se o código da instituição existe e não expirou (limite no 'INTERVAL 1 WEEK')
            $stmt_check = $pdo->prepare('
                    SELECT id FROM codigo_instituicao
                    WHERE codigo = ?
                    AND CURRENT_DATE() < DATE_ADD(data_criacao, INTERVAL 1 WEEK)
                ');
            $stmt_check->execute([$codigo_instituicao]);
            $codigo_valido = $stmt_check->fetch();

            if ($codigo_valido) {

                //guarda o código temporariamente para ser utilizado na página
                //da criação da conta, onde o usuário é criado no banco de dados
                $_SESSION['codigo_instituicao'] = $codigo_instituicao;
                header('Location: dashboard.php');
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

    <form action="vincular_instituicao.php" method="POST">
        <div class="form-group">
            <label for="codigo_instituicao">Código da Instituição</label>
            <input type="number" id="codigo_instituicao" name="codigo_instituicao" required>
        </div>

        <button type="submit">Confirmar</button>
    </form>

</div>

</body>
</html>
