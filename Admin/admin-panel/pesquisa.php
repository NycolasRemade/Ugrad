<?php
require __DIR__ . '/includes/auth_admin.php';
require __DIR__ . '/includes/data.php';
$pageTitle = 'Painel de controle - Pesquisa';

$q = trim($_GET['q'] ?? '');

$abas = ['instituicoes', 'turmas', 'projetos', 'usuarios'];
$openTab = in_array($_GET['tab'] ?? '', $abas, true) ? $_GET['tab'] : 'usuarios';

// pré-seleciona um item ao abrir (ex.: pesquisa.php?tipo=usuario&id=3)
$selectedType = in_array($_GET['tipo'] ?? '', ['usuario', 'instituicao', 'turma', 'projeto'], true) ? $_GET['tipo'] : null;
$selectedId = (int)($_GET['id'] ?? 0);

$instituicoes = db_instituicoes($q);
$turmas = db_turmas($q);
$projetos = db_projetos($q);
$usuarios = db_usuarios($q);

require __DIR__ . '/includes/header.php';
?>

<div class="search-layout">

  <div class="search-col">
    <form class="search-bar" id="searchForm" method="get">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"<?= isset($_GET['foco']) ? ' autofocus' : '' ?> placeholder="Pesquisar usuários, turmas, instituições ou projetos...">
      <input type="hidden" name="tab" id="tabField" value="<?= htmlspecialchars($openTab) ?>">
      <button type="submit">+</button>
    </form>

    <div class="accordion-section<?= $openTab === 'instituicoes' ? ' open' : '' ?>" data-section="instituicoes">
      <button type="button" class="accordion-header"><span>Instituições <span class="dim" data-count="instituicoes">(<?= count($instituicoes) ?>)</span></span> <span class="chev">⌄</span></button>
      <div class="accordion-body">
        <div class="pill-row">
          <?php if (empty($instituicoes)): ?>
            <p class="dim">Nenhuma instituição encontrada.</p>
          <?php endif; ?>
          <?php foreach ($instituicoes as $inst): ?>
            <div class="pill" data-row-type="instituicao" data-row-id="<?= $inst['id'] ?>">
              <a href="instituicao.php?id=<?= $inst['id'] ?>" class="detail-trigger" data-type="instituicao" data-id="<?= $inst['id'] ?>">
                <?= htmlspecialchars($inst['nome']) ?>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="accordion-section<?= $openTab === 'turmas' ? ' open' : '' ?>" data-section="turmas">
      <button type="button" class="accordion-header"><span>Turmas <span class="dim" data-count="turmas">(<?= count($turmas) ?>)</span></span> <span class="chev">⌄</span></button>
      <div class="accordion-body">
        <div class="pill-row">
          <?php if (empty($turmas)): ?>
            <p class="dim">Nenhuma turma encontrada.</p>
          <?php endif; ?>
          <?php foreach ($turmas as $t): ?>
            <div class="pill" data-row-type="turma" data-row-id="<?= $t['id'] ?>">
              <a href="turma.php?id=<?= $t['id'] ?>" class="detail-trigger" data-type="turma" data-id="<?= $t['id'] ?>">
                <?= htmlspecialchars($t['nome']) ?> <span class="dim">· <?= htmlspecialchars($t['instituicao_nome']) ?></span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="accordion-section<?= $openTab === 'projetos' ? ' open' : '' ?>" data-section="projetos">
      <button type="button" class="accordion-header"><span>Projetos <span class="dim" data-count="projetos">(<?= count($projetos) ?>)</span></span> <span class="chev">⌄</span></button>
      <div class="accordion-body">
        <div class="pill-row">
          <?php if (empty($projetos)): ?>
            <p class="dim">Nenhum projeto encontrado.</p>
          <?php endif; ?>
          <?php foreach ($projetos as $p): ?>
            <div class="pill" data-row-type="projeto" data-row-id="<?= $p['id'] ?>">
              <a href="projeto.php?id=<?= $p['id'] ?>" class="detail-trigger" data-type="projeto" data-id="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nome']) ?> <span class="dim">· <?= htmlspecialchars(estado_label($p['estado_nome'])) ?></span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="accordion-section<?= $openTab === 'usuarios' ? ' open' : '' ?>" data-section="usuarios">
      <button type="button" class="accordion-header"><span>Usuários <span class="dim" data-count="usuarios">(<?= count($usuarios) ?>)</span></span> <span class="chev">⌄</span></button>
      <div class="accordion-body">


        <?php if (empty($usuarios)): ?>
          <p class="dim">Nenhum usuário encontrado.</p>
        <?php endif; ?>
        <?php foreach ($usuarios as $u): ?>
          <div class="user-row" data-row-type="usuario" data-row-id="<?= $u['id'] ?>">
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
      <p class="detail-placeholder">Selecione um item na lista ao lado para ver os detalhes aqui.</p>
    </div>
  </div>

</div>

<script>
(function () {
  var panel = document.getElementById('detailPanel');
  var banner = document.getElementById('actionBanner');
  var bannerText = document.getElementById('actionBannerText');
  var tabField = document.getElementById('tabField');
  var current = { type: null, id: null }; // o que está aberto no painel agora

  var PLACEHOLDER = '<p class="detail-placeholder">Selecione um item na lista ao lado para ver os detalhes aqui.</p>';
  var ENDPOINTS = { usuario: 'usuario.php', instituicao: 'instituicao.php', turma: 'turma.php', projeto: 'projeto.php' };
  var SECTION_OF = { usuario: 'usuarios', instituicao: 'instituicoes', turma: 'turmas', projeto: 'projetos' };

  document.querySelectorAll('.accordion-header').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var section = btn.closest('.accordion-section');
      section.classList.toggle('open');
      // a próxima busca continua na seção que o admin acabou de abrir
      if (section.classList.contains('open')) tabField.value = section.dataset.section;
    });
  });

  function showBanner(message, isError) {
    bannerText.textContent = message && message.trim() !== '' ? message : 'Ação concluída.';
    banner.classList.toggle('error', !!isError);
    banner.hidden = false;
  }

  // Delegado no document (igual ao clique nos itens da lista), pra não
  // depender de o botão já existir no exato momento em que o script roda.
  document.addEventListener('click', function (e) {
    if (e.target.closest('#actionBannerClose')) {
      banner.hidden = true;
    }
  });

  function selectRow(type, id) {
    document.querySelectorAll('[data-row-type]').forEach(function (el) {
      el.classList.toggle('selected', el.dataset.rowType === type && el.dataset.rowId === String(id));
    });
  }

  function clearPanel() {
    panel.innerHTML = PLACEHOLDER;
    current = { type: null, id: null };
    selectRow(null, null);
  }

  function loadDetail(type, id) {
    if (!ENDPOINTS[type]) return;
    panel.innerHTML = '<p class="detail-placeholder">Carregando...</p>';
    current = { type: type, id: String(id) };
    selectRow(type, id);

    var url = ENDPOINTS[type] + '?id=' + encodeURIComponent(id) + '&partial=1';

    fetch(url)
      .then(function (res) { return res.text(); })
      .then(function (html) {
        panel.innerHTML = html;
      })
      .catch(function () {
        panel.innerHTML = '<p class="detail-placeholder">Não foi possível carregar os detalhes. Tente novamente.</p>';
      });
  }

  /** Remove da lista de busca o item excluído e ajusta o contador da seção. */
  function removeFromList(type, id) {
    var el = document.querySelector('[data-row-type="' + type + '"][data-row-id="' + id + '"]');
    if (!el) return;
    el.remove();
    var counter = document.querySelector('[data-count="' + SECTION_OF[type] + '"]');
    if (counter) {
      var n = parseInt(counter.textContent.replace(/\D/g, ''), 10);
      if (!isNaN(n) && n > 0) counter.textContent = '(' + (n - 1) + ')';
    }
  }

  // Abre o painel de detalhes ao clicar num item da lista.
  // Delegado no document: cobre também links injetados depois via AJAX
  // (professores, turmas, projetos e membros listados dentro dos detalhes).
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.detail-trigger');
    if (trigger) {
      e.preventDefault();
      loadDetail(trigger.dataset.type, trigger.dataset.id);
    }
  });

  // Ações dos botões (desativar/excluir/gerar código/senha/denúncias) via AJAX:
  // sem recarregar a página, e o item excluído some da lista na hora.
  document.addEventListener('submit', function (e) {
    var form = e.target.closest('.ajax-action-form');
    if (!form) return;
    e.preventDefault();

    if (form.dataset.confirm && !confirm(form.dataset.confirm)) return;

    var m = form.getAttribute('action').match(/(usuario|instituicao|turma|projeto)\.php/);
    var type = m ? m[1] : current.type;
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

        // o próprio item aberto foi excluído (usuario.php / instituicao.php / turma.php / projeto.php)
        if (data.success && data.removed) {
          removeFromList(type, data.id);
          clearPanel();
          return;
        }

        // denúncia aplicada: apagou um usuário/projeto (pode ser o aberto ou outro)
        if (data.success && data.removido_tipo && data.removido_id) {
          removeFromList(data.removido_tipo, data.removido_id);
          if (current.type === data.removido_tipo && current.id === String(data.removido_id)) {
            clearPanel();
            return;
          }
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

<?php require __DIR__ . '/includes/footer.php'; ?>
