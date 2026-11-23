<?php
/**
 * reportagem.php
 * Recebe os botões "Apagar" e "Ignorar" de cada card de Avaliações
 * (tabela reportagens). Responde em JSON quando chamado via AJAX pela
 * tela de pesquisa; senão, faz o fallback tradicional com redirect.
 */
require __DIR__ . '/includes/auth_admin.php';
require __DIR__ . '/includes/data.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$acao = $_POST['acao'] ?? '';
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

// "voltar" só pode apontar pra uma página deste painel (evita redirect pra site externo)
$voltar = (string)($_POST['voltar'] ?? '');
if (!preg_match('/^(usuario|instituicao|turma|projeto|pesquisa|index)\.php(\?[A-Za-z0-9=&_-]*)?$/', $voltar)) {
    $voltar = 'pesquisa.php';
}

$rep = reportagem_find($id);
$vazio = ['usuario_removido' => false, 'removido_tipo' => null, 'removido_id' => null];

if (!$rep) {
    $result = ['success' => false, 'message' => 'Reportagem não encontrada.'] + $vazio;
} elseif ($acao === 'ignorar') {
    $ok = reportagem_ignorar($id);
    $result = [
        'success' => $ok,
        'message' => $ok ? 'Reportagem ignorada.' : 'Não foi possível ignorar a reportagem.',
    ] + $vazio;
} elseif ($acao === 'apagar') {
    $result = reportagem_apagar($id);
} else {
    $result = ['success' => false, 'message' => 'Ação desconhecida.'] + $vazio;
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

flash_set($result['message']);
// se o item sumiu (usuário/projeto excluído), a página de detalhe dele não existe mais
header('Location: ' . (in_array($result['removido_tipo'], ['usuario', 'projeto'], true) ? 'pesquisa.php' : $voltar));
exit;
