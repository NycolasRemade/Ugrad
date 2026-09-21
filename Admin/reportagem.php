<?php
/**
 * reportagem.php
 * Recebe os botões "Apagar" e "Ignorar" de cada card de Avaliações
 * (tabela reportagens). Responde em JSON quando chamado via AJAX pela
 * tela de pesquisa; senão, faz o fallback tradicional com redirect.
 */
require __DIR__ . '/includes/data.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$acao = $_POST['acao'] ?? '';
$voltar = $_POST['voltar'] ?? 'pesquisa.php';
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

$rep = reportagem_find($id);

if (!$rep) {
    $result = ['success' => false, 'message' => 'Reportagem não encontrada.', 'usuario_removido' => false];
} elseif ($acao === 'ignorar') {
    $ok = reportagem_ignorar($id);
    $result = [
        'success' => $ok,
        'message' => $ok ? 'Reportagem ignorada.' : 'Não foi possível ignorar a reportagem.',
        'usuario_removido' => false,
    ];
} elseif ($acao === 'apagar') {
    $result = reportagem_apagar($id);
} else {
    $result = ['success' => false, 'message' => 'Ação desconhecida.', 'usuario_removido' => false];
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

flash_set($result['message']);
header('Location: ' . ($result['usuario_removido'] ? 'pesquisa.php' : $voltar));
exit;
