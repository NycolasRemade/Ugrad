<?php
session_start();
require_once '../Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['usuario_tipo'] !== 5) {
    header('Location: ../dashboard.php');
    exit;
}

require 'includes/data.php';

$q = trim($_GET['q'] ?? '');
$openTab = $_GET['tab'] ?? 'usuarios'; // instituicoes | turmas | usuarios
$selectedType = $_GET['tipo'] ?? null;   // 'usuario' | 'instituicao'
$selectedId = (int)($_GET['id'] ?? 0);

$instituicoes = db_instituicoes($q);
$turmas = db_turmas($q);
$usuarios = db_usuarios($q);

$title = 'Painel de controle - Pesquisa';
$href = '../index.php';
$baseUrl = '../';
require '../header.php';
?>

<!-- Contentor principal utilizando .dashboard-container e .multiple_inline do styles.css -->
<div class="dashboard-container multiple_inline" style="gap: 30px; align-items: flex-start;">

  <!-- Coluna da esquerda: Pesquisa e Listagens -->
  <div style="flex: 1; min-width: 0;">
    
    <!-- Formulário de pesquisa utilizando o estilo de cabeçalho .secao-header e botão .btn-novo -->
    <form method="get" class="secao-header" style="gap: 10px; border-bottom: none; margin-bottom: 25px;">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Pesquisar usuários, turmas ou instituições..." style="flex: 1; border: none; height: 48px; font-size: 16px; background-color: #ffffff; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25); padding-left: 15px;">
      <button type="submit" class="btn-novo" style="height: 48px; padding: 0 20px;">+</button>
    </form>

    <!-- Secção Instituições em bloco .box -->
    <details class="box" <?= $openTab === 'instituicoes' ? 'open' : '' ?> style="margin-bottom: 20px; padding: 20px;">
      <summary style="font-family: 'BMI'; font-size: 22px; cursor: pointer; user-select: none; margin-bottom: 10px;">Instituições</summary>
      <div style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px;">
        <?php if (empty($instituicoes)): ?>
          <p style="color: #666; font-size: 14px;">Nenhuma instituição encontrada.</p>
        <?php endif; ?>
        <?php foreach ($instituicoes as $inst): ?>
          <div class="small pill" data-row-id="<?= $inst['id'] ?>">
            <p>
              <a href="instituicao.php?id=<?= $inst['id'] ?>" class="detail-trigger" data-type="instituicao" data-id="<?= $inst['id'] ?>">
                <?= htmlspecialchars($inst['nome']) ?>
              </a>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </details>

    <!-- Secção Turmas em bloco .box -->
    <details class="box" <?= $openTab === 'turmas' ? 'open' : '' ?> style="margin-bottom: 20px; padding: 20px;">
      <summary style="font-family: 'BMI'; font-size: 22px; cursor: pointer; user-select: none; margin-bottom: 10px;">Turmas</summary>
      <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
        <?php if (empty($turmas)): ?>
          <p style="color: #666; font-size: 14px;">Nenhuma turma encontrada.</p>
        <?php endif; ?>
        <?php foreach ($turmas as $t): ?>
          <div class="small" style="width: auto; text-align: left; padding: 12px 15px;">
            <p>
              <span><?= htmlspecialchars($t['nome']) ?></span>
              <span style="color: #666; font-size: 13px;"> · <?= htmlspecialchars($t['instituicao_nome']) ?></span>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </details>

    <!-- Secção Usuários em bloco .box -->
    <details class="box" <?= $openTab === 'usuarios' ? 'open' : '' ?> style="margin-bottom: 20px; padding: 20px;">
      <summary style="font-family: 'BMI'; font-size: 22px; cursor: pointer; user-select: none; margin-bottom: 10px;">Usuários</summary>
      <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 8px;">
        <?php if (empty($usuarios)): ?>
          <p style="color: #666; font-size: 14px;">Nenhum usuário encontrado.</p>
        <?php endif; ?>
        <?php foreach ($usuarios as $u): ?>
          <div class="user-row small" data-row-id="<?= $u['id'] ?>" style="width: auto; text-align: left; padding: 12px 15px; cursor: pointer;">
            <p style="display: flex; align-items: center; justify-content: space-between; margin: 0; width: 100%;">
              <a class="user-name detail-trigger" href="usuario.php?id=<?= $u['id'] ?>" data-type="usuario" data-id="<?= $u['id'] ?>" style="flex: 1;">
                <?= htmlspecialchars($u['nome']) ?>
                <?php if (!$u['ativada']): ?><span style="color: #888; font-size: 13px;"> (desativada)</span><?php endif; ?>
              </a>
              <span style="color: #666; font-size: 13px; margin-right: 15px;"><?= htmlspecialchars($u['turma_nome'] ?? '—') ?></span>
              <span style="color: #666; font-size: 13px;"><?= htmlspecialchars(tipo_label($u['tipo_nome'])) ?></span>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </details>

  </div>

  <!-- Coluna da direita: Painel de detalhes e mensagens de ação -->
  <div style="flex: 1.2; min-width: 0;">
    
    <!-- Banner de notificação de ações -->
    <div id="actionBanner" hidden style="padding: 12px 15px; margin-bottom: 20px; font-family: 'BMI'; font-size: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: 5px 5px 4px rgba(0, 0, 0, 0.15);">
      <span id="actionBannerText"></span>
      <button type="button" id="actionBannerClose" class="btn-x" style="padding: 0 5px; line-height: 1;" aria-label="Fechar">×</button>
    </div>

    <!-- Painel de Detalhes -->
    <div id="detailPanel" class="box" style="padding: 30px; min-height: 400px;">
      <p class="detail-placeholder" style="font-family: 'BMI'; color: #666; text-align: center; margin-top: 100px; font-size: 18px;">
        Selecione um usuário ou instituição na lista ao lado para ver os detalhes aqui.
      </p>
    </div>

  </div>

</div>

<script>
(function () {
  var panel = document.getElementById('detailPanel');
  var banner = document.getElementById('actionBanner');
  var bannerText = document.getElementById('actionBannerText');
  var current = { type: null, id: null };

  function showBanner(message, isError) {
    bannerText.textContent = message;
    if (isError) {
      banner.style.backgroundColor = '#ffcbcb';
      banner.style.color = '#C50000';
      banner.style.borderLeft = '5px solid #C50000';
    } else {
      banner.style.backgroundColor = '#d4edda';
      banner.style.color = '#155724';
      banner.style.borderLeft = '5px solid #28a745';
    }
    banner.hidden = false;
  }

  document.getElementById('actionBannerClose').addEventListener('click', function () {
    banner.hidden = true;
  });

  function selectRow(id) {
    document.querySelectorAll('.user-row').forEach(function (row) {
      if (row.dataset.rowId === String(id)) {
        row.style.outline = '2px solid #000';
        row.style.backgroundColor = '#EEEEEE';
      } else {
        row.style.outline = 'none';
        row.style.backgroundColor = '#ffffff';
      }
    });
  }

  function loadDetail(type, id) {
    panel.innerHTML = '<p class="detail-placeholder" style="font-family: \'BMI\'; color: #666; text-align: center; margin-top: 100px; font-size: 18px;">Carregando...</p>';
    current = { type: type, id: String(id) };
    if (type === 'usuario') selectRow(id);

    var url = (type === 'usuario' ? 'usuario.php' : 'instituicao.php') + '?id=' + encodeURIComponent(id) + '&partial=1';

    fetch(url)
      .then(function (res) { return res.text(); })
      .then(function (html) {
        panel.innerHTML = html;
      })
      .catch(function () {
        panel.innerHTML = '<p class="detail-placeholder" style="font-family: \'BMI\'; color: #C50000; text-align: center; margin-top: 100px; font-size: 18px;">Não foi possível carregar os detalhes. Tente novamente.</p>';
      });
  }

  function removeFromList(type, id) {
    var selector = type === 'usuario'
      ? '.user-row[data-row-id="' + id + '"]'
      : '.pill[data-row-id="' + id + '"]';
    var el = document.querySelector(selector);
    if (el) el.remove();
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.detail-trigger');
    if (trigger) {
      e.preventDefault();
      loadDetail(trigger.dataset.type, trigger.dataset.id);
    }
  });

  document.addEventListener('submit', function (e) {
    var form = e.target.closest('.ajax-action-form');
    if (!form) return;
    e.preventDefault();

    if (form.dataset.confirm && !confirm(form.dataset.confirm)) return;

    var type = form.getAttribute('action').indexOf('instituicao.php') !== -1 ? 'instituicao' : 'usuario';
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    fetch(form.getAttribute('action'), {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(form)
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        showBanner(data.message, !data.success);

        if (data.success && data.removed) {
          removeFromList(type, data.id);
          panel.innerHTML = '<p class="detail-placeholder" style="font-family: \'BMI\'; color: #666; text-align: center; margin-top: 100px; font-size: 18px;">Selecione um usuário ou instituição na lista ao lado para ver os detalhes aqui.</p>';
          current = { type: null, id: null };
          return;
        }

        if (data.success && data.usuario_removido) {
          if (current.type && current.id) removeFromList(current.type, current.id);
          panel.innerHTML = '<p class="detail-placeholder" style="font-family: \'BMI\'; color: #666; text-align: center; margin-top: 100px; font-size: 18px;">Selecione um usuário ou instituição na lista ao lado para ver os detalhes aqui.</p>';
          current = { type: null, id: null };
          return;
        }

        if (current.type && current.id) loadDetail(current.type, current.id);
      })
      .catch(function () {
        showBanner('Não foi possível concluir a ação. Tente novamente.', true);
        if (submitBtn) submitBtn.disabled = false;
      });
  });

  <?php if ($selectedId && $selectedType): ?>
  loadDetail(<?= json_encode($selectedType) ?>, <?= json_encode($selectedId) ?>);
  <?php endif; ?>
})();
</script>
</body>
</html>