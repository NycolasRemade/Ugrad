<?php
/**
 * includes/usuario_detail.php
 * Espera: $usuario (linha de USUARIO_SELECT), $projetos, $avaliacoes
 */
$foto = avatar_data_uri($usuario['imagem_perfil'] ?? null);
?>
<div class="detail-grid">
  <?php if ($foto): ?>
    <div class="detail-avatar" style="background-image:url('<?= $foto ?>');background-size:cover;background-position:center;"></div>
  <?php else: ?>
    <div class="detail-avatar"></div>
  <?php endif; ?>
  <div class="detail-fields">
    <div>
      <div class="field-label">ID do usuário</div>
      <div class="field-value">#<?= $usuario['id'] ?></div>
    </div>
    <div>
      <div class="field-label">Tipo</div>
      <div class="field-value"><?= htmlspecialchars(tipo_label($usuario['tipo_nome'])) ?></div>
    </div>
    <div>
      <div class="field-label">Nome</div>
      <div class="field-value"><?= htmlspecialchars($usuario['nome']) ?></div>
    </div>
    <div>
      <div class="field-label">Turma</div>
      <div class="field-value"><?= htmlspecialchars($usuario['turma_nome'] ?? '—') ?></div>
    </div>
    <div>
      <div class="field-label">E-mail</div>
      <div class="field-value"><?= htmlspecialchars($usuario['email']) ?></div>
    </div>
    <div>
      <div class="field-label">Instituição</div>
      <div class="field-value"><?= htmlspecialchars($usuario['instituicao_nome'] ?? '—') ?></div>
    </div>
    <div>
      <div class="field-label">Data de criação</div>
      <div class="field-value"><?= htmlspecialchars(formatar_data($usuario['data_criacao'])) ?></div>
    </div>
    <div>
      <div class="field-label">Status</div>
      <div class="field-value" style="color: <?= $usuario['ativada'] ? 'var(--green)' : 'var(--red)' ?>;"><?= $usuario['ativada'] ? 'Ativa' : 'Desativada' ?></div>
    </div>
  </div>
</div>

<div class="block">
  <h2>Descrição</h2>
  <div class="desc-box"><?= htmlspecialchars($usuario['descricao'] ?: 'Sem descrição.') ?></div>
</div>

<div class="block">
  <h2>Projetos</h2>
  <div class="chip-row">
    <?php if (empty($projetos)): ?>
      <p class="dim" style="font-size:var(--fs-sm);">Nenhum projeto vinculado.</p>
    <?php endif; ?>
    <?php foreach ($projetos as $p): ?>
      <span class="chip"><?= htmlspecialchars($p['nome']) ?></span>
    <?php endforeach; ?>
  </div>
</div>

<div class="block">
  <h2>
    Avaliações
    <?php if (count($avaliacoes) > 0): ?><span class="badge-count"><?= count($avaliacoes) ?></span><?php endif; ?>
  </h2>

  <?php if (empty($avaliacoes)): ?>
    <p class="dim" style="font-size:var(--fs-sm);">Nenhuma reportagem registrada.</p>
  <?php endif; ?>

  <?php foreach ($avaliacoes as $a): ?>
    <div class="review-card" data-report-id="<?= $a['id'] ?>">
      <div class="review-meta">
        <span class="review-project">
          <?= $a['tipo_rep_nome'] === 'PROJETO' ? htmlspecialchars($a['projeto_nome'] ?? 'Projeto removido') : 'Perfil do usuário' ?>
        </span>
        <span class="status-tag ativo"><?= htmlspecialchars(tipo_label($a['tipo_rep_nome'] ?? 'GENERICO')) ?></span>
      </div>
      <div class="review-footer">
        <span>Reportado por: <strong><?= htmlspecialchars($a['reportado_por']) ?></strong></span>
        <span><?= htmlspecialchars(formatar_data($a['data_reportagem'])) ?></span>
      </div>
      <div class="review-actions">
        <form method="post" action="reportagem.php" class="ajax-action-form inline-form" data-confirm="Apagar o item reportado? Essa ação não pode ser desfeita.">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <input type="hidden" name="acao" value="apagar">
          <input type="hidden" name="voltar" value="usuario.php?id=<?= $usuario['id'] ?>">
          <button type="submit" class="btn-mini btn-mini-danger">Apagar</button>
        </form>
        <form method="post" action="reportagem.php" class="ajax-action-form inline-form" data-confirm="Ignorar esta reportagem?">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <input type="hidden" name="acao" value="ignorar">
          <input type="hidden" name="voltar" value="usuario.php?id=<?= $usuario['id'] ?>">
          <button type="submit" class="btn-mini btn-mini-dark">Ignorar</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="actions-row">
  <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Enviar link de redefinição de senha para este usuário?">
    <input type="hidden" name="acao" value="senha">
    <button type="submit" class="btn btn-outline">Solicitar alteração de senha</button>
  </form>

  <?php if ($usuario['ativada']): ?>
    <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Desativar esta conta?">
      <input type="hidden" name="acao" value="desativar">
      <button type="submit" class="btn btn-danger">Desativar conta</button>
    </form>
  <?php else: ?>
    <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Reativar esta conta?">
      <input type="hidden" name="acao" value="reativar">
      <button type="submit" class="btn btn-dark">Reativar conta</button>
    </form>
  <?php endif; ?>

  <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Tem certeza que deseja excluir esta conta? Essa ação não pode ser desfeita.">
    <input type="hidden" name="acao" value="excluir">
    <button type="submit" class="btn btn-danger">Excluir conta</button>
  </form>
</div>
