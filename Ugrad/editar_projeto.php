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
    WHERE p.id = ?'
);
$stmt->execute([$nome_projeto]);
$projeto = $stmt->fetch();
$id_projeto = $projeto['id'];

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
   'SELECT c.comentario, c.feedback, c.data_criacao, u.nome AS nome_usuario, tu.nome AS tipo_usuario 
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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
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
            <button type="button">Deixe sua avaliação +</button>
        </div>

        <br>

        <main>
            <?php if (!empty($comentarios)): ?>
                <?php foreach ($comentarios as $c): ?>
                    <article style="border: 1px solid #ccc; padding: 10px; margin-bottom: 15px;">
                        <header>
                            <strong><?= htmlspecialchars(ucfirst(strtolower($c['tipo_usuario']))) ?> (<?= htmlspecialchars($c['nome_usuario']) ?>)</strong>
                            <span><?= $c['feedback'] ? '★★★★★' : '★★★☆☆' ?></span>
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

            document.getElementById('aba-' + nomeAba).style.display = 'block';
        }
    </script>

</body>
</html>