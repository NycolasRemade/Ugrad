<?php
/**
 * includes/turma_detail.php
 * Espera: $turma, $membros, $codigos
 */
?>
<div class="detail-grid no-avatar">
  <div class="detail-fields">
    <div>
      <div class="field-label">ID da turma</div>
      <div class="field-value">#<?= $turma['id'] ?></div>
    </div>
    <div>
      <div class="field-label">Contas na turma</div>
      <div class="field-value"><?= count($membros) ?></div>
    </div>
    <div>
      <div class="field-label">Nome</div>
      <div class="field-value"><?= htmlspecialchars($turma['nome']) ?></div>
    </div>
    <div>
      <div class="field-label">Instituição</div>
      <div class="field-value">
        <a class="detail-trigger" href="instituicao.php?id=<?= $turma['id_instituicao'] ?>" data-type="instituicao" data-id="<?= $turma['id_instituicao'] ?>"><?= htmlspecialchars($turma['instituicao_nome']) ?></a>
      </div>
    </div>
  </div>
</div>

<div class="block">
  <h2>Alunos e membros</h2>
  <?php if (empty($membros)): ?>
    <p class="dim" style="font-size:var(--fs-sm);">Nenhuma conta vinculada a esta turma.</p>
  <?php endif; ?>
  <?php foreach ($membros as $m): ?>
    <div class="user-row">
      <a class="user-name detail-trigger" href="usuario.php?id=<?= $m['id'] ?>" data-id="<?= $m['id'] ?>" data-type="usuario"><?= htmlspecialchars($m['nome']) ?><?php if (!$m['ativada']): ?> <span class="dim">(desativada)</span><?php endif; ?></a>
      <span class="dim"><?= htmlspecialchars($m['email']) ?></span>
      <span class="dim"><?= htmlspecialchars(tipo_label($m['tipo_nome'])) ?></span>
    </div>
  <?php endforeach; ?>
</div>

<div class="block">
  <h2>Códigos de acesso</h2>
  <div class="chip-row">
    <?php if (empty($codigos)): ?>
      <p class="dim" style="font-size:var(--fs-sm);">Nenhum código gerado para esta turma.</p>
    <?php endif; ?>
    <?php foreach ($codigos as $c): ?>
      <span class="chip"><?= htmlspecialchars($c['codigo']) ?> <span class="dim">· <?= htmlspecialchars(tipo_label($c['tipo_nome'])) ?> · <?= htmlspecialchars(formatar_data($c['data_criacao'])) ?></span></span>
    <?php endforeach; ?>
  </div>
</div>

<div class="actions-row">
  <form method="post" action="turma.php?id=<?= $turma['id'] ?>" class="ajax-action-form">
    <input type="hidden" name="acao" value="gerar_codigo">
    <button type="submit" class="btn btn-dark">Gerar código de aluno</button>
  </form>

  <form method="post" action="turma.php?id=<?= $turma['id'] ?>" class="ajax-action-form" data-confirm="Excluir esta turma? Os alunos ficam sem turma e os códigos dela são apagados.">
    <input type="hidden" name="acao" value="excluir">
    <button type="submit" class="btn btn-danger">Excluir turma</button>
  </form>
</div>
