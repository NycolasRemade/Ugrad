<?php
require __DIR__ . '/includes/auth_admin.php';
require __DIR__ . '/includes/data.php';

$eu = find_usuario((int)$_SESSION['usuario_id']);
if (!$eu) {
    // a conta logada não existe mais: encerra a sessão
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$pageTitle = 'Minha conta';
$foto = avatar_data_uri($eu['imagem_perfil'] ?? null);
$pendentes = total_reportagens();
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Minha conta</h1>

<div class="detail-grid">
  <?php if ($foto): ?>
    <div class="detail-avatar" style="background-image:url('<?= $foto ?>');background-size:cover;background-position:center;"></div>
  <?php else: ?>
    <div class="detail-avatar"></div>
  <?php endif; ?>
  <div class="detail-fields">
    <div>
      <div class="field-label">Nome</div>
      <div class="field-value"><?= htmlspecialchars($eu['nome']) ?></div>
    </div>
    <div>
      <div class="field-label">Tipo</div>
      <div class="field-value"><?= htmlspecialchars(tipo_label($eu['tipo_nome'])) ?></div>
    </div>
    <div>
      <div class="field-label">E-mail</div>
      <div class="field-value"><?= htmlspecialchars($eu['email']) ?></div>
    </div>
    <div>
      <div class="field-label">Conta criada em</div>
      <div class="field-value"><?= htmlspecialchars(formatar_data($eu['data_criacao'])) ?></div>
    </div>
  </div>
</div>

<div class="block">
  <h2>Pendências</h2>
  <div class="desc-box">
    <?php if ($pendentes > 0): ?>
      Há <strong><?= $pendentes ?></strong> denúncia<?= $pendentes > 1 ? 's' : '' ?> aguardando análise nas telas de detalhe.
    <?php else: ?>
      Nenhuma denúncia pendente.
    <?php endif; ?>
  </div>
</div>

<div class="actions-row">
  <a class="btn btn-outline" href="index.php">Voltar ao painel</a>
  <form method="post" action="logout.php">
    <button type="submit" class="btn btn-danger">Sair</button>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
