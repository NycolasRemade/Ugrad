<?php
/**
 * includes/mod_09.php
 * Espera: $usuario (linha de USUARIO_SELECT), $projetos, $avaliacoes
 */
$foto = avatar_data_uri($usuario['imagem_perfil'] ?? null);
?>
<div class="detail-grid">
  <?php if ($foto): ?>
    <div class="detail-avatar" aria-hidden="true" style="background-image:url('<?= $foto ?>');background-size:cover;background-position:center;"></div>
  <?php else: ?>
    <div class="detail-avatar" aria-hidden="true"></div>
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
      <div class="field-value">
        <?php if (!empty($usuario['id_turma'])): ?>
          <a class="detail-trigger" href="arq_04.php?id=<?= $usuario['id_turma'] ?>" data-type="turma" data-id="<?= $usuario['id_turma'] ?>"><?= htmlspecialchars($usuario['turma_nome'] ?? '—') ?></a>
        <?php else: ?>—<?php endif; ?>
      </div>
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
      <a class="chip detail-trigger" href="arq_05.php?id=<?= $p['id'] ?>" data-type="projeto" data-id="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></a>
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
        <form method="post" action="arq_06.php" class="ajax-action-form inline-form" data-confirm="Apagar o item reportado? Essa ação não pode ser desfeita.">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <input type="hidden" name="acao" value="apagar">
          <input type="hidden" name="voltar" value="arq_02.php?id=<?= $usuario['id'] ?>">
          <button type="submit" class="btn-mini btn-mini-danger">Apagar</button>
        </form>
        <form method="post" action="arq_06.php" class="ajax-action-form inline-form" data-confirm="Ignorar esta reportagem?">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <input type="hidden" name="acao" value="ignorar">
          <input type="hidden" name="voltar" value="arq_02.php?id=<?= $usuario['id'] ?>">
          <button type="submit" class="btn-mini btn-mini-dark">Ignorar</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($usuario['tipo_nome'] === 'INSTITUICAO'): ?>
  <?php $turmasInst = turmas_da_instituicao((int)$usuario['id']); ?>
  <div class="block">
    <h2>Códigos de acesso</h2>
    <div class="chip-row">
      <?php $codigosInst = codigos_da_instituicao((int)$usuario['id']); ?>
      <?php if (empty($codigosInst)): ?>
        <p class="dim" style="font-size:var(--fs-sm);">Nenhum código gerado.</p>
      <?php endif; ?>
      <?php foreach ($codigosInst as $c): ?>
        <?php $expirado = strtotime($c['data_criacao'] . ' +7 days') < strtotime('today'); ?>
        <span class="chip"><?= htmlspecialchars($c['codigo']) ?> <span class="dim">· <?= htmlspecialchars($c['turma_nome'] ?? 'Instituição') ?> · <?= $expirado ? 'expirado em ' : 'válido até ' ?><?= date('d/m/Y', strtotime($c['data_criacao'] . ' +7 days')) ?></span>
          <?php if (file_exists(__DIR__ . '/../../codigo_instituicao.php') && !$expirado): ?>
            <a class="dim" href="../codigo_instituicao.php?codigo=<?= urlencode($c['codigo']) ?>" target="_blank" rel="noopener">· abrir cadastro</a>
          <?php endif; ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="actions-row">
  <a class="btn btn-outline" href="arq_02.php?id=<?= $usuario['id'] ?>&amp;exportar=1" download>Exportar dados</a>

  <?php if ($usuario['tipo_nome'] === 'INSTITUICAO'): ?>
  <form method="post" action="arq_03.php?id=<?= $usuario['id'] ?>" class="ajax-action-form">
    <input type="hidden" name="acao" value="gerar_codigo">
    <input type="hidden" name="turma_id" value="0">
    <button type="submit" class="btn btn-dark">Gerar</button>
  </form>
  <?php endif; ?>

  <form method="post" action="arq_02.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Gerar uma nova senha temporária para este usuário e enviar por e-mail? A senha atual deixa de funcionar.">
    <input type="hidden" name="acao" value="senha">
    <button type="submit" class="btn btn-outline">Solicitar alteração de senha</button>
  </form>

  <?php if ($usuario['ativada'] && !conta_protegida((int)$usuario['id'])): ?>
    <form method="post" action="arq_02.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Desativar esta conta?">
      <input type="hidden" name="acao" value="desativar">
      <button type="submit" class="btn btn-danger">Desativar conta</button>
    </form>
  <?php elseif (!$usuario['ativada']): ?>
    <form method="post" action="arq_02.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Reativar esta conta?">
      <input type="hidden" name="acao" value="reativar">
      <button type="submit" class="btn btn-dark">Reativar conta</button>
    </form>
  <?php endif; ?>

  <?php if (!conta_protegida((int)$usuario['id'])): ?>
  <form method="post" action="arq_02.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Tem certeza que deseja excluir esta conta? Essa ação não pode ser desfeita.">
    <input type="hidden" name="acao" value="excluir">
    <button type="submit" class="btn btn-danger">Excluir conta</button>
  </form>
  <?php endif; ?>
</div>
