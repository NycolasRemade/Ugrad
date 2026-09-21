<?php
/**
 * includes/instituicao_detail.php
 * Espera: $instituicao, $professores, $turmas
 */
$foto = avatar_data_uri($instituicao['imagem_perfil'] ?? null);
?>
<div class="detail-panel-inner">

  <div class="detail-grid">
    <?php if ($foto): ?>
      <img class="detail-avatar" src="<?= $foto ?>" alt="Logo de <?= htmlspecialchars($instituicao['nome']) ?>">
    <?php else: ?>
      <div class="detail-avatar"></div>
    <?php endif; ?>
    <div class="detail-fields">
      <div><span class="field-label">ID da instituição</span></div>
      <div><span class="field-label">Status</span></div>
      <div class="field-value">#<?= $instituicao['id'] ?></div>
      <div class="field-value"><?= $instituicao['ativada'] ? 'Ativa' : 'Desativada' ?></div>

      <div><span class="field-label">Nome</span></div>
      <div><span class="field-label">E-mail</span></div>
      <div class="field-value"><?= htmlspecialchars($instituicao['nome']) ?></div>
      <div class="field-value"><?= htmlspecialchars($instituicao['email']) ?></div>

      <div><span class="field-label">Data de criação</span></div>
      <div><span class="field-label">Código da instituição</span></div>
      <div class="field-value"><?= htmlspecialchars(formatar_data($instituicao['data_criacao'])) ?></div>
      <div class="field-value"><?= htmlspecialchars($instituicao['codigo'] ?: '—') ?></div>
    </div>
  </div>

  <div class="block">
    <h2>Descrição</h2>
    <div class="desc-box"><?= htmlspecialchars($instituicao['descricao'] ?: 'Sem descrição.') ?></div>
  </div>

  <div class="block">
    <h2>Professores</h2>
    <?php if (empty($professores)): ?>
      <p style="font-size:13px;color:var(--text-muted)">Nenhum professor vinculado.</p>
    <?php endif; ?>
    <?php foreach ($professores as $p): ?>
      <div class="user-row" style="grid-template-columns: 22px 1fr;">
        <span class="radio"></span>
        <a class="user-name detail-trigger" href="usuario.php?id=<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" data-type="usuario"><?= htmlspecialchars($p['nome']) ?></a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="block">
    <h2>Turmas</h2>
    <div class="chip-row">
      <?php if (empty($turmas)): ?>
        <p style="font-size:13px;color:var(--text-muted)">Nenhuma turma cadastrada.</p>
      <?php endif; ?>
      <?php foreach ($turmas as $t): ?>
        <div class="chip"><?= htmlspecialchars($t['nome']) ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="actions-row ajax-action-form">
    <input type="hidden" name="acao" value="gerar_codigo">
    <button type="submit" class="btn btn-dark">Gerar código</button>
  </form>

  <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="actions-row ajax-action-form" data-confirm="Enviar link de redefinição de senha?">
    <input type="hidden" name="acao" value="senha">
    <button type="submit" class="btn btn-dark">Solicitar alteração de senha</button>
  </form>

  <div class="actions-row">
    <?php if ($instituicao['ativada']): ?>
      <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Desativar esta instituição?">
        <input type="hidden" name="acao" value="desativar">
        <button type="submit" class="btn btn-danger">Desativar</button>
      </form>
    <?php else: ?>
      <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Reativar esta instituição?">
        <input type="hidden" name="acao" value="reativar">
        <button type="submit" class="btn btn-dark">Reativar</button>
      </form>
    <?php endif; ?>
    <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Tem certeza que deseja excluir esta instituição?">
      <input type="hidden" name="acao" value="excluir">
      <button type="submit" class="btn btn-danger">Exclusão</button>
    </form>
  </div>

</div>
