<?php
/**
 * arq_09.php — Privacidade e proteção de dados (Lei nº 13.709/2018, LGPD).
 * Resume como o painel trata dados pessoais e mostra o registro das
 * últimas ações do administrador (art. 37).
 */
require __DIR__ . '/includes/mod_01.php';
require __DIR__ . '/includes/mod_02.php';

// Contato do encarregado pelo tratamento de dados (art. 41). Defina em
// includes/mod_03.php:  define('LGPD_CONTATO', 'privacidade@seudominio.com');
$contatoEncarregado = defined('LGPD_CONTATO') ? LGPD_CONTATO : '';

$registros = auditoria_ultimas(30);

$pageTitle = 'Privacidade e proteção de dados (LGPD)';
require __DIR__ . '/includes/mod_04.php';
?>

<p class="lgpd-aviso">
  Este painel trata dados pessoais dos usuários da plataforma Ugrad. Esta página resume como isso é feito,
  conforme a Lei nº 13.709/2018 (Lei Geral de Proteção de Dados Pessoais — LGPD).
  É um texto de apoio do projeto e deve ser revisado pelo responsável pela plataforma.
</p>

<div class="block">
  <h2>Quais dados pessoais são tratados</h2>
  <ul class="lgpd-lista">
    <li>Nome, e-mail, tipo de conta e descrição do perfil.</li>
    <li>Foto de perfil, turma e instituição do usuário.</li>
    <li>Participação em projetos, comentários e denúncias.</li>
  </ul>
</div>

<div class="block">
  <h2>Para que são usados (finalidade e necessidade)</h2>
  <ul class="lgpd-lista">
    <li>Administrar contas, turmas, instituições e projetos.</li>
    <li>Analisar denúncias e proteger a comunidade.</li>
    <li>Só administradores acessam o painel. Senhas nunca são exibidas e não saem nos arquivos de exportação.</li>
  </ul>
</div>

<div class="block">
  <h2>Direitos do titular (art. 18) e como o painel atende</h2>
  <table class="lgpd-tabela">
    <thead><tr><th>Direito</th><th>Como atender no painel</th></tr></thead>
    <tbody>
      <tr><td>Confirmação e acesso aos dados</td><td>Ficha da conta → botão <strong>Exportar dados</strong>.</td></tr>
      <tr><td>Portabilidade</td><td>O mesmo botão baixa um arquivo legível (JSON) com os dados da conta.</td></tr>
      <tr><td>Eliminação dos dados</td><td>Ficha da conta → <strong>Excluir conta</strong> (remove também o que está ligado a ela).</td></tr>
      <tr><td>Bloqueio / suspensão do uso</td><td>Ficha da conta → <strong>Desativar conta</strong>.</td></tr>
      <tr><td>Correção de dados</td><td>Feita pelo próprio usuário na página da conta, ou a pedido ao administrador.</td></tr>
    </tbody>
  </table>
</div>

<div class="block">
  <h2>Segurança</h2>
  <ul class="lgpd-lista">
    <li>Acesso restrito a contas de administrador ativas.</li>
    <li>Senhas guardadas de forma protegida; ao redefinir, é gerada uma senha temporária.</li>
    <li>Todas as ações do administrador ficam registradas (abaixo).</li>
  </ul>
</div>

<div class="block">
  <h2>Registro das operações (art. 37)</h2>
  <p class="lgpd-aviso">Últimas 30 ações. O registro guarda só números (quem agiu e em qual item), sem nomes, e-mails nem endereço de IP.</p>
  <?php if (empty($registros)): ?>
    <p class="dim" style="font-size:var(--fs-sm);">Nenhuma ação registrada ainda.</p>
  <?php else: ?>
    <table class="lgpd-tabela">
      <thead><tr><th>Quando</th><th>Administrador</th><th>Ação</th><th>Item</th><th>Resultado</th></tr></thead>
      <tbody>
      <?php foreach ($registros as $r): ?>
        <tr>
          <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['quando'] ?? 'now'))) ?></td>
          <td>#<?= (int)($r['admin'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string)($r['acao'] ?? '')) ?></td>
          <td><?= isset($r['alvo']) ? '#' . (int)$r['alvo'] : '—' ?></td>
          <td><?= htmlspecialchars((string)($r['resultado'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="block">
  <h2>Encarregado pelo tratamento de dados (art. 41)</h2>
  <p style="font-size:var(--fs-sm);">
    <?= $contatoEncarregado !== '' ? htmlspecialchars($contatoEncarregado) : 'Contato a ser definido pela equipe da plataforma.' ?>
  </p>
</div>

<?php require __DIR__ . '/includes/mod_05.php'; ?>
