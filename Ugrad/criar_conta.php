<?php
session_start();
require_once 'config.php';
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

            //terminar de adicionar campos no HTML e aqui
            //também inserir código da instituição em algum lugar
            $stmt = $pdo->prepare('INSERT INTO usuarios(email, senha) VALUES (?, ?)');
            $stmt->execute([$email, $senha]);
            header('Location: dashboard.php');
            exit;
            
        } catch (\PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente mais tarde.';
        }
    } else {
        $erro = 'Email ou senha inválidos.';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Ugrad</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div>
        <div>Ugrad</div>
        <h2>Cadastro</h2>
        <form action="criar_conta.php" method="POST">
            <input type="text" id="email" placeholder="Email" required>
            <input type="password" id="senha" placeholder="Senha" required>
            <button type="submit">→</button>
        </form>
        <div>
            <a href="login.php">Já tenho uma conta Ugrad</a>
        </div>
        <!--button class="google-btn">
            imagina ter que programar a integração com o treco de login do google kkkkkkkkk
            <img src="imagens/Google.jpeg" alt="Google logo">
            <span>Entrar com o Google</span>
        </button-->
      </div>
</body>
</html>
