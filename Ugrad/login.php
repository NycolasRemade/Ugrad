<?php
session_start();
require_once 'Servidor/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if ($email && !empty($senha)) {
        try {
            $stmt = $pdo->prepare('SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {

                session_regenerate_id(true);

                $_SESSION['usuario_id']   = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];

                header('Location: dashboard.php');
                exit;

            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } catch (\PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente mais tarde.';
        }
    } else {
        $erro = 'Por favor, insira um e-mail válido e preencha a senha.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div>
    <h2>Acessar Conta</h2>

    <?php if (!empty($erro)): ?>
        <div id="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autocomplete="email">
        </div>
        
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required autocomplete="current-password">
        </div>

        <button type="submit">Entrar</button>

        
    </form>
</div>

</body>
</html>