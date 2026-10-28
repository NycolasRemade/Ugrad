<?php
/**
 * login.php — entrada do painel administrativo.
 *
 * Confere e-mail + senha da conta e só deixa passar quem é administrador
 * (tipo 5) e está com a conta ativa. Se o site completo já tiver uma
 * tela de login própria, basta apontar o redirecionamento do
 * includes/auth_admin.php pra ela e este arquivo deixa de ser usado
 * (o login do site só precisa gravar usuario_id e usuario_tipo na sessão).
 */
require __DIR__ . '/includes/data.php'; // conexão + registrar_acesso()

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Já está logado como administrador: vai direto pro painel.
if (isset($_SESSION['usuario_id']) && (int)($_SESSION['usuario_tipo'] ?? 0) === 5) {
    header('Location: index.php');
    exit;
}

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');

    $stmt = $pdo->prepare('SELECT id, senha, tipo, ativada FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    // Confere a senha mesmo quando o e-mail não existe (hash falso), pra a
    // resposta demorar igual e não revelar quais e-mails estão cadastrados.
    $hash = $u ? $u['senha'] : '$2y$10$Mb3PIaS.V1emsDdhGqubcO.m3LTMfmBgY.UCf4KC62aVDtCgP2mGW';
    $senhaOk = password_verify($senha, $hash);

    if ($u && $senhaOk) {
        if ((int)$u['tipo'] !== 5) {
            $erro = 'Esta conta não tem permissão de administrador.';
        } elseif (!$u['ativada']) {
            $erro = 'Esta conta está desativada.';
        } else {
            session_regenerate_id(true); // evita fixação de sessão
            $_SESSION['usuario_id'] = (int)$u['id'];
            $_SESSION['usuario_tipo'] = (int)$u['tipo'];
            registrar_acesso((int)$u['id']);
            header('Location: index.php');
            exit;
        }
    } else {
        usleep(300000); // freia tentativa em massa
        $erro = 'E-mail ou senha incorretos.';
    }
}

$cssPath = __DIR__ . '/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar · Ugrad Admin</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?= $cssVersion ?>">
</head>
<body>
<div class="app-shell">

  <header class="topbar">
    <a class="logo" href="login.php">Ugrad</a>
  </header>

  <main class="content">
    <div class="login-wrap">
      <form class="login-card" method="post" autocomplete="on">
        <h1 class="page-title">Entrar no painel</h1>

        <?php if ($erro !== ''): ?>
          <div class="action-banner error" role="alert"><span><?= htmlspecialchars($erro) ?></span></div>
        <?php endif; ?>

        <label class="login-label" for="email">E-mail</label>
        <input class="login-input" type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required autofocus autocomplete="username">

        <label class="login-label" for="senha">Senha</label>
        <input class="login-input" type="password" id="senha" name="senha" required autocomplete="current-password">

        <button type="submit" class="dash-search-btn">Entrar</button>
      </form>
    </div>
  </main>

</div>
</body>
</html>
