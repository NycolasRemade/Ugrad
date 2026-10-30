<?php
require __DIR__ . '/includes/auth_admin.php';
require __DIR__ . '/includes/data.php';

$id = (int)($_GET['id'] ?? 0);
$turma = find_turma($id);
$isPartial = ($_GET['partial'] ?? '') === '1'; // pedido via AJAX pela tela de pesquisa
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if (!$turma) {
    http_response_code(404);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Turma não encontrada.']);
        exit;
    }
    if ($isPartial) {
        echo '<p class="detail-placeholder">Turma não encontrada.</p>';
        exit;
    }
    $pageTitle = 'Turma não encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<p>Turma não encontrada. <a href="pesquisa.php">Voltar para a pesquisa</a>.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $result = ['success' => true, 'acao' => $_POST['acao'], 'id' => $id, 'message' => '', 'removed' => false];

    switch ($_POST['acao']) {
        case 'gerar_codigo':
            $codigo = instituicao_gerar_codigo((int)$turma['id_instituicao'], 'ALUNO', $id);
            $result['success'] = (bool)$codigo;
            $result['message'] = $codigo
                ? 'Novo código de acesso da turma ' . $turma['nome'] . ': ' . $codigo
                : 'Não foi possível gerar o código.';
            break;
        case 'excluir':
            if (turma_excluir($id)) {
                $result['message'] = 'Turma ' . $turma['nome'] . ' excluída. Os alunos ficaram sem turma.';
                $result['removed'] = true;
            } else {
                $result['success'] = false;
                $result['message'] = 'Não foi possível excluir a turma "' . $turma['nome'] . '".';
            }
            break;
        default:
            $result['success'] = false;
            $result['message'] = 'Ação desconhecida.';
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Fallback sem JS: navegação normal com mensagem via sessão.
    flash_set($result['message']);
    header('Location: ' . ($result['removed'] ? 'pesquisa.php?tab=turmas' : ('turma.php?id=' . $id)));
    exit;
}

$membros = membros_da_turma($id);
$codigos = codigos_da_turma($id);

if ($isPartial) {
    // Fragmento puro (sem <html>/header/footer) para ser injetado via fetch()
    require __DIR__ . '/includes/turma_detail.php';
    exit;
}

$pageTitle = 'Painel de controle - Detalhes da turma';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/turma_detail.php';
require __DIR__ . '/includes/footer.php';
