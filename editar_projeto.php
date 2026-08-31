<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nome_projeto = $_GET['nome'];

// dados do projeto
$stmt = $pdo->prepare(
   'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
    FROM projetos p 
    LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
    WHERE p.nome = ?'
);
$stmt->execute([$nome_projeto]);
$projeto = $stmt->fetch();
if (empty($projeto)) {
    header('Location: dashboard.php');
    exit;
}
$id_projeto = $projeto['id'];

$stmt = $pdo->prepare(
    'SELECT id, comentario, nota FROM comentarios WHERE id_usuario = ?'
);
$stmt->execute([$_SESSION['usuario_id']]);
$comentario_usuario = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_comentario'])) {
    $id_comentario = (int)$_POST['id_comentario'];
    $comentario_texto = trim($_POST['comentario'] ?? '');
    $nota = isset($_POST['nota']) ? (int) $_POST['nota'] : null;
    $feedback = 0;

    if (!empty($comentario_texto) && $id_projeto) {
        if (empty($comentario_usuario)) {
            $stmt_ins = $pdo->prepare(
               'INSERT INTO comentarios (id_usuario, id_projeto, feedback, comentario, nota) 
                VALUES (?, ?, ?, ?, ?)'
            );
            $stmt_ins->execute([$_SESSION['usuario_id'], $id_projeto, $feedback, $comentario_texto, $nota]);
        } else {
            $stmt_upd = $pdo->prepare(
               'UPDATE comentarios
                SET comentario = ?, nota = ?
                WHERE id_usuario = ?'
            );
            $stmt_upd->execute([$comentario_texto, $nota, $_SESSION['usuario_id']]);
        }
        header('Location: editar_projeto.php?nome=' . urlencode($nome_projeto));
        exit;
    }
}

// membros do projeto
$stmt = $pdo->prepare(
   'SELECT u.nome 
    FROM proj_membros pm 
    JOIN usuarios u ON pm.id_convidado = u.id 
    WHERE pm.id_projeto = ?'
);
$stmt->execute([$id_projeto]);
$membros = $stmt->fetchAll(PDO::FETCH_COLUMN);

// tags do projeto
$stmt = $pdo->prepare(
   'SELECT c.nome 
    FROM proj_categorias pc 
    JOIN categorias c ON pc.id_categoria = c.id 
    WHERE pc.id_projeto = ?'
);
$stmt->execute([$id_projeto]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

// comentários/avaliações
$stmt = $pdo->prepare(
   'SELECT c.comentario, c.nota, c.feedback, c.data_criacao, u.nome AS nome_usuario, tu.nome AS tipo_usuario, u.imagem_perfil
    FROM comentarios c
    JOIN usuarios u ON c.id_usuario = u.id
    JOIN tipos_usuario tu ON u.tipo = tu.id
    WHERE c.id_projeto = ?
    ORDER BY c.data_criacao DESC'
);
$stmt->execute([$id_projeto]);
$comentarios = $stmt->fetchAll();

//////////////////////////////////
$title = urldecode($_GET['nome'] ?? 'Projeto');
$href = 'dashboard.php';
include 'header.php'
?>

    <nav>
        <button type="button" onclick="mudarAba('visao-geral')">Visão geral</button>
        <button type="button" onclick="mudarAba('historia')">História</button>
        <button type="button" onclick="mudarAba('avaliacoes')">Avaliações</button>
    </nav>

    <hr>

    <!-- VISÃO GERAL -->
    <div id="aba-visao-geral">
        <h1><?= htmlspecialchars($projeto['nome'] ?? 'Nome do projeto') ?></h1>

        <div>
            <?php if (!empty($projeto['img'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($projeto['img']) ?>" alt="Imagem do Projeto">
            <?php else: ?>
                <div style="width: 640px; height:360px; background-color: lightgray; display: flex; justify-content: center; align-items: center;">
                    [ Imagem do Projeto ]
                </div>
            <?php endif; ?>
        </div>

        <p>
            <strong>Criado por:</strong> 
            <?= !empty($membros) ? htmlspecialchars(implode(', ', $membros)) : 'Nenhum integrante cadastrado' ?>
        </p>

        <p>
            <strong>Tags:</strong> 
            <?= !empty($tags) ? htmlspecialchars(implode(', ', $tags)) : 'Sem categorias' ?>
        </p>

        <div>
            <strong>Descrição:</strong>
            <p><?= nl2br(htmlspecialchars($projeto['descricao'] ?? 'Sem descrição disponível.')) ?></p>
        </div>
    </div>


    <!-- HISTÓRIA -->
    <div id="aba-historia" style="display: none;">
        <h2>História</h2>

        <div>
            <p><strong>Bloco inicial:</strong> <?= nl2br(htmlspecialchars($projeto['historia'] ?? 'Conteúdo da história não informado.')) ?></p>
        </div>

        <h2>Título</h2>

        <section>
            <p><strong>Bloco universal/com imagem:</strong> Conteúdo descritivo da seção de história do projeto...</p>
            
            <div style="width: 640px; height:360px; background-color: lightgray; display: flex; justify-content: center; align-items: center;">
                [ Imagem da História ]
            </div>

            <p><strong>Bloco universal/com imagem:</strong> Continuação do texto após a imagem...</p>
        </section>

        <h3>Subtítulo</h3>

        <div>
            <p><strong>Bloco pequeno:</strong> Texto curto complementar sobre a história.</p>
        </div>

        <div>
            <p><strong>Bloco médio:</strong> Texto com extensão média detalhando etapas do projeto.</p>
        </div>

        <div>
            <p><strong>Bloco grande:</strong> Texto longo detalhado apresentando reflexões, histórico e conquistas do projeto.</p>
        </div>
    </div>


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
            ?></h2>
            <button type="button">Filtros +</button>
        </div>

        <br>

        <div>
            <button type="button" onclick="toggleFormAvaliacao()"><?= (empty($comentario_usuario)) ? 'Deixe sua avaliação +' : 'Editar avaliação' ?></button>
        </div>

        <br>

        <!-- ENTRADA DE TEXTO E FORMULÁRIO DE AVALIAÇÃO -->
        <div id="form-avaliacao-container" style="display: none;">
            <button type="button" onclick="toggleFormAvaliacao()">Cancelar x</button>
            <br><br>
            <form action="" method="POST">
                <input type="hidden" name="id_comentario" value="<?= (empty($comentario_usuario)) ? '0' : $comentario_usuario['id'] ?>">
                <div>
                    <textarea name="comentario" placeholder="Escreva uma avaliação..." rows="4" required><?php if (!empty($comentario_usuario)) { echo $comentario_usuario['comentario']; }?></textarea>
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

        <main>
            <?php if (!empty($comentarios)): ?>
                <?php foreach ($comentarios as $c): ?>
                    <div>
                        <div>
                            <img class="membro-avatar" style="background-image: url(fotos-perfil/<?= $usuario['imagem_perfil'] ?>.webp)" title="<?= htmlspecialchars($c['nome_usuario']); ?>">
                            <strong><?= htmlspecialchars($c['nome_usuario']) ?> (<?= htmlspecialchars(ucfirst(strtolower($c['tipo_usuario']))) ?>)</strong>
                            <span><?= str_pad(str_repeat('★', $c['nota']), 15, '☆') ?></span>
                        </div>
                        
                        <p><?= htmlspecialchars($c['comentario']) ?></p>
                        
                        <div>
                            <small>Data: <?= date('d/m/Y', strtotime($c['data_criacao'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhuma avaliação cadastrada para este projeto ainda.</p>
            <?php endif; ?>
        </main>
    </div>


    <script>
        function mudarAba(nomeAba) {
            document.getElementById('aba-visao-geral').style.display = 'none';
            document.getElementById('aba-historia').style.display = 'none';
            document.getElementById('aba-avaliacoes').style.display = 'none';

            const bloco = document.getElementById('aba-' + nomeAba);
            if (bloco) {
                bloco.style.display = 'block';
                location.hash = nomeAba;
            } else {
                document.getElementById('aba-visao-geral').style.display = 'block';
                location.hash = 'visao-geral';
            }
        }
        if (location.hash && location.hash !== '#') mudarAba(location.hash.slice(1));

        function toggleFormAvaliacao() {
            const formContainer = document.getElementById('form-avaliacao-container');
            if (formContainer.style.display === 'none') {
                formContainer.style.display = 'block';
            } else {
                formContainer.style.display = 'none';
            }
        }

        function definirNota(valor) {
            document.getElementById('nota_input').value = valor;
            const estrelas = document.querySelectorAll('#estrelas-rating span');
            estrelas.forEach(function(estrela, index) {
                if (index < valor) {
                    estrela.textContent = '★';
                } else {
                    estrela.textContent = '☆';
                }
            });
        }
    </script>

</body>
</html>