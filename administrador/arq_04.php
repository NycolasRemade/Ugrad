<?php
require __DIR__ . '/includes/mod_01.php';
require __DIR__ . '/includes/mod_02.php';

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
    require __DIR__ . '/includes/mod_04.php';
    echo '<p>Turma não encontrada. <a href="arq_01.php">Voltar para a pesquisa</a>.</p>';
    require __DIR__ . '/includes/mod_05.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $result = ['success' => true, 'acao' => $_POST['acao'], 'id' => $id, 'message' => '', 'removed' => false];

    switch ($_POST['acao']) {
        case 'gerar_codigo':
            $gerado = instituicao_gerar_codigo((int)$turma['id_instituicao'], 'ALUNO', $id);
            $result['success'] = (bool)$gerado;
            if (!$gerado) {
                $result['message'] = 'Não foi possível gerar o código.';
            } elseif ($gerado['novo']) {
                $result['message'] = 'Código de acesso da turma ' . $turma['nome'] . ': ' . $gerado['codigo'];
            } else {
                $result['message'] = 'A turma ' . $turma['nome'] . ' teve o código de acesso atualizado: ' . $gerado['codigo'];
            }
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

    auditoria_registrar(($_POST['acao'] ?? '?'), $id, !empty($result['success']) ? 'ok' : 'falhou');
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Fallback sem JS: navegação normal com mensagem via sessão.
    flash_set($result['message']);
    header('Location: ' . ($result['removed'] ? 'arq_01.php?tab=turmas' : ('arq_04.php?id=' . $id)));
    exit;
}

$membros = membros_da_turma($id);
$codigos = codigos_da_turma($id);

if ($isPartial) {
    // Fragmento puro (sem <html>/header/footer) para ser injetado via fetch()
    require __DIR__ . '/includes/mod_08.php';
    exit;
}

$pageTitle = 'Painel de controle - Detalhes da turma';
require __DIR__ . '/includes/mod_04.php';
require __DIR__ . '/includes/mod_08.php';
require __DIR__ . '/includes/mod_05.php';
