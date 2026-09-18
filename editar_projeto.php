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
} elseif ($_SESSION['usuario_tipo'] === 3) {
    // EMPRESARIO
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
            if ($imagem_original) {
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

$usuarios_query = $pdo->prepare(
   'SELECT u.id, u.nome, u.email 
    FROM usuarios u 
    LEFT JOIN extra_usuarios e ON u.id = e.id_usuario
    WHERE u.id != ? AND u.tipo = 1 AND e.id_instituicao = ?'
);
$usuarios_query->execute([$usuario_id, $usuario_id_instituicao]);
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

//////////////////////////////////

$title = 'Editar ' . htmlspecialchars($projeto['nome']);
$href = 'dashboard.php';
include 'header.php';
?>


<main class='centrao'>
    <div style="height: 200px"></div>

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
    <div id="aba-visao-geral" style="min-width: 640px;">
        <form method="POST" action="">
            <input type="hidden" name="salvar_visao_geral" value="1">

            <div>
                <label for="nome_projeto"><strong>Nome do projeto:</strong></label><br>
                <input type="text" id="nome_projeto" name="nome_projeto" value="<?= htmlspecialchars($projeto['nome'] ?? '') ?>" required style="font-size: 1.5rem; font-weight: bold; width: 100%; max-width: 640px;">
            </div>
            <br>

            <!-- UPLOAD DA IMAGEM DO PROJETO -->
            <div id="area-clicavel-imagem" style="cursor: pointer; background-color:lightgray; background-position:center; background-repeat:none; background-size:cover; <?php if (!empty($projeto['img'])) echo 'background-image:url(\'data:image/webp;base64,' . base64_encode($projeto['img']) . '\');'; ?> display:flex; justify-content:center; align-items:center; width:640px; height:360px;">
                [ Clique para selecionar uma imagem ]
            </div>
            <script>
                document.getElementById("area-clicavel-imagem").onclick = function() {
                    document.getElementById("imagem_projeto").click();
                };
                const imgInput = document.getElementById("imagem_projeto");
                imgInput.onchange = function() {
                    if (imgInput.files.length > 0) {
                        document.getElementById('form-upload-imagem').submit();
                    }
                };
            </script>
            <br>

            <!-- MEMBROS -->
            <div>
                <strong>Membros:</strong>
                <div class="multiple_inline">
                    <div id="membros-ativos-lista" class="multiple_inline">
                        <?php foreach ($membros_ativos as $ma): ?>
                            <div id="membro-ativo-<?= $ma['id'] ?>">
                                <span><?= htmlspecialchars($ma['nome'] ?: $ma['email']) ?></span>
                                <?php if ($ma['id'] != $usuario_id && $ma['status_membro'] != 1): ?>
                                    <button type="button" onclick="removerMembroAJAX(<?= $ma['id'] ?>)" class="btn-x">x</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <script>
                async function removerMembroAJAX(idMembro) {
                    const formData = new FormData();
                    formData.append('ajax', '1');
                    formData.append('remover_membro_id', idMembro);

                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        const res = await response.json();

                        if (res.success) {
                            const elPendente = document.getElementById('membros-item-' + idMembro);
                            if (elPendente) elPendente.remove();

                            const elAtivo = document.getElementById('membro-ativo-' + idMembro);
                            if (elAtivo) elAtivo.remove();

                            const select = document.getElementById('select membro');
                            if (select && res.nome) {
                                const newOpt = document.createElement('option');
                                newOpt.value = idMembro;
                                newOpt.textContent = res.nome;
                                select.appendChild(newOpt);
                            }
                        }
                    } catch (err) {
                        console.error('Erro ao remover membro:', err);
                    }
                }
            </script>
            <br>

            <!-- CONVITES PENDENTES E NOVO CONVITE -->
            <div>
                <strong>Convites pendentes:</strong>
                <div class="multiple_inline">
                    <div id="membros-selecionados" class="multiple_inline">
                        <?php foreach ($membros_pendentes as $mp): ?>
                            <div id="membros-item-<?= $mp['id'] ?>">
                                <span><?= htmlspecialchars($mp['nome'] ?: $mp['email']) ?></span>
                                <button type="button" onclick="removerMembroAJAX(<?= $mp['id'] ?>)" class="btn-x">x</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="membros selector" class='selector' style="display: none;">
                        <select id="select membro" onchange="confirmarSelecao('membros')">
                            <option value="">Selecione um usuário para convidar...</option>
                            <?php 
                            $ids_existentes = array_column(array_merge($membros_ativos, $membros_pendentes), 'id');
                            foreach ($lista_usuarios as $u): 
                                if (in_array($u['id'], $ids_existentes)) continue;
                            ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nome'] ?: $u['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" class="plus" onclick="mostrarSeletor('membros')">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="72" height="72" fill="#111111"/>
                            <path d="M34.4878 45.52V37.16H26.4878V34.44H34.4878V26.08H37.5278V34.44H45.5278V37.16H37.5278V45.52H34.4878Z" fill="white"/>
                        </svg>
                    </button>
                </div>
            </div>
            <br>

            <div>
                <strong>Categorias:</strong>
                <div class="multiple_inline">
                    <div id="categorias-selecionados">
                        <?php foreach ($categorias_projeto as $cp): ?>
                            <div id="categorias-item-<?= $cp['id'] ?>">
                                <span><?= htmlspecialchars($cp['nome']) ?></span>
                                <input type="hidden" name="categorias[]" value="<?= $cp['id'] ?>">
                                <button type="button" onclick="removerItem('categorias-item-<?= $cp['id'] ?>')" class="btn-x">x</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="categorias selector" class='selector' style="display: none;">
                        <select id="select categoria" onchange="confirmarSelecao('categorias')">
                            <option value="">Selecione uma categoria...</option>
                            <?php foreach ($lista_categorias as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" class="plus" onclick="mostrarSeletor('categorias')">
                        <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="72" height="72" fill="#111111"/>
                            <path d="M34.4878 45.52V37.16H26.4878V34.44H34.4878V26.08H37.5278V34.44H45.5278V37.16H37.5278V45.52H34.4878Z" fill="white"/>
                        </svg>
                    </button>
                </div>
            </div>
            <br>

            <div>
                <label for="descricao"><strong>Descrição:</strong></label><br>
                <textarea id="descricao" name="descricao" rows="4" 
                style="width: 100%; max-width: 640px;"><?= htmlspecialchars($projeto['descricao'] ?? '') ?></textarea>
            </div>

            <br>
            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar Alterações</button>
            </div>
        </form>
    </div>

    <!-- HISTÓRIA DO PROJETO -->
    <div id="aba-historia" style="display: none; min-width: 640px;">

        <h2>História</h2>
        <form method="POST" action="">
            <input type="hidden" name="salvar_historia" value="1">

            <textarea name="historia_projeto" id="historia_projeto" 
            style="max-width: 640px;"><?= htmlspecialchars($projeto['historia'] ?? '') ?></textarea>

            <br>

            <div class='buttons_criar'>
                <button type="submit" class="btn-novo">Salvar Alterações</button>
            </div>
        </form>
    </div>

    <!-- AVALIAÇÕES -->
    <div id="aba-avaliacoes" style="display: none; min-width: 640px;">
        <div>
            <h2>Avaliações <?php 
                $total_comentarios = count($comentarios);
                if ($total_comentarios > 0) {
                    $soma_notas = array_sum(array_column($comentarios, 'nota'));
                    $media_nota = (int) round($soma_notas / $total_comentarios * 2) / 2;
                    $estrelas_cheias = (int) floor($media_nota);
                    if ($estrelas_cheias === $media_nota) {
                        echo str_repeat('★', $estrelas_cheias) . str_repeat('☆', 5 - $estrelas_cheias);
                    } else {
                        echo str_repeat('★', $estrelas_cheias) . '⯪' . str_repeat('☆', 4 - $estrelas_cheias);
                    }
                } else {
                    echo '☆☆☆☆☆';
                }
            ?></h2>    <button type="button" onclick="toggleFiltros()" class="btn-novo">Filtros +</button>
        </div>

        <!-- PAINEL DE FILTROS -->
        <div id="painel-filtros" style="display: none; margin: 15px 0; padding: 12px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 6px;">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div>
                    <label for="filtro_tipo"><strong>Exibir:</strong></label>
                    <select id="filtro_tipo" onchange="filtrarComentarios()">
                        <option value="todos">Todos os tipos</option>
                        <option value="professor">Apenas professores</option>
                        <option value="empresario">Apenas investidores</option>
                    </select>
                </div>
                <div>
                    <label for="filtro_nota"><strong>Avaliação:</strong></label>
                    <select id="filtro_nota" onchange="filtrarComentarios()">
                        <option value="todas">Todas as notas</option>
                        <option value="5">5 ★★★★★</option>
                        <option value="4">4 ★★★★☆</option>
                        <option value="3">3 ★★★☆☆</option>
                        <option value="2">2 ★★☆☆☆</option>
                        <option value="1">1 ★☆☆☆☆</option>
                    </select>
                </div>
                <div>
                    <label for="filtro_busca"><strong>Pesquisar palavra-chave:</strong></label>
                    <input type="text" id="filtro_busca" onkeyup="filtrarComentarios()" placeholder="Digite para filtrar..." style="padding: 4px 8px;">
                </div>
            </div>
        </div>

        <br>

        <div>
            <button type="button" onclick="toggleFormAvaliacao()" class="btn-novo" id="botao-toggle-avaliacao"><?= (empty($comentario_usuario)) ? 'Deixe sua avaliação +' : 'Editar avaliação' ?></button>
        </div>

        <div id="form-avaliacao-container" style="display: none;">
            <button type="button" onclick="toggleFormAvaliacao()" class="btn-novo btn-secundario">Cancelar x</button>
            <br><br>
            <form action="" method="POST">
                <input type="hidden" name="id_comentario" value="<?= (empty($comentario_usuario)) ? '0' : $comentario_usuario['id'] ?>">
                <div>
                    <textarea name="comentario" placeholder="Escreva uma avaliação..." rows="4" required><?php if (!empty($comentario_usuario)) { echo htmlspecialchars($comentario_usuario['comentario']); }?></textarea>
                </div>
                <br>
                <div>
                <?php if (empty($comentario_usuario)): ?>
                    <input type="hidden" name="nota" id="nota_input" value="5">
                    <span id="estrelas-rating" style="cursor: pointer; font-size: 1.3rem;">
                        <span onclick="definirNota(1)">★</span><span onclick="definirNota(2)">★</span><span onclick="definirNota(3)">★</span><span onclick="definirNota(4)">★</span><span onclick="definirNota(5)">★</span>
                    </span>
                <?php else: ?>
                    <input type="hidden" name="nota" id="nota_input" value="<?= $comentario_usuario['nota'] ?>">
                    <span id="estrelas-rating" style="cursor: pointer; font-size: 1.3rem;">
                        <?php $nota = (int)$comentario_usuario['nota'];
                        for ($i = 1; $i <= $nota; $i++): ?><span onclick="definirNota(<?= $i ?>)">★</span><?php endfor;
                        for ($i = $nota + 1; $i <= 5; $i++): ?><span onclick="definirNota(<?= $i ?>)">☆</span><?php endfor; ?>
                    </span>
                <?php endif; ?>
                    <button type="submit" class="btn-novo">→</button>
                </div>
            </form>
        </div>

        <br>

        <main id="lista-comentarios">
            <?php if (!empty($comentarios)): ?>
                <?php foreach ($comentarios as $c): ?>
                    <div class="card-comentario" 
                         data-tipo="<?= htmlspecialchars(mb_strtolower($c['tipo_usuario'])) ?>" 
                         data-nota="<?= (int)$c['nota'] ?>" 
                         data-texto="<?= htmlspecialchars(mb_strtolower($c['comentario'] . ' ' . $c['nome_usuario'])) ?>">
                        <div>
                            <div class="projeto-membros">
                                <img class="membro-avatar" style="background-image: url('data:image/webp;base64,<?= base64_encode($c['imagem_perfil']) ?>')" title="<?= htmlspecialchars($c['nome_usuario']); ?>">
                            </div>
                                <strong><?= htmlspecialchars($c['nome_usuario']) ?> (<?= htmlspecialchars(ucfirst(strtolower($c['tipo_usuario']))) ?>)</strong>
                            <span><?= str_pad(str_repeat('★', $c['nota']), 15, '☆') ?></span>
                        </div>
                        
                        <p><?= htmlspecialchars($c['comentario']) ?></p>
                        
                        <div>
                            <small>Data: <?= date('d/m/Y H:i', strtotime($c['data_criacao'])) ?></small>
                            <?php if ($c['data_criacao'] !== $c['data_edicao']): ?>
                            <br><small>Editada em: <?= date('d/m/Y H:i', strtotime($c['data_edicao'])) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhuma avaliação cadastrada para este projeto ainda.</p>
            <?php endif; ?>
        </main>
    </div>

    <!-- BARRA DE AÇÕES INFERIOR VISÍVEL EM TODAS AS SEÇÕES -->
    <hr style="margin-top: 30px; max-width: 640px; width: 100%;">
    <div style="display: flex; gap: 10px; margin: 15px 0; align-items: center; justify-content: space-between; max-width: 640px; width: 100%;">
        <div>
            <strong>Visibilidade atual:</strong> 
            <span><?= ($projeto['estado'] == 3) ? 'Público' : 'Restrito para Professores' ?></span>
        </div>
        <div style="display: flex; gap: 10px;">
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="alterar_estado_projeto" value="1">
                <input type="hidden" name="novo_estado" value="3">
                <button type="submit" class="btn-novo" style="background-color: #007bff; color: white;">Publicar projeto</button>
            </form>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="alterar_estado_projeto" value="1">
                <input type="hidden" name="novo_estado" value="1">
                <button type="submit" class="btn-novo" style="background-color: #dc3545; color: white;">Restringir visualização para professores</button>
            </form>
        </div>
    </div>
</main>

<?php 

////////////////////////////////// Inclusividades

include 'projeto_funcoes.php';

?>

<script>
    // parser da história do projeto
    function laxante(src) {
        const tokens = [];
        for (let i = 0, len = src.length; i < len; ++i) {
            if (src[i] === '\\') i++;
        }
        return tokens;
    }
</script>

</body>
</html>