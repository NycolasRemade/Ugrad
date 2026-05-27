<?php
session_start();
require_once 'config.php';
// if (!isset($_SESSION['usuario_id'])) {
//     header('Location: login.php');
//     exit;
// }
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo_instituicao = filter_input(INPUT_POST, 'codigo_instituicao', FILTER_VALIDATE_INT);

    if ($codigo_instituicao) {

        try {

            $stmt_check = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? AND tipo = 4');
            $stmt_check->execute([$codigo_instituicao]);
            $instituicao_valida = $stmt_check->fetch();

            if ($instituicao_valida) {

                $stmt_extra = $pdo->prepare("SELECT id FROM extra_usuarios WHERE id = ?");
                $stmt_extra->execute([$_SESSION['usuario_id']]);
                $existe_registro_extra = $stmt_extra->fetch();

                if ($existe_registro_extra) {

                    $stmt_update = $pdo->prepare('UPDATE extra_usuarios SET instituicao = ? WHERE id = ?');
                    $stmt_update->execute([$codigo_instituicao, $_SESSION['usuario_id']]);
                } else {
                    $stmt_insert = $pdo->prepare('INSERT INTO extra_usuarios (id, instituicao) VALUES (?, ?)');
                    $stmt_insert->execute([$_SESSION['usuario_id'], $codigo_instituicao]);
                }

                $erro = 'Sucesso! Sua conta foi vinculada à instituição.';
                
            } else {
                $erro = 'Código inválido. Nenhuma instituição ativa foi encontrada com este número.';
            }
        } catch (\PDOException $e) {
            $erro = 'Ocorreu um erro ao processar sua solicitação. Tente novamente.';
        }
    } else {
        $erro = 'Por favor, digite um código numérico válido.';
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