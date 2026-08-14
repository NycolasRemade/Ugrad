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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_comentario'])) {
    $comentario_texto = trim($_POST['comentario'] ?? '');
    $nota = isset($_POST['nota']) ? (int) $_POST['nota'] : null;
    $feedback = 0;

    if (!empty($comentario_texto) && $id_projeto) {
        $stmt_ins = $pdo->prepare(
           'INSERT INTO comentarios (id_usuario, id_projeto, feedback, comentario, nota) 
            VALUES (?, ?, ?, ?, ?)'
        );
        $stmt_ins->execute([$_SESSION['usuario_id'], $id_projeto, $feedback, $comentario_texto, $nota]);

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
   'SELECT c.comentario, c.nota, c.feedback, c.data_criacao, u.nome AS nome_usuario, tu.nome AS tipo_usuario 
    FROM comentarios c
    JOIN usuarios u ON c.id_usuario = u.id
    JOIN tipos_usuario tu ON u.tipo = tu.id
    WHERE c.id_projeto = ?
    ORDER BY c.data_criacao DESC'
);
$stmt->execute([$id_projeto]);
$comentarios = $stmt->fetchAll();
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?= urldecode($_GET['nome'] ?? 'Projeto') ?></title>
</head>
<body>

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
                <div style="width: 100%; height: 300px; background-color: #ccc; text-align: center; line-height: 300px;">
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
            
            <div style="width: 80%; height: 200px; background-color: #ccc; margin: 10px 0; text-align: center; line-height: 200px;">
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
            <h2>Avaliações ★★★★☆</h2>
            <button type="button">Filtros +</button>
        </div>

        <br>

        <div>
            <button type="button" onclick="toggleFormAvaliacao()">Deixe sua avaliação +</button>
        </div>

        <br>

        <!-- ENTRADA DE TEXTO E FORMULÁRIO DE AVALIAÇÃO -->
        <div id="form-avaliacao-container" style="display: none;">
            <button type="button" onclick="toggleFormAvaliacao()">Cancelar x</button>
            <br><br>
            <form action="" method="POST">
                <input type="hidden" name="salvar_comentario" value="1">
                <div>
                    <textarea name="comentario" placeholder="Escreva uma avaliação..." rows="4" style="width: 100%;" required></textarea>
                </div>
                <br>
                <div>
                    <div>
                        <label for="nota">Nota:</label>
                        <select name="nota" id="nota">
                            <option value="5">★★★★★ (5)</option>
                            <option value="4">★★★★☆ (4)</option>
                            <option value="3">★★★☆☆ (3)</option>
                            <option value="2">★★☆☆☆ (2)</option>
                            <option value="1">★☆☆☆☆ (1)</option>
                        </select>
                    </div>
                    <button type="submit">→</button>
                </div>
            </form>
        </div>

        <main>
            <?php if (!empty($comentarios)): ?>
                <?php foreach ($comentarios as $c): ?>
                    <article>
                        <header>
                            <strong><?= htmlspecialchars(ucfirst(strtolower($c['tipo_usuario']))) ?> (<?= htmlspecialchars($c['nome_usuario']) ?>)</strong>
                            <span><?= str_pad(str_repeat('★', $c['nota']), 5, '☆') ?></span>
                        </header>
                        
                        <p><?= nl2br(htmlspecialchars($c['comentario'])) ?></p>
                        
                        <footer>
                            <small>Data: <?= date('d/m/Y', strtotime($c['data_criacao'])) ?></small>
                        </footer>
                    </article>
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
    </script>

</body>
</html>