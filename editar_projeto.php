<?php
session_start();
require_once 'Servidor/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}
$id_projeto = (int)$_GET['id'];
$usuario_id = $_SESSION['usuario_id'];
$usuario_id_instituicao = $_SESSION['usuario_id_instituicao'] ?? 0;

$stmt = null;
$projeto = null;

// Consulta dados do projeto incluindo o campo estado
if ($_SESSION['usuario_tipo'] === 1) {
    // ALUNO
    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, p.estado, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        JOIN proj_membros pm ON p.id = pm.id_projeto
        WHERE p.id = ? AND pm.id_convidado = ?'
    );
    $stmt->execute([$id_projeto, $usuario_id]);
    $projeto = $stmt->fetch();
} elseif ($_SESSION['usuario_tipo'] === 2 || $_SESSION['usuario_tipo'] === 4) {
    // PROFESSOR ou INSTITUIÇÃO
    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, p.estado, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        INNER JOIN proj_membros pm ON p.id = pm.id_projeto
        LEFT JOIN extra_usuarios e ON e.id_usuario = pm.id_convidado
        WHERE p.id = ? AND pm.id_convidado = pm.id_convidante AND e.id_instituicao = ?'
    );
    $stmt->execute([$id_projeto, $usuario_id_instituicao]);
    $projeto = $stmt->fetch();
} elseif ($_SESSION['usuario_tipo'] === 5) {
    // ADMINISTRADOR
    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, p.estado, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        WHERE p.id = ?'
    );
    $stmt->execute([$id_projeto]);
    $projeto = $stmt->fetch();
}

if (empty($projeto)) {
    header('Location: dashboard.php');
    exit;
}

// upload da imagem do projeto
$max_allowed_packet = $pdo->query('SELECT @@global.max_allowed_packet')->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // alterar estado/visibilidade do projeto (Público / Restrito a Professores)
    if (isset($_POST['alterar_estado_projeto'])) {
        $novo_estado = (int)$_POST['novo_estado'];
        if (in_array($novo_estado, [1, 3])) { // 3 = PUBLICO_PUBLICO, 1 = PRIVADO_PRIVADO
            try {
                $stmt_est = $pdo->prepare('UPDATE projetos SET estado = ? WHERE id = ?');
                $stmt_est->execute([$novo_estado, $id_projeto]);
                header("Location: editar_projeto.php?id=$id_projeto");
                exit;
            } catch (Exception $e) {
                $erro = 'Erro ao alterar a visibilidade do projeto.';
            }
        }
    }

    // convidar e remover membros sem recarregar a página
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');

        // convidar membro
        if (isset($_POST['convidar_membro_id'])) {
            try {
                $id_convidar = (int)$_POST['convidar_membro_id'];
                if ($id_convidar > 0) {
                    $stmt_m_exist = $pdo->prepare('SELECT id_convidado FROM proj_membros WHERE id_projeto = ? AND id_convidado = ?');
                    $stmt_m_exist->execute([$id_projeto, $id_convidar]);
                    
                    if (!$stmt_m_exist->fetch()) {
                        $stmt_in_membro = $pdo->prepare('INSERT INTO proj_membros (id_convidante, id_convidado, id_projeto, status_membro) VALUES (?, ?, ?, 3)');
                        $stmt_in_membro->execute([$usuario_id, $id_convidar, $id_projeto]);

                        $stmt_u = $pdo->prepare('SELECT id, nome, email FROM usuarios WHERE id = ?');
                        $stmt_u->execute([$id_convidar]);
                        $usr = $stmt_u->fetch();

                        echo json_encode([
                            'success' => true, 
                            'id' => $usr['id'], 
                            'nome' => $usr['nome'] ?: $usr['email']
                        ]);
                        exit;
                    }
                }
                echo json_encode(['success' => false, 'message' => 'Membro já cadastrado ou inválido']);
                exit;
            } catch (\PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar']);
                exit;
            }
        }

        // remover membro ou cancelar convite
        if (isset($_POST['remover_membro_id'])) {
            try {
                $id_remover = (int)$_POST['remover_membro_id'];
                $stmt_del_membro = $pdo->prepare('DELETE FROM proj_membros WHERE id_projeto = ? AND id_convidado = ?');
                $stmt_del_membro->execute([$id_projeto, $id_remover]);

                echo json_encode(['success' => true, 'id' => $id_remover]);
                exit;
            } catch (\PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao remover membro']);
                exit;
            }
        }
    }

    // salvar imagem do projeto
    if (isset($_FILES['imagem_projeto'])) {

        $imagem = $_FILES['imagem_projeto'];
        if (
            $imagem['size'] > 5 * 1024 * 1024 || 
            $imagem['error'] === UPLOAD_ERR_INI_SIZE || 
            $imagem['error'] === UPLOAD_ERR_FORM_SIZE
        ) {
            $erro = 'A imagem excede o tamanho máximo permitido de 5MB.';
        } elseif ($imagem['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Erro no upload.';
        } else {
            $imgInfo = getimagesize($imagem['tmp_name']);
            switch ($imgInfo['mime']) {
                case 'image/jpeg':
                    $imagem_original = imagecreatefromjpeg($imagem['tmp_name']);
                    break;
                case 'image/png':
                    $imagem_original = imagecreatefrompng($imagem['tmp_name']);
                    imagepalettetotruecolor($imagem_original);
                    imagealphablending($imagem_original, false);
                    imagesavealpha($imagem_original, true);
                    break;
                case 'image/webp':
                    $imagem_original = imagecreatefromwebp($imagem['tmp_name']);
                    break;
                default:
                    $erro = 'Apenas os formatos .jpeg, .png e .webp são permitidos. Selecione uma imagem válida.';
            }
            if (isset($imagem_original) && $imagem_original) {
                ob_start();
                imagewebp($imagem_original, null, 70);
                imagedestroy($imagem_original);
                $imagem_nova = ob_get_clean();

                $stmt = $pdo->prepare('UPDATE projetos SET img = ? WHERE id = ?');
                $stmt->bindParam(1, $imagem_nova, PDO::PARAM_LOB);
                $stmt->bindParam(2, $id_projeto);
                $stmt->execute();

                $mensagem_sucesso = 'Imagem de perfil atualizada!';
                header("Location: editar_projeto.php?id=$id_projeto");
                exit;
            }
        }
    }

    // salvar edição da visão geral
    if (isset($_POST['salvar_visao_geral'])) {
        $nome_projeto = trim($_POST['nome_projeto'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categorias_selecionadas = isset($_POST['categorias']) ? array_filter($_POST['categorias']) : [];

        if (!empty($nome_projeto)) {
            try {
                $pdo->beginTransaction();

                $stmt_up_proj = $pdo->prepare('UPDATE projetos SET nome = ? WHERE id = ?');
                $stmt_up_proj->execute([$nome_projeto, $id_projeto]);

                $stmt_up_dados = $pdo->prepare('UPDATE proj_dados SET descricao = ? WHERE id_projeto = ?');
                $stmt_up_dados->execute([$descricao, $id_projeto]);

                $stmt_del_cat = $pdo->prepare('DELETE FROM proj_categorias WHERE id_projeto = ?');
                $stmt_del_cat->execute([$id_projeto]);

                if (!empty($categorias_selecionadas)) {
                    $stmt_in_cat = $pdo->prepare('INSERT INTO proj_categorias (id_projeto, id_categoria) VALUES (?, ?)');
                    foreach ($categorias_selecionadas as $id_cat) {
                        $stmt_in_cat->execute([$id_projeto, $id_cat]);
                    }
                }

                $pdo->commit();
                header("Location: editar_projeto.php?id=$id_projeto");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = 'Erro ao salvar projeto';
            }
        }

    }

    // salvar edição da história do projeto
    if (isset($_POST['salvar_historia'])) {
        $historia = trim($_POST['historia_projeto'] ?? '');

        $stmt = $pdo->prepare('UPDATE proj_dados SET historia = ? WHERE id_projeto = ?');
        $stmt->execute([$historia, $id_projeto]);

        $mensagem_sucesso = 'História do projeto atualizada!';
        header("Location: editar_projeto.php?id=$id_projeto");
        exit;
    }
}

// avaliações e comentários
$stmt_coment_usr = $pdo->prepare('SELECT id, comentario, nota FROM comentarios WHERE id_usuario = ? AND id_projeto = ?');
$stmt_coment_usr->execute([$usuario_id, $id_projeto]);
$comentario_usuario = $stmt_coment_usr->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_comentario'])) {
    $comentario_texto = trim($_POST['comentario'] ?? '');
    $nota = isset($_POST['nota']) ? (int)$_POST['nota'] : null;

    if (!empty($comentario_texto) && $id_projeto) {
        try {
            if (empty($comentario_usuario)) {
                $stmt_ins = $pdo->prepare(
                   'INSERT INTO comentarios (id_usuario, id_projeto, feedback, comentario, nota) 
                    VALUES (?, ?, 0, ?, ?)'
                );
                $stmt_ins->execute([$usuario_id, $id_projeto, $comentario_texto, $nota]);
            } else {
                $stmt_upd = $pdo->prepare(
                   'UPDATE comentarios 
                    SET comentario = ?, nota = ? 
                    WHERE id_usuario = ? AND id_projeto = ?'
                );
                $stmt_upd->execute([$comentario_texto, $nota, $usuario_id, $id_projeto]);
            }
            header("Location: editar_projeto.php?id=$id_projeto#avaliacoes");
            exit;
        } catch (\PDOException $e) {
            $erro = 'Erro ao salvar comentário';
        }
    }
}

// consulta de membros ativos e pendentes
$stmt_membros_ativos = $pdo->prepare(
   'SELECT u.id, u.nome, u.email, pm.status_membro
    FROM proj_membros pm 
    JOIN usuarios u ON pm.id_convidado = u.id 
    WHERE pm.id_projeto = ? AND (pm.status_membro = 1 OR pm.status_membro = 2)'
);
$stmt_membros_ativos->execute([$id_projeto]);
$membros_ativos = $stmt_membros_ativos->fetchAll();

$stmt_membros_pendentes = $pdo->prepare(
   'SELECT u.id, u.nome, u.email
    FROM proj_membros pm 
    JOIN usuarios u ON pm.id_convidado = u.id 
    WHERE pm.id_projeto = ? AND pm.status_membro = 3'
);
$stmt_membros_pendentes->execute([$id_projeto]);
$membros_pendentes = $stmt_membros_pendentes->fetchAll();

// categorias do projeto
$stmt_cat_proj = $pdo->prepare(
   'SELECT c.id, c.nome 
    FROM proj_categorias pc 
    JOIN categorias c ON pc.id_categoria = c.id 
    WHERE pc.id_projeto = ?'
);
$stmt_cat_proj->execute([$id_projeto]);
$categorias_projeto = $stmt_cat_proj->fetchAll();

$stmt = $pdo->prepare('SELECT id_turma FROM extra_usuarios WHERE id_usuario = ?');
$stmt->execute([$usuario_id]);
$usuario_turma = $stmt->fetchColumn();

$usuarios_query = $pdo->prepare(
   'SELECT u.id, u.nome, u.email 
    FROM usuarios u 
    LEFT JOIN extra_usuarios e ON u.id = e.id_usuario
    WHERE u.id != ? AND u.tipo = 1 AND e.id_turma = ? AND e.id_instituicao = ?'
);
$usuarios_query->execute([$usuario_id, $usuario_turma, $usuario_id_instituicao]);
$lista_usuarios = $usuarios_query->fetchAll();

$categorias_query = $pdo->query('SELECT id, nome FROM categorias');
$lista_categorias = $categorias_query->fetchAll();

// comentários/avaliações
try {
    $stmt_coments = $pdo->prepare(
       'SELECT c.comentario, c.nota, c.feedback, c.data_criacao, c.data_edicao, u.nome AS nome_usuario, tu.nome AS tipo_usuario, u.imagem_perfil
        FROM comentarios c
        JOIN usuarios u ON c.id_usuario = u.id
        JOIN tipos_usuario tu ON u.tipo = tu.id
        WHERE c.id_projeto = ?
        ORDER BY c.data_criacao DESC'
    );
    $stmt_coments->execute([$id_projeto]);
    $comentarios = $stmt_coments->fetchAll();
} catch (PDOException $e) {
    $comentarios = [];
}
//
//////////////////////////////////

$title = 'Editar ' . htmlspecialchars($projeto['nome']);
$href = 'dashboard.php';
$baseUrl = '_projeto';
include 'header.php';
?>



<main class='centrao'>
    <div style="height: 100px"></div>


<div id='main_paper_edit'>
    <nav id='mudaraba'>
        <div onclick="mudarAba('visao-geral')" id='btn_visao-geral'><p>Visão geral</p></div>
        <div onclick="mudarAba('historia')" id='btn_historia'><p>História</p></div>
        <div onclick="mudarAba('avaliacoes')" id='btn_avaliacoes'><p>Avaliações</p></div>
    </nav>

    <hr>

    <?php if (!empty($erro)): ?>
        <p style="color: red; padding: 0 20px;"><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    <!-- FORMULÁRIO OCULTO PARA UPLOAD DA IMAGEM -->
    <form id="form-upload-imagem" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="file" id="imagem_projeto" name="imagem_projeto" accept="image/png, image/jpeg, image/webp" >
    </form>

    <!-- VISÃO GERAL -->

    <?=include 'editar_visao-geral.php';?>

    <!-- AVALIAÇÕES -->

    <?=include 'editar_avaliacoes.php'?>
    
</div>

    <!-- HISTÓRIA DO PROJETO -->

    <?=include 'editar_historia.php';?>

    <!-- BARRA DE AÇÕES INFERIOR -->
    <hr style="margin-top: 30px; max-width: 640px; width: 100%;">
    <div>
        <div>
            <strong>Visibilidade atual:</strong> 
            <span><?= ($projeto['estado'] == 3) ? 'Público' : (($projeto['estado'] == 2) ? 'Restrito para Professores' : (($projeto['estado'] == 1) ? 'Privado' : 'Em revisão' ))?></span>
        </div>
        <div style="display: flex; gap: 10px; margin: 15px 0; align-items: center; justify-content: space-between; max-width: 640px; width: 100%;">
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="alterar_estado_projeto" value="1">
                <input type="hidden" name="novo_estado" value="3">
                <button type="submit" class="btn-novo" style="background-color: #005EFF; color: white;">Publicar projeto</button>
            </form>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="alterar_estado_projeto" value="1">
                <input type="hidden" name="novo_estado" value="1">
                <button type="submit" class="btn-novo" style="background-color: #C50000; color: white;">Restringir visualização para professores</button>
            </form>
        </div>
    </div>
</main>

<?= include 'projeto_funcoes.php'; ?>

</body>
</html>