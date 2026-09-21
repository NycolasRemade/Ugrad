<?php
session_start();
require_once 'Servidor/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['info_projeto'])) {
    header('Content-Type: application/json');

    $id = (int)$_GET['info_projeto'];

    $stmt = $pdo->prepare(
       'SELECT p.nome, p.data_criacao, pd.descricao, p.img
        FROM projetos p LEFT JOIN proj_dados pd
        ON p.id = pd.id_projeto
        WHERE p.id = ? AND p.estado = 3'
    );
    $stmt->execute([$id]);
    $proj = $stmt->fetch();

    if ($proj) {
        echo json_encode([
            'success' => true,
            'id' => $id,
            'nome' => $proj['nome'],
            'data_criacao' => $proj['data_criacao'],
            'descricao' => $proj['descricao'],
            'img' => base64_encode($proj['img'])
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Projeto inexistente']);
    exit;
}

$stmt_proj = $pdo->query('SELECT COUNT(*) from projetos WHERE estado = 3');
$projetos_qtd = (int)$stmt_proj->fetchColumn();

$stmt_proj = $pdo->query('SELECT id from projetos WHERE estado = 3');
$projetos_id = $stmt_proj->fetchAll();


$usuario_id = $_SESSION['usuario_id'];
$usuario_id_instituicao = $_SESSION['usuario_id_instituicao'] ?? null;

//////////////////////////////////
$title = 'Pesquisa de Projetos';
$href = 'dashboard.php';
$pdp = true;
include 'header.php'
?>

<div class='centrao'>

<div id='title_paper'>
    <h1 class='meringue'>Pesquisa de Projetos</h1>
    <img src="Fotos/Polygon 3.png" alt="title">
</div>

<script>
    const divFeedProjetos = document.getElementById("feed-projetos");
    function carregarInfoProjeto(id) {
        fetch('pesquisa_de_projetos.php?info_projeto=' + id)
        .then((response) => response.json())
        .then((data) => {
            const divProjeto = document.createElement("div");
            divProjeto.innerText = JSON.stringify(data);
            divFeedProjetos.appendChild(divProjeto);
        })
        .catch((error) => console.error(error));
    }
</script>

<div id='main_paper'>

    <?php if (!empty($erro)): ?>
        <p style="color: red; padding: 0 20px;"><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    <main id="feed-projetos">
        <?php for ($i = 0; $i < $projetos_id; $i++): ?>
            <div id="info-projeto-<?= $p['id'] ?>" style="background-color: lightgray; width: calc(50% - 16px); height: 128px;">
                <script>carregarInfoProjeto(<?= $p['id'] ?>);</script>
            </div>
        <?php endfor; ?>
    </main>

    <script>
        function gerarIntervaloAleatorio(min, max) {
            const lista = [];
            for (let i = min; i <= max; i++) lista.push(i);
            for (let i = lista.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [lista[i], lista[j]] = [lista[j], lista[i]];
            }
            return lista;
        }
        const idsProjetos = gerarIntervaloAleatorio(1, <?= $projetos_qtd ?>);

        let offset = 1;
        function carregar50projetos() {
            //
        }
    </script>

    </div>

</div>

</body>
</html>