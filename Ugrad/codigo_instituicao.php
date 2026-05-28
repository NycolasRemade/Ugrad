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

    $codigo_instituicao = trim($_POST['codigo_instituicao'] ?? '');
    if ($codigo_instituicao) {

        try {

            $stmt_check = $pdo->prepare('SELECT id FROM codigo_instituicao WHERE codigo = ? AND CURRENT_DATE() < DATE_ADD(data_criacao, INTERVAL 1 WEEK)');
            $stmt_check->execute([$codigo_instituicao]);

            if ($stmt_check->fetch()) { //Isso não faz muito sentido, não precisa ser um condicional, e se o fetch() desse erro,
                                        //não seria esse if() que detectaria e sim o PDO

                $stmt_usuario = $pdo->prepare("SELECT id FROM extra_usuarios WHERE id = ?");
                $stmt_usuario->execute([$_SESSION['usuario_id']]);

                
                $stmt_insert = $pdo->prepare('INSERT INTO extra_usuarios (id, instituicao) VALUES (?, ?)');
                $stmt_insert->execute([$_SESSION['usuario_id'], $codigo_instituicao]);
            

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
