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

// Consulta dados do projeto de acordo com o tipo de usuário
if ($_SESSION['usuario_tipo'] === 1) {
    // ALUNO
    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
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
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
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
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
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

// CONVIDAR E REMOVER MEMBROS SEM RECARREGAR A PÁGINA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // CONVIDAR MEMBRO
    if (isset($_POST['convidar_membro_id'])) {
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
    }

    // REMOVER OU CANCELAR CONVITE DE MEMBRO
    if (isset($_POST['remover_membro_id'])) {
        $id_remover = (int)$_POST['remover_membro_id'];
        $stmt_del_membro = $pdo->prepare('DELETE FROM proj_membros WHERE id_projeto = ? AND id_convidado = ?');
        $stmt_del_membro->execute([$id_projeto, $id_remover]);

        $stmt_u = $pdo->prepare('SELECT id, nome, email FROM usuarios WHERE id = ?');
        $stmt_u->execute([$id_remover]);
        $usr = $stmt_u->fetch();

        echo json_encode([
            'success' => true, 
            'id' => $id_remover,
            'nome' => $usr ? ($usr['nome'] ?: $usr['email']) : ''
        ]);
        exit;
    }
}

// SALVAR ALTERAÇÕES DO PROJETO (NOME, DESCRIÇÃO, HISTÓRIA, CATEGORIAS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_projeto'])) {
    $nome_projeto = trim($_POST['nome_projeto'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $historia = trim($_POST['historia'] ?? '');
    $categorias_selecionadas = isset($_POST['categorias']) ? array_filter($_POST['categorias']) : [];

    if (!empty($nome_projeto)) {
        try {
            $pdo->beginTransaction();

            $stmt_up_proj = $pdo->prepare('UPDATE projetos SET nome = ? WHERE id = ?');
            $stmt_up_proj->execute([$nome_projeto, $id_projeto]);

            $stmt_check_dados = $pdo->prepare('SELECT id_projeto FROM proj_dados WHERE id_projeto = ?');
            $stmt_check_dados->execute([$id_projeto]);

            if ($stmt_check_dados->fetch()) {
                $stmt_up_dados = $pdo->prepare('UPDATE proj_dados SET descricao = ?, historia = ? WHERE id_projeto = ?');
                $stmt_up_dados->execute([$descricao, $historia, $id_projeto]);
            } else {
                $stmt_in_dados = $pdo->prepare('INSERT INTO proj_dados (id_projeto, descricao, historia) VALUES (?, ?, ?)');
                $stmt_in_dados->execute([$descricao, $historia, $id_projeto]);
            }

            $stmt_del_cat = $pdo->prepare('DELETE FROM proj_categorias WHERE id_projeto = ?');
            $stmt_del_cat->execute([$id_projeto]);

            if (!empty($categorias_selecionadas)) {
                $stmt_in_cat = $pdo->prepare('INSERT INTO proj_categorias (id_projeto, id_categoria) VALUES (?, ?)');
                foreach ($categorias_selecionadas as $id_cat) {
                    $stmt_in_cat->execute([$id_projeto, $id_cat]);
                }
            }

            $pdo->commit();
            header('Location: editar_projeto.php?id=' . $id_projeto . '#visao-geral');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = 'Erro ao salvar projeto: ' . $e->getMessage();
        }
    }
}

// AVALIAÇÕES E COMENTÁRIOS
$stmt_coment_usr = $pdo->prepare('SELECT id, comentario, nota FROM comentarios WHERE id_usuario = ? AND id_projeto = ?');
$stmt_coment_usr->execute([$usuario_id, $id_projeto]);
$comentario_usuario = $stmt_coment_usr->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_comentario'])) {
    $comentario_texto = trim($_POST['comentario'] ?? '');
    $nota = isset($_POST['nota']) ? (int)$_POST['nota'] : null;

    if (!empty($comentario_texto) && $id_projeto) {
        if (empty($comentario_usuario)) {
            $stmt_ins = $pdo->prepare(
               'INSERT INTO comentarios (id_usuario, id_projeto, feedback, comentario, nota) 
                VALUES (?, ?, 0, ?, ?)'
            );
            $stmt_ins->execute([$usuario_id, $id_projeto, $comentario_texto, $nota]);
        } else {
            $stmt_upd = $pdo->prepare(
               'UPDATE comentarios SET comentario = ?, nota = ? WHERE id_usuario = ? AND id_projeto = ?'
            );
            $stmt_upd->execute([$comentario_texto, $nota, $usuario_id, $id_projeto]);
        }
        header('Location: editar_projeto.php?id=' . $id_projeto . '#avaliacoes');
        exit;
    }
}

// CONSULTA DE MEMBROS ATIVOS E PENDENTES
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

// CONSULTA DE CATEGORIAS VINCULADAS
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

// CONSULTA DE COMENTÁRIOS/AVALIAÇÕES
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

$title = 'Editar ' . htmlspecialchars($projeto['nome']);
$href = 'dashboard.php';
include 'header.php';
?>
<main style="margin-left: 32px">
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

    <!-- FORMULÁRIO PRINCIPAL ENGLOBANDO AS ABAS EDITÁVEIS -->
    <form method="POST" action="">
        <input type="hidden" name="salvar_projeto" value="1">

        <!-- VISÃO GERAL -->
        <div id="aba-visao-geral">
            <div>
                <label for="nome_projeto"><strong>Nome do projeto:</strong></label><br>
                <input type="text" id="nome_projeto" name="nome_projeto" value="<?= htmlspecialchars($projeto['nome'] ?? '') ?>" required style="font-size: 1.5rem; font-weight: bold; width: 100%; max-width: 640px;">
            </div>
            <br>

            <div>
                <?php if (!empty($projeto['img'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($projeto['img']) ?>" alt="Imagem do Projeto">
                <?php else: ?>
                    <div style="width: 640px; height:360px; background-color: lightgray; display: flex; justify-content: center; align-items: center;">
                        [ Imagem do Projeto ]
                    </div>
                <?php endif; ?>
            </div>
            <br>

            <!-- SEÇÃO DE MEMBROS ATIVOS -->
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
            <br>

            <!-- SEÇÃO DE CONVITES PENDENTES E NOVO CONVITE -->
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
                <textarea id="descricao" name="descricao" rows="4" style="width: 100%; max-width: 640px;"><?= htmlspecialchars($projeto['descricao'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- HISTÓRIA -->
        <div id="aba-historia" style="display: none;">
            <h2>História</h2>
            <div>
                <textarea id="historia" name="historia" rows="8" style="width: 100%; max-width: 640px;"><?= htmlspecialchars($projeto['historia'] ?? '') ?></textarea>
            </div>
        </div>

        <br>
        <div class='buttons_criar' id="acoes-salvar">
            <button type="submit" class="btn-novo">Salvar Alterações</button>
        </div>
    </form>

    <!-- AVALIAÇÕES -->
    <div id="aba-avaliacoes" style="display: none;">
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
            ?></h2>    <button type="button" onclick="toggleFiltros()">Filtros +</button>
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
            <button type="button" onclick="toggleFormAvaliacao()"><?= (empty($comentario_usuario)) ? 'Deixe sua avaliação +' : 'Editar avaliação' ?></button>
        </div>

        <br>

        <div id="form-avaliacao-container" style="display: none;">
            <button type="button" onclick="toggleFormAvaliacao()">Cancelar x</button>
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
                    <button type="submit">→</button>
                </div>
            </form>
        </div>

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
</main>

    <script>
        function mudarAba(nomeAba) {
            document.getElementById('aba-visao-geral').style.display = 'none';
            document.getElementById('aba-historia').style.display = 'none';
            document.getElementById('aba-avaliacoes').style.display = 'none';

            document.getElementById('btn_visao-geral').className = 'deepmod';
            document.getElementById('btn_historia').className = 'deepmod';
            document.getElementById('btn_avaliacoes').className = 'deepmod';

            const acoesSalvar = document.getElementById('acoes-salvar');

            if (nomeAba === 'avaliacoes') {
                if (acoesSalvar) acoesSalvar.style.display = 'none';
            } else {
                if (acoesSalvar) acoesSalvar.style.display = 'block';
            }

            const bloco = document.getElementById('aba-' + nomeAba);
            const botao = document.getElementById('btn_' + nomeAba);
            
            if (bloco) {
                bloco.style.display = 'block';
                location.hash = nomeAba;
                if (botao) botao.className = 'btn-novo_mod';
            } else {
                document.getElementById('aba-visao-geral').style.display = 'block';
                location.hash = 'visao-geral';
            }
        }

        if (location.hash && location.hash !== '#') mudarAba(location.hash.slice(1));
        else mudarAba('visao-geral');

        function toggleFormAvaliacao() {
            const formContainer = document.getElementById('form-avaliacao-container');
            formContainer.style.display = (formContainer.style.display === 'none') ? 'block' : 'none';
        }

        function definirNota(valor) {
            document.getElementById('nota_input').value = valor;
            const estrelas = document.querySelectorAll('#estrelas-rating span');
            estrelas.forEach((estrela, index) => {
                estrela.textContent = (index < valor) ? '★' : '☆';
            });
        }

        function mostrarSeletor(tipo) {
            const selectorDiv = document.getElementById(tipo + ' selector');
            selectorDiv.style.display = (selectorDiv.style.display === 'none') ? 'block' : 'none';
        }

        async function confirmarSelecao(tipo) {
            if (tipo === 'membros') {
                const select = document.getElementById('select membro');
                const value = select.value;
                if (!value) return;

                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('convidar_membro_id', value);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const res = await response.json();

                    if (res.success) {
                        const container = document.getElementById('membros-selecionados');
                        const itemDiv = document.createElement('div');
                        itemDiv.id = 'membros-item-' + res.id;
                        itemDiv.innerHTML = `
                            <span>${res.nome}</span>
                            <button type="button" onclick="removerMembroAJAX(${res.id})" class="btn-x">x</button>
                        `;
                        container.appendChild(itemDiv);

                        const optToRem = select.querySelector(`option[value="${value}"]`);
                        if (optToRem) optToRem.remove();

                        select.selectedIndex = 0;
                        document.getElementById('membros selector').style.display = 'none';
                    }
                } catch (err) {
                    console.error('Erro ao convidar membro:', err);
                }
                return;
            }

            const select = document.getElementById('select categoria');
            const value = select.value;
            const text = select.options[select.selectedIndex].text;

            if (!value) return;

            if (document.getElementById(tipo + '-item-' + value)) {
                select.selectedIndex = 0;
                document.getElementById(tipo + ' selector').style.display = 'none';
                return;
            }

            const container = document.getElementById(tipo + '-selecionados');
            const itemDiv = document.createElement('div');
            itemDiv.id = tipo + '-item-' + value;
            itemDiv.innerHTML = `
                <span>${text}</span>
                <input type="hidden" name="${tipo}[]" value="${value}">
                <button type="button" onclick="removerItem('${tipo}-item-${value}')" class="btn-x">x</button>
            `;

            container.appendChild(itemDiv);
            select.selectedIndex = 0;
            document.getElementById(tipo + ' selector').style.display = 'none';
        }

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

        function removerItem(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.remove();
            }
        }

        function toggleFiltros() {
            const painel = document.getElementById('painel-filtros');
            painel.style.display = (painel.style.display === 'none') ? 'block' : 'none';
        }

        function filtrarComentarios() {
            const tipo = document.getElementById('filtro_tipo').value.toLowerCase();
            const nota = document.getElementById('filtro_nota').value;
            const busca = document.getElementById('filtro_busca').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.card-comentario');

            cards.forEach(card => {
                const cardTipo = card.getAttribute('data-tipo') || '';
                const cardNota = card.getAttribute('data-nota') || '';
                const cardTexto = card.getAttribute('data-texto') || '';

                let atendeTipo = false;
                if (tipo === 'todos') {
                    atendeTipo = true;
                } else if (tipo === 'professor' && cardTipo.includes('professor')) {
                    atendeTipo = true;
                } else if (tipo === 'empresario' && (cardTipo.includes('empresario') || cardTipo.includes('investidor'))) {
                    atendeTipo = true;
                }

                let atendeNota = false;
                if (nota === 'todas' || cardNota === nota) {
                    atendeNota = true;
                }

                const atendeBusca = !busca || cardTexto.includes(busca);

                if (atendeTipo && atendeNota && atendeBusca) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>

</body>
</html>