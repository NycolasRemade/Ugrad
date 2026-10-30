<?php
/**
 * includes/projeto_detail.php
 * Espera: $projeto, $categorias, $membros, $reportagens
 */
$foto = avatar_data_uri($projeto['img'] ?? null);
?>
<div class="detail-grid<?= $foto ? '' : ' no-avatar' ?>">
  <?php if ($foto): ?>
    <div class="detail-avatar" style="background-image:url('<?= $foto ?>');background-size:cover;background-position:center;"></div>
  <?php endif; ?>
  <div class="detail-fields">
    <div>
      <div class="field-label">ID do projeto</div>
      <div class="field-value">#<?= $projeto['id'] ?></div>
    </div>
    <div>
      <div class="field-label">Estado</div>
      <div class="field-value"><?= htmlspecialchars(estado_label($projeto['estado_nome'])) ?></div>
    </div>
    <div>
      <div class="field-label">Nome</div>
      <div class="field-value"><?= htmlspecialchars($projeto['nome']) ?></div>
    </div>
    <div>
      <div class="field-label">Data de criação</div>
      <div class="field-value"><?= htmlspecialchars(formatar_data($projeto['data_criacao'])) ?></div>
    </div>
  </div>
</div>

<div class="block">
  <h2>Categorias</h2>
  <div class="chip-row">
    <?php if (empty($categorias)): ?>
      <p class="dim" style="font-size:var(--fs-sm);">Nenhuma categoria.</p>
    <?php endif; ?>
    <?php foreach ($categorias as $c): ?>
      <span class="chip"><?= htmlspecialchars($c['nome']) ?></span>
    <?php endforeach; ?>
  </div>
</div>

<div class="block">
  <h2>Descrição</h2>
  <div class="desc-box"><?= nl2br(htmlspecialchars($projeto['descricao'] ?: 'Sem descrição.')) ?></div>
</div>

<div class="block">
  <h2>História</h2>
  <div class="desc-box"><?= nl2br(htmlspecialchars($projeto['historia'] ?: 'Sem história.')) ?></div>
</div>

<div class="block">
  <h2>Membros</h2>
  <?php if (empty($membros)): ?>
    <p class="dim" style="font-size:var(--fs-sm);">Nenhum membro vinculado.</p>
  <?php endif; ?>
  <?php foreach ($membros as $m): ?>
    <div class="user-row">
      <a class="user-name detail-trigger" href="usuario.php?id=<?= $m['id'] ?>" data-id="<?= $m['id'] ?>" data-type="usuario"><?= htmlspecialchars($m['nome']) ?></a>
      <span class="dim"><?= htmlspecialchars(membro_status_label($m['status_nome'])) ?></span>
      <span class="dim"></span>
    </div>
  <?php endforeach; ?>
</div>

<div class="block">
  <h2>
    Denúncias
    <?php if (count($reportagens) > 0): ?><span class="badge-count"><?= count($reportagens) ?></span><?php endif; ?>
  </h2>

  <?php if (empty($reportagens)): ?>
    <p class="dim" style="font-size:var(--fs-sm);">Nenhuma denúncia registrada.</p>
  <?php endif; ?>

  <?php foreach ($reportagens as $r): ?>
    <div class="review-card" data-report-id="<?= $r['id'] ?>">
      <div class="review-meta">
        <span class="review-project"><?= htmlspecialchars($projeto['nome']) ?></span>
        <span class="status-tag ativo">Projeto</span>
      </div>
      <div class="review-footer">
        <span>Reportado por: <strong><?= htmlspecialchars($r['reportado_por']) ?></strong></span>
        <span><?= htmlspecialchars(formatar_data($r['data_reportagem'])) ?></span>
      </div>
      <div class="review-actions">
        <form method="post" action="reportagem.php" class="ajax-action-form inline-form" data-confirm="Apagar o projeto reportado? Essa ação não pode ser desfeita.">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input type="hidden" name="acao" value="apagar">
          <input type="hidden" name="voltar" value="projeto.php?id=<?= $projeto['id'] ?>">
          <button type="submit" class="btn-mini btn-mini-danger">Apagar</button>
        </form>
        <form method="post" action="reportagem.php" class="ajax-action-form inline-form" data-confirm="Ignorar esta denúncia?">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input type="hidden" name="acao" value="ignorar">
          <input type="hidden" name="voltar" value="projeto.php?id=<?= $projeto['id'] ?>">
          <button type="submit" class="btn-mini btn-mini-dark">Ignorar</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="actions-row">
  <form method="post" action="projeto.php?id=<?= $projeto['id'] ?>" class="ajax-action-form" data-confirm="Excluir este projeto? Imagens, comentários, membros e categorias dele também serão apagados. Essa ação não pode ser desfeita.">
    <input type="hidden" name="acao" value="excluir">
    <button type="submit" class="btn btn-danger">Excluir projeto</button>
  </form>
</div>
