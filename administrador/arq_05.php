<?php
require __DIR__ . '/includes/mod_01.php';
require __DIR__ . '/includes/mod_02.php';

$id = (int)($_GET['id'] ?? 0);
$projeto = find_projeto($id);
$isPartial = ($_GET['partial'] ?? '') === '1'; // pedido via AJAX pela tela de pesquisa
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if (!$projeto) {
    http_response_code(404);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Projeto não encontrado.']);
        exit;
    }
    if ($isPartial) {
        echo '<p class="detail-placeholder">Projeto não encontrado.</p>';
        exit;
    }
    $pageTitle = 'Projeto não encontrado';
    require __DIR__ . '/includes/mod_04.php';
    echo '<p>Projeto não encontrado. <a href="arq_01.php">Voltar para a pesquisa</a>.</p>';
    require __DIR__ . '/includes/mod_05.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $result = ['success' => true, 'acao' => $_POST['acao'], 'id' => $id, 'message' => '', 'removed' => false];

    switch ($_POST['acao']) {
        case 'excluir':
            if (projeto_excluir($id)) {
                $result['message'] = 'Projeto ' . $projeto['nome'] . ' excluído.';
                $result['removed'] = true;
            } else {
                $result['success'] = false;
                $result['message'] = 'Não foi possível excluir o projeto "' . $projeto['nome'] . '": há dados vinculados que não puderam ser removidos.';
            }
            break;
        default:
            $result['success'] = false;
            $result['message'] = 'Ação desconhecida.';
    }

    auditoria_registrar(($_POST['acao'] ?? '?'), $id, !empty($result['success']) ? 'ok' : 'falhou');
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Fallback sem JS: navegação normal com mensagem via sessão.
    flash_set($result['message']);
    header('Location: ' . ($result['removed'] ? 'arq_01.php?tab=projetos' : ('arq_05.php?id=' . $id)));
    exit;
}

$categorias = categorias_do_projeto($id);
$membros = membros_do_projeto($id);
$reportagens = reportagens_do_projeto($id);

if ($isPartial) {
    // Fragmento puro (sem <html>/header/footer) para ser injetado via fetch()
    require __DIR__ . '/includes/mod_07.php';
    exit;
}

$pageTitle = 'Painel de controle - Detalhes do projeto';
require __DIR__ . '/includes/mod_04.php';
require __DIR__ . '/includes/mod_07.php';
require __DIR__ . '/includes/mod_05.php';
