<?php
if (!isset($_SESSION))
    session_start();
if ($_SESSION['usuario_tipo'] != 5) {
    echo 'sem permissão';
    exit;
}
require 'includes/data.php';

$id = (int)($_GET['id'] ?? 0);
$instituicao = find_instituicao($id);
$isPartial = ($_GET['partial'] ?? '') === '1';
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if (!$instituicao) {
    http_response_code(404);
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Instituição não encontrada.']);
        exit;
    }
    if ($isPartial) {
        echo '<div class="small"><p style="color:#C50000;">Instituição não encontrada.</p></div>';
        exit;
    }
    $pageTitle = 'Instituição não encontrada';
    echo '<div class="dashboard-container"><p>Instituição não encontrada. <a href="pesquisa.php">Voltar para a pesquisa</a>.</p></div>';
?>
</main>
</div>
</body>
</html>
<?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $result = ['success' => true, 'acao' => $_POST['acao'], 'id' => $id, 'message' => '', 'removed' => false];

    switch ($_POST['acao']) {
        case 'gerar_codigo':
            $codigo = instituicao_gerar_codigo($id, 'ALUNO');
            $result['success'] = (bool)$codigo;
            $result['message'] = $codigo
                ? 'Novo código de acesso gerado: ' . $codigo
                : 'Não foi possível gerar o código (tipo "ALUNO" não encontrado em tipos_usuario).';
            break;
        case 'senha':
            $result['message'] = 'Link de alteração de senha seria enviado (envio de e-mail não implementado).';
            break;
        case 'desativar':
            usuario_toggle_ativacao($id, false);
            $result['message'] = 'Instituição ' . $instituicao['nome'] . ' desativada.';
            break;
        case 'reativar':
            usuario_toggle_ativacao($id, true);
            $result['message'] = 'Instituição ' . $instituicao['nome'] . ' reativada.';
            break;
        case 'excluir':
            if (usuario_excluir($id)) {
                $result['message'] = 'Instituição ' . $instituicao['nome'] . ' excluída.';
                $result['removed'] = true;
            } else {
                $result['success'] = false;
                $result['message'] = 'Não foi possível excluir "' . $instituicao['nome'] . '": existem registros vinculados que não puderam ser removidos automaticamente.';
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

    flash_set($result['message']);
    header('Location: ' . ($result['removed'] ? 'pesquisa.php' : ('instituicao.php?id=' . $id)));
    exit;
}

$professores = professores_da_instituicao($id);
$turmas = turmas_da_instituicao($id);

if ($isPartial) {
    require __DIR__ . '/includes/instituicao_detail.php';
    exit;
}

$pageTitle = 'Painel de controle - Detalhes da instituição';
require __DIR__ . '/includes/instituicao_detail.php';
?>
</main>
</div>
</body>
</html>