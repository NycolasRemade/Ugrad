<?php
session_start();
require_once 'Servidor/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $tipo = $_SESSION['usuario_tipo'] ?? 0;

    if (!empty($nome) && $email && !empty($senha) && $tipo > 0) {
        try {

            $stmt = $pdo->prepare('INSERT INTO usuarios(nome, email, senha, tipo) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nome, $email, $senha, $tipo]);
            // $stmt = $pdo->prepare('INSERT INTO extra_usuarios(id_usuario, id_instituicao) VALUES (?, ?)');
            // $stmt->execute([$pdo->lastInsertId(), $_SESSION['usuario_id_instituicao']]);
            header('Location: dashboard.php');
            exit;
            
        } catch (\PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente mais tarde.';
        }
    } else {
        $erro = 'Senha ou Email incorretos!.';
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
            <input type="text" id="nome" name="nome" placeholder="Nome" required>
            <input type="text" id="email" name="email" placeholder="Email" required>
            <input type="password" id="senha" name="senha" placeholder="Senha" required>
            <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Confirmar senha" required>
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
    <script type="module">
        const campoSenha = document.getElementById("senha");
        const campoSenha2 = document.getElementById("confirma_senha");
        document.querySelector("form").onsubmit = function(e) {
            if (campoSenha.value !== campoSenha2.value) {
                e.preventDefault();
                alert("As senhas não coincidem!");
            }
        };
    </script>
</body>
</html>
