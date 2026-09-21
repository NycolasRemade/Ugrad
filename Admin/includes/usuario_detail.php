<?php
/**
 * includes/usuario_detail.php
 * Espera: $usuario (linha de USUARIO_SELECT), $projetos, $avaliacoes
 */
$foto = avatar_data_uri($usuario['imagem_perfil'] ?? null);
?>
<div class="detail-panel-inner">

  <div class="detail-grid">
    <?php if ($foto): ?>
      <img class="detail-avatar" src="<?= $foto ?>" alt="Foto de <?= htmlspecialchars($usuario['nome']) ?>">
    <?php else: ?>
      <div class="detail-avatar"></div>
    <?php endif; ?>
    <div class="detail-fields">
      <div><span class="field-label">ID do usuário</span></div>
      <div><span class="field-label">Tipo</span></div>
      <div class="field-value">#<?= $usuario['id'] ?></div>
      <div class="field-value"><?= htmlspecialchars(tipo_label($usuario['tipo_nome'])) ?></div>

      <div><span class="field-label">Nome</span></div>
      <div><span class="field-label">Turma</span></div>
      <div class="field-value"><?= htmlspecialchars($usuario['nome']) ?></div>
      <div class="field-value"><?= htmlspecialchars($usuario['turma_nome'] ?? '—') ?></div>

      <div><span class="field-label">E-mail</span></div>
      <div><span class="field-label">Instituição</span></div>
      <div class="field-value"><?= htmlspecialchars($usuario['email']) ?></div>
      <div class="field-value"><?= htmlspecialchars($usuario['instituicao_nome'] ?? '—') ?></div>

      <div><span class="field-label">Data de criação</span></div>
      <div><span class="field-label">Status</span></div>
      <div class="field-value"><?= htmlspecialchars(formatar_data($usuario['data_criacao'])) ?></div>
      <div class="field-value"><?= $usuario['ativada'] ? 'Ativa' : 'Desativada' ?></div>
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
        <p style="font-size:13px;color:var(--text-muted)">Nenhum projeto vinculado.</p>
      <?php endif; ?>
      <?php foreach ($projetos as $p): ?>
        <div class="chip"><?= htmlspecialchars($p['nome']) ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="block">
    <h2>Avaliações
      <?php if (count($avaliacoes) > 0): ?>
        <span class="badge-count"><?= count($avaliacoes) ?></span>
      <?php endif; ?>
    </h2>

    <?php if (empty($avaliacoes)): ?>
      <p style="font-size:13px;color:var(--text-muted)">Nenhuma reportagem registrada.</p>
    <?php endif; ?>

    <?php foreach ($avaliacoes as $a): ?>
      <div class="review-card" data-report-id="<?= $a['id'] ?>">
        <div class="review-meta">
          <span class="review-project">
            Feita em: <?= $a['tipo_rep_nome'] === 'PROJETO' ? htmlspecialchars($a['projeto_nome'] ?? 'Projeto removido') : 'Perfil do usuário' ?>
          </span>
          <span class="status-tag ativo"><?= htmlspecialchars(tipo_label($a['tipo_rep_nome'] ?? 'GENERICO')) ?></span>
        </div>
        <div class="review-footer">
          <span>Reportado por: <?= htmlspecialchars($a['reportado_por']) ?> · <?= htmlspecialchars(formatar_data($a['data_reportagem'])) ?></span>
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

    <?php if (!empty($avaliacoes)): ?>
      <p style="font-size:11px;color:var(--text-muted);margin-top:6px;">
        A tabela <code>reportagens</code> ainda não guarda motivo/texto nem status (ativo/resolvido) —
        adicione essas colunas se quiser exibi-los aqui.
      </p>
    <?php endif; ?>
  </div>

  <!-- action="usuario.php" explícito: garante que o POST funcione mesmo
       quando este trecho está injetado dentro de pesquisa.php via AJAX -->
  <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="actions-row ajax-action-form" data-confirm="Enviar link de redefinição de senha para este usuário?">
    <input type="hidden" name="acao" value="senha">
    <button type="submit" class="btn btn-dark">Solicitar alteração de senha</button>
  </form>

  <div class="actions-row">
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

</div>
