<?php
require __DIR__ . '/includes/data.php';

$id = (int)($_GET['id'] ?? 0);
$usuario = find_usuario($id);
$isPartial = ($_GET['partial'] ?? '') === '1'; // pedido via AJAX pela tela de pesquisa
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if (!$usuario) {
    http_response_code(404);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }
    if ($isPartial) {
        echo '<p class="dim" style="padding:16px;font-size:13px;">Usuário não encontrado.</p>';
        exit;
    }
    $pageTitle = 'Usuário não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<p>Usuário não encontrado. <a href="pesquisa.php">Voltar para a pesquisa</a>.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Ações reais no banco (desativar / reativar / excluir / solicitar senha).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $result = ['success' => true, 'acao' => $_POST['acao'], 'id' => $id, 'message' => '', 'removed' => false];

    switch ($_POST['acao']) {
        case 'senha':
            // Não há sistema de e-mail/token neste protótipo — apenas confirma a intenção.
            $result['message'] = 'Link de alteração de senha seria enviado para ' . $usuario['email'] . ' (envio de e-mail não implementado).';
            break;
        case 'desativar':
            usuario_toggle_ativacao($id, false);
            $result['message'] = 'Conta de ' . $usuario['nome'] . ' desativada.';
            break;
        case 'reativar':
            usuario_toggle_ativacao($id, true);
            $result['message'] = 'Conta de ' . $usuario['nome'] . ' reativada.';
            break;
        case 'excluir':
            if (usuario_excluir($id)) {
                $result['message'] = 'Conta de ' . $usuario['nome'] . ' excluída.';
                $result['removed'] = true;
            } else {
                $result['success'] = false;
                $result['message'] = 'Não foi possível excluir "' . $usuario['nome'] . '": existem registros vinculados a este usuário que não puderam ser removidos automaticamente.';
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
    header('Location: ' . ($result['removed'] ? 'pesquisa.php' : ('usuario.php?id=' . $id)));
    exit;
}

$projetos = projetos_do_usuario($id);
$avaliacoes = avaliacoes_do_usuario($id);

if ($isPartial) {
    // Fragmento puro (sem <html>/header/footer) para ser injetado via fetch()
    require __DIR__ . '/includes/usuario_detail.php';
    exit;
}

$pageTitle = 'Painel de controle - Detalhes do usuário';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/usuario_detail.php';

?>
</main>
</div>
</body>
</html>

