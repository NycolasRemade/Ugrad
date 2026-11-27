<?php
/**
 * includes/mod_06.php
 * Espera: $instituicao, $professores, $turmas, $codigos
 */
$foto = avatar_data_uri($instituicao['imagem_perfil'] ?? null);
?>
<div class="detail-grid">
  <?php if ($foto): ?>
    <div class="detail-avatar" aria-hidden="true" style="background-image:url('<?= $foto ?>');background-size:cover;background-position:center;"></div>
  <?php else: ?>
    <div class="detail-avatar" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="detail-fields">
    <div>
      <div class="field-label">ID da instituição</div>
      <div class="field-value">#<?= $instituicao['id'] ?></div>
    </div>
    <div>
      <div class="field-label">Status</div>
      <div class="field-value" style="color: <?= $instituicao['ativada'] ? 'var(--green)' : 'var(--red)' ?>;"><?= $instituicao['ativada'] ? 'Ativa' : 'Desativada' ?></div>
    </div>
    <div>
      <div class="field-label">Nome</div>
      <div class="field-value"><?= htmlspecialchars($instituicao['nome']) ?></div>
    </div>
    <div>
      <div class="field-label">E-mail</div>
      <div class="field-value"><?= htmlspecialchars($instituicao['email']) ?></div>
    </div>
    <div>
      <div class="field-label">Data de criação</div>
      <div class="field-value"><?= htmlspecialchars(formatar_data($instituicao['data_criacao'])) ?></div>
    </div>
  </div>
</div>

<div class="block">
  <h2>Descrição</h2>
  <div class="desc-box"><?= htmlspecialchars($instituicao['descricao'] ?: 'Sem descrição.') ?></div>
</div>

<div class="block">
  <h2>Professores</h2>
  <?php if (empty($professores)): ?>
    <p class="dim" style="font-size:var(--fs-sm);">Nenhum professor vinculado.</p>
  <?php endif; ?>
  <?php foreach ($professores as $p): ?>
    <div class="user-row">
      <span class="radio" aria-hidden="true"></span>
      <a class="user-name detail-trigger" href="arq_02.php?id=<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" data-type="usuario"><?= htmlspecialchars($p['nome']) ?></a>
      <span class="dim"><?= htmlspecialchars($p['email']) ?></span>
      <span class="dim"></span>
    </div>
  <?php endforeach; ?>
</div>

<div class="block">
  <h2>Turmas</h2>
  <div class="chip-row">
    <?php if (empty($turmas)): ?>
      <p class="dim" style="font-size:var(--fs-sm);">Nenhuma turma cadastrada.</p>
    <?php endif; ?>
    <?php foreach ($turmas as $t): ?>
      <a class="chip detail-trigger" href="arq_04.php?id=<?= $t['id'] ?>" data-type="turma" data-id="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="block">
  <h2>Códigos de acesso</h2>
  <div class="chip-row">
    <?php if (empty($codigos)): ?>
      <p class="dim" style="font-size:var(--fs-sm);">Nenhum código gerado.</p>
    <?php endif; ?>
    <?php foreach ($codigos as $c): ?>
      <?php $expirado = strtotime($c['data_criacao'] . ' +7 days') < strtotime('today'); ?>
      <span class="chip"><?= htmlspecialchars($c['codigo']) ?> <span class="dim">· <?= htmlspecialchars($c['turma_nome'] ?? 'Instituição') ?> · <?= $expirado ? 'expirado em ' : 'válido até ' ?><?= date('d/m/Y', strtotime($c['data_criacao'] . ' +7 days')) ?></span>
        <?php if (file_exists(__DIR__ . '/../../codigo_instituicao.php') && !$expirado): ?>
          <a class="dim" href="../codigo_instituicao.php?codigo=<?= urlencode($c['codigo']) ?>" target="_blank" rel="noopener">· abrir cadastro</a>
        <?php endif; ?>
      </span>
    <?php endforeach; ?>
  </div>
</div>

<div class="actions-row">
  <a class="btn btn-outline" href="arq_02.php?id=<?= $instituicao['id'] ?>&amp;exportar=1" download>Exportar dados</a>

  <form method="post" action="arq_03.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form">
    <input type="hidden" name="acao" value="gerar_codigo">
    <input type="hidden" name="turma_id" value="0">
    <button type="submit" class="btn btn-dark">Gerar</button>
  </form>

  <form method="post" action="arq_03.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Gerar uma nova senha temporária para esta instituição e enviar por e-mail? A senha atual deixa de funcionar.">
    <input type="hidden" name="acao" value="senha">
    <button type="submit" class="btn btn-outline">Solicitar alteração de senha</button>
  </form>

  <?php if ($instituicao['ativada']): ?>
    <form method="post" action="arq_03.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Desativar esta instituição?">
      <input type="hidden" name="acao" value="desativar">
      <button type="submit" class="btn btn-danger">Desativar</button>
    </form>
  <?php else: ?>
    <form method="post" action="arq_03.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Reativar esta instituição?">
      <input type="hidden" name="acao" value="reativar">
      <button type="submit" class="btn btn-dark">Reativar</button>
    </form>
  <?php endif; ?>

  <form method="post" action="arq_03.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Tem certeza que deseja excluir esta instituição?">
    <input type="hidden" name="acao" value="excluir">
    <button type="submit" class="btn btn-danger">Excluir</button>
  </form>
</div>
