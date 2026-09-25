<?php
if (!isset($_SESSION)) 
    session_start();
if ($_SESSION['usuario_tipo'] != 5) {
    echo 'sem permissão';
    exit;
}
/**
 * includes/instituicao_detail.php
 * Espera: $instituicao, $professores, $turmas
 */
$foto = avatar_data_uri($instituicao['imagem_perfil'] ?? null);
?>
<div style="display: flex; flex-direction: column; gap: 20px;">

  <!-- Cabeçalho com Avatar e Informações -->
  <div style="display: flex; gap: 20px; align-items: flex-start;">
    <?php if ($foto): ?>
      <div class="config" style="width: 100px; height: 100px; background-image: url('<?= $foto ?>'); flex-shrink: 0;"></div>
    <?php else: ?>
      <div id="kirkle" style="width: 100px; height: 100px; flex-shrink: 0; align-items: center; justify-content: center; font-size: 28px;">
        <span style="font-family: 'BMI'; color: #666;"><?= strtoupper(substr($instituicao['nome'], 0, 1)) ?></span>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; flex: 1;">
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">ID da instituição</span>
        <span style="font-family: 'BMI'; font-size: 16px;">#<?= $instituicao['id'] ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Status</span>
        <span style="font-family: 'BMI'; font-size: 16px; color: <?= $instituicao['ativada'] ? '#28a745' : '#C50000' ?>;"><?= $instituicao['ativada'] ? 'Ativa' : 'Desativada' ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Nome</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars($instituicao['nome']) ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">E-mail</span>
        <span style="font-family: 'BMI'; font-size: 16px; word-break: break-all;"><?= htmlspecialchars($instituicao['email']) ?></span>
      </div>
      <div>
        <span style="font-family: 'BMI'; font-size: 13px; color: #666; display: block;">Data de criação</span>
        <span style="font-family: 'BMI'; font-size: 16px;"><?= htmlspecialchars(formatar_data($instituicao['data_criacao'])) ?></span>
      </div>
    </div>
  </div>

  <!-- Descrição -->
  <div>
    <h2 style="font-family: 'BMI'; font-size: 18px; margin-bottom: 8px;">Descrição</h2>
    <div style="background-color: #ffffff; padding: 15px; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25); font-family: IBM; font-size: 14px; min-height: 50px;">
      <?= htmlspecialchars($instituicao['descricao'] ?: 'Sem descrição.') ?>
    </div>
  </div>

  <!-- Professores -->
  <div>
    <h2 style="font-family: 'BMI'; font-size: 18px; margin-bottom: 8px;">Professores</h2>
    <?php if (empty($professores)): ?>
      <p style="font-size: 14px; color: #666;">Nenhum professor vinculado.</p>
    <?php endif; ?>
    <div style="display: flex; flex-direction: column; gap: 8px;">
      <?php foreach ($professores as $p): ?>
        <div class="small" style="width: auto; text-align: left; padding: 10px 15px;">
          <p>
            <a class="detail-trigger" href="usuario.php?id=<?= $p['id'] ?>" data-id="<?= $p['id'] ?>" data-type="usuario">
              <?= htmlspecialchars($p['nome']) ?>
            </a>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Turmas -->
  <div>
    <h2 style="font-family: 'BMI'; font-size: 18px; margin-bottom: 8px;">Turmas</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
      <?php if (empty($turmas)): ?>
        <p style="font-size: 14px; color: #666;">Nenhuma turma cadastrada.</p>
      <?php endif; ?>
      <?php foreach ($turmas as $t): ?>
        <div class="small" style="padding: 8px 14px;">
          <p><?= htmlspecialchars($t['nome']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Ações -->
  <div class="secao-header" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; border-top: 2px solid #EEEEEE; padding-top: 15px; border-bottom: none;">
    <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form">
      <input type="hidden" name="acao" value="gerar_codigo">
      <button type="submit" class="btn-novo" style="font-size: 14px; padding: 8px 16px;">Gerar código</button>
    </form>

    <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Enviar link de redefinição de senha?">
      <input type="hidden" name="acao" value="senha">
      <button type="submit" class="btn-novo btn-secundario" style="font-size: 14px; padding: 8px 16px; border: 1px solid #000;">Solicitar alteração de senha</button>
    </form>

    <?php if ($instituicao['ativada']): ?>
      <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Desativar esta instituição?">
        <input type="hidden" name="acao" value="desativar">
        <button type="submit" class="btn-novo excluir" style="font-size: 14px; padding: 8px 16px;">Desativar</button>
      </form>
    <?php else: ?>
      <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Reativar esta instituição?">
        <input type="hidden" name="acao" value="reativar">
        <button type="submit" class="btn-novo" style="font-size: 14px; padding: 8px 16px;">Reativar</button>
      </form>
    <?php endif; ?>

    <form method="post" action="instituicao.php?id=<?= $instituicao['id'] ?>" class="ajax-action-form" data-confirm="Tem certeza que deseja excluir esta instituição?">
      <input type="hidden" name="acao" value="excluir">
      <button type="submit" class="btn-novo excluir" style="font-size: 14px; padding: 8px 16px;">Exclusão</button>
    </form>
  </div>

</div>