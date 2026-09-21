<?php
require __DIR__ . '/includes/data.php';
$pageTitle = 'Painel de controle - Pesquisa';

$q = trim($_GET['q'] ?? '');
$openTab = $_GET['tab'] ?? 'usuarios'; // instituicoes | turmas | usuarios
$selectedType = $_GET['tipo'] ?? null;   // 'usuario' | 'instituicao' — pré-seleciona no load (opcional, ?tipo=usuario&id=3)
$selectedId = (int)($_GET['id'] ?? 0);

$instituicoes = db_instituicoes($q);
$turmas = db_turmas($q);
$usuarios = db_usuarios($q);

require __DIR__ . '/includes/header.php';
?>

<div class="search-layout">

  <div class="search-col">
    <form class="search-bar" method="get">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Pesquisar usuários, turmas ou instituições...">
      <button type="submit">+</button>
    </form>

    <div class="accordion-section<?= $openTab === 'instituicoes' ? ' open' : '' ?>" data-section="instituicoes">
      <button type="button" class="accordion-header">Instituições <span class="chev">⌄</span></button>
      <div class="accordion-body">
        <div class="pill-row">
          <?php if (empty($instituicoes)): ?>
            <p class="dim">Nenhuma instituição encontrada.</p>
          <?php endif; ?>
          <?php foreach ($instituicoes as $inst): ?>
            <div class="pill" data-row-id="<?= $inst['id'] ?>">
              <a href="instituicao.php?id=<?= $inst['id'] ?>" class="detail-trigger" data-type="instituicao" data-id="<?= $inst['id'] ?>">
                <?= htmlspecialchars($inst['nome']) ?>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="accordion-section<?= $openTab === 'turmas' ? ' open' : '' ?>" data-section="turmas">
      <button type="button" class="accordion-header">Turmas <span class="chev">⌄</span></button>
      <div class="accordion-body">
        <div class="pill-row">
          <?php if (empty($turmas)): ?>
            <p class="dim">Nenhuma turma encontrada.</p>
          <?php endif; ?>
          <?php foreach ($turmas as $t): ?>
            <div class="pill"><?= htmlspecialchars($t['nome']) ?> <span class="dim">· <?= htmlspecialchars($t['instituicao_nome']) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="accordion-section<?= $openTab === 'usuarios' ? ' open' : '' ?>" data-section="usuarios">
      <button type="button" class="accordion-header">Usuários <span class="chev">⌄</span></button>
      <div class="accordion-body">
        <?php if (empty($usuarios)): ?>
          <p class="dim">Nenhum usuário encontrado.</p>
        <?php endif; ?>
        <?php foreach ($usuarios as $u): ?>
          <div class="user-row" data-row-id="<?= $u['id'] ?>">
            <span class="radio"></span>
            <a class="user-name detail-trigger" href="usuario.php?id=<?= $u['id'] ?>" data-type="usuario" data-id="<?= $u['id'] ?>">
              <?= htmlspecialchars($u['nome']) ?>
              <?php if (!$u['ativada']): ?><span class="dim">(desativada)</span><?php endif; ?>
            </a>
            <span class="dim"><?= htmlspecialchars($u['turma_nome'] ?? '—') ?></span>
            <span class="dim"><?= htmlspecialchars(tipo_label($u['tipo_nome'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="detail-col">
    <div id="actionBanner" class="action-banner" hidden>
      <span id="actionBannerText"></span>
      <button type="button" id="actionBannerClose" aria-label="Fechar">×</button>
    </div>
    <div id="detailPanel" class="detail-panel">
      <p class="detail-placeholder">Selecione um usuário ou instituição na lista ao lado para ver os detalhes aqui.</p>
    </div>
  </div>

</div>

<script>
(function () {
  var panel = document.getElementById('detailPanel');
  var banner = document.getElementById('actionBanner');
  var bannerText = document.getElementById('actionBannerText');
  var current = { type: null, id: null }; // o que está aberto no painel agora

  document.querySelectorAll('.accordion-header').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.accordion-section').classList.toggle('open');
    });
  });

  function showBanner(message, isError) {
    bannerText.textContent = message;
    banner.classList.toggle('error', !!isError);
    banner.hidden = false;
  }
  document.getElementById('actionBannerClose').addEventListener('click', function () {
    banner.hidden = true;
  });

  function selectRow(id) {
    document.querySelectorAll('.user-row').forEach(function (row) {
      row.classList.toggle('selected', row.dataset.rowId === String(id));
    });
  }

  function loadDetail(type, id) {
    panel.innerHTML = '<p class="detail-placeholder">Carregando...</p>';
    current = { type: type, id: String(id) };
    if (type === 'usuario') selectRow(id);

    var url = (type === 'usuario' ? 'usuario.php' : 'instituicao.php') + '?id=' + encodeURIComponent(id) + '&partial=1';

    fetch(url)
      .then(function (res) { return res.text(); })
      .then(function (html) {
        panel.innerHTML = html;
      })
      .catch(function () {
        panel.innerHTML = '<p class="detail-placeholder">Não foi possível carregar os detalhes. Tente novamente.</p>';
      });
  }

  /** Remove da lista de busca a linha (usuário) ou pill (instituição) do item excluído. */
  function removeFromList(type, id) {
    var selector = type === 'usuario'
      ? '.user-row[data-row-id="' + id + '"]'
      : '.pill[data-row-id="' + id + '"]';
    var el = document.querySelector(selector);
    if (el) el.remove();
  }

  // Abre o painel de detalhes ao clicar num usuário/instituição da lista.
  // Delegado no document: cobre também links injetados depois via AJAX
  // (ex.: professores listados dentro dos detalhes de uma instituição).
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.detail-trigger');
    if (trigger) {
      e.preventDefault();
      loadDetail(trigger.dataset.type, trigger.dataset.id);
    }
  });

  // Ações dos botões (desativar/reativar/excluir/gerar código/senha) via AJAX:
  // sem recarregar a página, e a conta excluída some da lista na hora.
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
          // Conta/instituição excluída (botão "Excluir conta"/"Exclusão").
          removeFromList(type, data.id);
          panel.innerHTML = '<p class="detail-placeholder">Selecione um usuário ou instituição na lista ao lado para ver os detalhes aqui.</p>';
          current = { type: null, id: null };
          return;
        }

        if (data.success && data.usuario_removido) {
          // Botão "Apagar" numa reportagem que resultou na exclusão do
          // próprio usuário reportado (o perfil que está aberto agora).
          if (current.type && current.id) removeFromList(current.type, current.id);
          panel.innerHTML = '<p class="detail-placeholder">Selecione um usuário ou instituição na lista ao lado para ver os detalhes aqui.</p>';
          current = { type: null, id: null };
          return;
        }

        // Qualquer outra ação (desativar/reativar/gerar código/senha/
        // apagar ou ignorar uma reportagem): recarrega o painel pra
        // refletir o novo estado vindo do banco.
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

<?php require __DIR__ . '/includes/footer.php'; ?>
