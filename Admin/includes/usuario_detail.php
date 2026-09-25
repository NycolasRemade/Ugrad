<?php
if (!isset($_SESSION)) 
    session_start();
if ($_SESSION['usuario_tipo'] != 5) {
    echo 'sem permissão';
    exit;
}
/**
 * includes/usuario_detail.php
 * Espera: $usuario (linha de USUARIO_SELECT), $projetos, $avaliacoes
 */
$foto = avatar_data_uri($usuario['imagem_perfil'] ?? null);
?>
<div style="display: flex; flex-direction: column; gap: 20px;">

  <!-- Cabeçalho com Avatar e Informações -->
  <div style="display: flex; gap: 20px; align-items: flex-start;">
    <?php if ($foto): ?>
      <div class="config" style="width: 100px; height: 100px; background-image: url('<?= $foto ?>'); flex-shrink: 0;"></div>
    <?php else: ?>
      <div id="kirkle" style="width: 100px; height: 100px; flex-shrink: 0; align-items: center; justify-content: center; font-size: 28px;">
        <span style="font-family: 'BMI'; color: #666;"><?= strtoupper(substr($usuario['nome'], 0, 1)) ?></span>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; flex: 1;">
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">ID do usuário</span>
        <span style="font-family: 'BMI'; font-size: 16px;">#<?= $usuario['id'] ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Tipo</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars(tipo_label($usuario['tipo_nome'])) ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Nome</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars($usuario['nome']) ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Turma</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars($usuario['turma_nome'] ?? '—') ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">E-mail</span>
        <span style="font-family: 'BMI'; font-size: 16px; word-break: break-all;"><?= htmlspecialchars($usuario['email']) ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Instituição</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars($usuario['instituicao_nome'] ?? '—') ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Data de criação</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars(formatar_data($usuario['data_criacao'])) ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Status</span>
        <span style="font-family: 'BMI'; font-size: 16px; color: <?= $usuario['ativada'] ? '#28a745' : '#C50000' ?>;"><?= $usuario['ativada'] ? 'Ativa' : 'Desativada' ?></span>
      </div>
    </div>
  </div>

  <!-- Descrição -->
  <div>
    <h2 style="font-family: 'BMI'; font-size: 18px; margin-bottom: 8px;">Descrição</h2>
    <div style="background-color: #ffffff; padding: 15px; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25); font-family: IBM; font-size: 14px; min-height: 50px;">
      <?= htmlspecialchars($usuario['descricao'] ?: 'Sem descrição.') ?>
    </div>
  </div>

  <!-- Projetos -->
  <div>
    <h2 style="font-family: 'BMI'; font-size: 18px; margin-bottom: 8px;">Projetos</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
      <?php if (empty($projetos)): ?>
        <p style="font-size: 14px; color: #666;">Nenhum projeto vinculado.</p>
      <?php endif; ?>
      <?php foreach ($projetos as $p): ?>
        <div class="small" style="padding: 8px 14px;">
          <p><?= htmlspecialchars($p['nome']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Avaliações -->
  <div>
    <h2 style="font-family: 'BMI'; font-size: 18px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
      Avaliações
      <?php if (count($avaliacoes) > 0): ?>
        <span style="background-color: #C50000; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-family: 'BMI';"><?= count($avaliacoes) ?></span>
      <?php endif; ?>
    </h2>

    <?php if (empty($avaliacoes)): ?>
      <p style="font-size: 14px; color: #666;">Nenhuma reportagem registrada.</p>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 12px;">
      <?php foreach ($avaliacoes as $a): ?>
        <div class="small" data-report-id="<?= $a['id'] ?>" style="width: auto; text-align: left; padding: 15px; display: flex; flex-direction: column; gap: 10px;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <p style="margin: 0;">
              Feita em: <?= $a['tipo_rep_nome'] === 'PROJETO' ? htmlspecialchars($a['projeto_nome'] ?? 'Projeto removido') : 'Perfil do usuário' ?>
            </p>
            <span style="background-color: #EEEEEE; padding: 4px 8px; font-size: 12px; font-family: 'BMI';">
              <?= htmlspecialchars(tipo_label($a['tipo_rep_nome'] ?? 'GENERICO')) ?>
            </span>
          </div>

          <div style="font-size: 13px; color: #666;">
            Reportado por: <strong style="font-family: 'BMI';"><?= htmlspecialchars($a['reportado_por']) ?></strong> · <?= htmlspecialchars(formatar_data($a['data_reportagem'])) ?>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 5px;">
            <form method="post" action="reportagem.php" class="ajax-action-form" data-confirm="Apagar o item reportado? Essa ação não pode ser desfeita.">
              <input type="hidden" name="id" value="<?= $a['id'] ?>">
              <input type="hidden" name="acao" value="apagar">
              <input type="hidden" name="voltar" value="usuario.php?id=<?= $usuario['id'] ?>">
              <button type="submit" class="btn-novo excluir" style="font-size: 12px; padding: 5px 12px;">Apagar</button>
            </form>

            <form method="post" action="reportagem.php" class="ajax-action-form" data-confirm="Ignorar esta reportagem?">
              <input type="hidden" name="id" value="<?= $a['id'] ?>">
              <input type="hidden" name="acao" value="ignorar">
              <input type="hidden" name="voltar" value="usuario.php?id=<?= $usuario['id'] ?>">
              <button type="submit" class="btn-novo btn-secundario" style="font-size: 12px; padding: 5px 12px; border: 1px solid #000;">Ignorar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Ações -->
  <div class="secao-header" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; border-top: 2px solid #EEEEEE; padding-top: 15px; border-bottom: none;">
    <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Enviar link de redefinição de senha para este usuário?">
      <input type="hidden" name="acao" value="senha">
      <button type="submit" class="btn-novo btn-secundario" style="font-size: 14px; padding: 8px 16px; border: 1px solid #000;">Solicitar alteração de senha</button>
    </form>

    <?php if ($usuario['ativada']): ?>
      <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Desativar esta conta?">
        <input type="hidden" name="acao" value="desativar">
        <button type="submit" class="btn-novo excluir" style="font-size: 14px; padding: 8px 16px;">Desativar conta</button>
      </form>
    <?php else: ?>
      <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Reativar esta conta?">
        <input type="hidden" name="acao" value="reativar">
        <button type="submit" class="btn-novo" style="font-size: 14px; padding: 8px 16px;">Reativar conta</button>
      </form>
    <?php endif; ?>

    <form method="post" action="usuario.php?id=<?= $usuario['id'] ?>" class="ajax-action-form" data-confirm="Tem certeza que deseja excluir esta conta? Essa ação não pode ser desfeita.">
      <input type="hidden" name="acao" value="excluir">
      <button type="submit" class="btn-novo excluir" style="font-size: 14px; padding: 8px 16px;">Excluir conta</button>
    </form>
  </div>

</div>