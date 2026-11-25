<?php
/**
 * includes/acessibilidade.php  —  MODO DE ACESSIBILIDADE
 *
 * Recursos de acessibilidade do painel, de acordo com a Lei nº 13.146/2015
 * (Lei Brasileira de Inclusão da Pessoa com Deficiência / Estatuto da Pessoa
 * com Deficiência), art. 63: os sítios da internet devem ser acessíveis para
 * uso da pessoa com deficiência, seguindo as boas práticas e diretrizes
 * internacionais de acessibilidade (WCAG).
 *
 * Como usar (já está ligado no includes/header.php):
 *   acessibilidade_head();    // dentro do <head>  (estilos + aplica a escolha salva)
 *   acessibilidade_widget();  // logo depois do <body> (link "pular", botão e menu)
 *
 * O que o modo oferece:
 *   - aumentar / diminuir o tamanho do texto (4 níveis)
 *   - alto contraste (fundo preto, texto branco, links amarelos)
 *   - destacar links
 *   - fonte mais legível
 *   - mais espaço entre letras, palavras e linhas
 *   - ler em voz alta (a página ou só o trecho selecionado)
 *   - link "Pular para o conteúdo" e foco do teclado sempre visível
 * A escolha da pessoa fica salva no navegador e vale em todas as telas.
 */

/** Estilos do modo de acessibilidade + aplicação da escolha salva (antes de desenhar a tela). */
function acessibilidade_head(): void { ?>
<script>
/* aplica as preferências salvas antes da página aparecer (evita "piscar") */
(function () {
  try {
    var p = JSON.parse(localStorage.getItem('ugrad_acessibilidade') || '{}');
    var c = document.documentElement.classList;
    if (p.fonte) c.add('acc-fonte-' + p.fonte);
    ['contraste', 'links', 'legivel', 'espacamento'].forEach(function (k) {
      if (p[k]) c.add('acc-' + k);
    });
  } catch (e) {}
})();
</script>
<style>
/* ---------- foco do teclado sempre visível ---------- */
:focus-visible { outline: 3px solid #1a73e8 !important; outline-offset: 2px !important; }

/* ---------- link "Pular para o conteúdo" ---------- */
.acc-pular { position: absolute; left: 8px; top: -60px; z-index: 10000; background: #fff; color: #000;
  padding: 10px 16px; border: 2px solid #000; font: 600 14px/1.2 var(--font, monospace); text-decoration: none; }
.acc-pular:focus { top: 8px; }

/* ---------- botão e menu ---------- */
.acc-botao { position: fixed; right: 16px; bottom: 16px; z-index: 9998; display: flex; align-items: center; gap: 8px;
  background: #111; color: #fff; border: 2px solid #fff; padding: 10px 14px; cursor: pointer;
  font: 600 14px/1 var(--font, monospace); box-shadow: 0 2px 8px rgba(0,0,0,.35); }
.acc-botao svg { width: 22px; height: 22px; fill: currentColor; }
.acc-menu { position: fixed; right: 16px; bottom: 70px; z-index: 9999; width: min(340px, calc(100vw - 32px));
  max-height: calc(100vh - 100px); overflow: auto; background: #fff; color: #111; border: 2px solid #111;
  padding: 16px; box-shadow: 0 4px 16px rgba(0,0,0,.35); font: 14px/1.4 var(--font, monospace); }
.acc-menu[hidden] { display: none; }
.acc-menu h2 { margin: 0 0 4px; font-size: 16px; }
.acc-menu .acc-lei { margin: 0 0 12px; font-size: 12px; color: #444; }
.acc-grupo { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
.acc-menu button { font: inherit; padding: 10px; border: 2px solid #111; background: #fff; color: #111; cursor: pointer; text-align: left; }
.acc-menu button[aria-pressed="true"] { background: #111; color: #fff; }
.acc-menu .acc-largo { grid-column: 1 / -1; }
.acc-menu .acc-reset { margin-top: 4px; width: 100%; text-align: center; font-weight: 600; }

/* ---------- tamanho do texto ---------- */
html.acc-fonte-1 { --fs-2xs: 12px; --fs-xs: 13px; --fs-sm: 14px; --fs-base: 15px; --fs-md: 16px; --fs-lg: 18px; --fs-xl: 21px; }
html.acc-fonte-2 { --fs-2xs: 14px; --fs-xs: 15px; --fs-sm: 16px; --fs-base: 17px; --fs-md: 18px; --fs-lg: 20px; --fs-xl: 24px; }
html.acc-fonte-3 { --fs-2xs: 16px; --fs-xs: 17px; --fs-sm: 18px; --fs-base: 20px; --fs-md: 21px; --fs-lg: 23px; --fs-xl: 28px; }
html.acc-fonte-4 { --fs-2xs: 18px; --fs-xs: 19px; --fs-sm: 21px; --fs-base: 23px; --fs-md: 24px; --fs-lg: 26px; --fs-xl: 32px; }

/* ---------- fonte mais legível ---------- */
html.acc-legivel, html.acc-legivel * { --font: Verdana, Arial, Helvetica, sans-serif; font-family: Verdana, Arial, Helvetica, sans-serif !important; }

/* ---------- espaçamento do texto ---------- */
html.acc-espacamento body { letter-spacing: .08em; word-spacing: .16em; line-height: 1.7; }

/* ---------- destacar links ---------- */
html.acc-links a { text-decoration: underline !important; text-underline-offset: 3px; text-decoration-thickness: 2px; font-weight: 700; }

/* ---------- alto contraste ---------- */
html.acc-contraste, html.acc-contraste body, html.acc-contraste .app-shell, html.acc-contraste .content { background: #000 !important; color: #fff !important; transition: none !important; }
html.acc-contraste *:not(svg):not(path):not(.acc-botao):not(.acc-botao *):not(.acc-menu):not(.acc-menu *) {
  background-color: #000 !important; color: #fff !important; border-color: #fff !important; box-shadow: none !important; text-shadow: none !important; }
html.acc-contraste a:not(.acc-pular) { color: #ffeb3b !important; }
html.acc-contraste .btn, html.acc-contraste button:not(.acc-menu button) { border: 2px solid #fff !important; }
html.acc-contraste .btn-danger { background-color: #fff !important; color: #000 !important; }
html.acc-contraste .topbar { background-color: #000 !important; }
html.acc-contraste .dash-avatar, html.acc-contraste .detail-avatar, html.acc-contraste .avatar-dot, html.acc-contraste .radio { border: 2px solid #fff !important; }
html.acc-contraste .flash, html.acc-contraste .banner { border: 2px solid #ffeb3b !important; }

</style>
<?php }

/** Link "Pular", botão flutuante e menu. Chamar logo depois do <body>. */
function acessibilidade_widget(): void { ?>
<a class="acc-pular" href="#conteudo">Pular para o conteúdo</a>

<button type="button" class="acc-botao" id="accBotao" aria-expanded="false" aria-controls="accMenu">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm8 5.5v2l-5 .5v3l2.5 7.5-1.9.6L12.7 15h-1.4l-2 6.1-1.9-.6L9.9 13V10L5 9.5v-2l7 .7 8-.7z"/></svg>
  Acessibilidade
</button>

<div class="acc-menu" id="accMenu" role="dialog" aria-label="Opções de acessibilidade" hidden>
  <h2>Acessibilidade</h2>
  <p class="acc-lei">Recursos de acessibilidade</p>

  <div class="acc-grupo" role="group" aria-label="Tamanho do texto">
    <button type="button" data-acc="fonte+">A+ Aumentar texto</button>
    <button type="button" data-acc="fonte-">A− Diminuir texto</button>
  </div>
  <div class="acc-grupo" role="group" aria-label="Aparência">
    <button type="button" data-acc="contraste" aria-pressed="false">Alto contraste</button>
    <button type="button" data-acc="links" aria-pressed="false">Destacar links</button>
    <button type="button" data-acc="legivel" aria-pressed="false">Fonte legível</button>
    <button type="button" data-acc="espacamento" aria-pressed="false">Espaçar texto</button>
  </div>
  <div class="acc-grupo" role="group" aria-label="Leitura">
    <button type="button" data-acc="voz" class="acc-largo" id="accVoz" aria-pressed="false">Ler em voz alta</button>
  </div>
  <button type="button" class="acc-reset" data-acc="reset">Restaurar padrão</button>
  <p class="acc-lei" id="accStatus" role="status" aria-live="polite" style="margin:10px 0 0"></p>
</div>

<script>
(function () {
  var KEY = 'ugrad_acessibilidade';
  var html = document.documentElement;
  var botao = document.getElementById('accBotao');
  var menu = document.getElementById('accMenu');
  var status = document.getElementById('accStatus');
  var estado = {};
  try { estado = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}

  function salvar() { try { localStorage.setItem(KEY, JSON.stringify(estado)); } catch (e) {} }

  function aplicar() {
    [1, 2, 3, 4].forEach(function (n) { html.classList.remove('acc-fonte-' + n); });
    if (estado.fonte) html.classList.add('acc-fonte-' + estado.fonte);
    ['contraste', 'links', 'legivel', 'espacamento'].forEach(function (k) {
      html.classList.toggle('acc-' + k, !!estado[k]);
      var b = menu.querySelector('[data-acc="' + k + '"]');
      if (b) b.setAttribute('aria-pressed', estado[k] ? 'true' : 'false');
    });
  }

  function dizer(t) { status.textContent = t; }

  menu.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-acc]');
    if (!b || b.id === 'accVoz') return;
    var a = b.getAttribute('data-acc');
    if (a === 'fonte+') { estado.fonte = Math.min(4, (estado.fonte || 0) + 1); dizer('Tamanho do texto: nível ' + estado.fonte + ' de 4.'); }
    else if (a === 'fonte-') { estado.fonte = Math.max(0, (estado.fonte || 0) - 1); dizer(estado.fonte ? 'Tamanho do texto: nível ' + estado.fonte + ' de 4.' : 'Tamanho do texto: padrão.'); }
    else if (a === 'reset') { estado = {}; pararLeitura(); dizer('Configurações restauradas ao padrão.'); }
    else { estado[a] = !estado[a]; dizer(b.textContent.trim() + (estado[a] ? ': ligado.' : ': desligado.')); }
    salvar(); aplicar();
  });

  /* ---- Ler em voz alta (usa a voz do próprio navegador, em português) ---- */
  var voz = ('speechSynthesis' in window) ? window.speechSynthesis : null;
  var btnVoz = document.getElementById('accVoz');
  if (!voz) { btnVoz.parentNode.hidden = true; }

  function pararLeitura() {
    if (voz) voz.cancel();
    btnVoz.setAttribute('aria-pressed', 'false');
    btnVoz.textContent = 'Ler em voz alta';
  }

  function lerEmVozAlta() {
    var texto = (window.getSelection().toString() || '').trim();
    var origem = texto ? 'o texto selecionado' : 'a página';
    if (!texto) { var c = document.getElementById('conteudo'); texto = c ? c.innerText.trim() : ''; }
    if (!texto) { dizer('Não há texto para ler.'); return; }
    // quebra em pedaços curtos: o navegador interrompe leituras muito longas
    var partes = texto.replace(/\s*\n+\s*/g, '. ').match(/[^.!?]+[.!?]*/g) || [texto];
    voz.cancel();
    partes.forEach(function (parte, i) {
      parte = parte.trim();
      if (!parte || !/[A-Za-zÀ-ÿ0-9]/.test(parte)) return;  // ignora sobras como "+" ou "×"
      var u = new SpeechSynthesisUtterance(parte);
      u.lang = 'pt-BR';
      if (i === partes.length - 1) u.onend = pararLeitura;
      u.onerror = pararLeitura;
      voz.speak(u);
    });
    btnVoz.setAttribute('aria-pressed', 'true');
    btnVoz.textContent = 'Parar leitura';
    dizer('Lendo ' + origem + '.');
  }

  btnVoz.addEventListener('click', function (ev) {
    ev.stopPropagation();
    if (btnVoz.getAttribute('aria-pressed') === 'true') { pararLeitura(); dizer('Leitura parada.'); }
    else { lerEmVozAlta(); }
  });
  window.addEventListener('beforeunload', function () { if (voz) voz.cancel(); });

  function abrir(v) {
    menu.hidden = !v;
    botao.setAttribute('aria-expanded', v ? 'true' : 'false');
    if (v) menu.querySelector('button').focus();
  }
  botao.addEventListener('click', function () { abrir(menu.hidden); });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && !menu.hidden) { abrir(false); botao.focus(); }
  });

  aplicar();
})();
</script>
<?php }
