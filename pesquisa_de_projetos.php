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
        echo json_encode(array_merge([
            'success' => true,
            'id' => $id,
            'nome' => $proj['nome'],
            'data_criacao' => $proj['data_criacao'],
            'descricao' => $proj['descricao'],
            'img' => base64_encode($proj['img'])
        ]));
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Projeto inexistente']);
    exit;
}

$stmt_proj = $pdo->query('SELECT id from projetos WHERE estado = 3');
$stmt_proj->execute();
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

<div id='main_paper'>

    <?php if (!empty($erro)): ?>
        <p style="color: red; padding: 0 20px;"><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    <?php foreach ($projetos_id as $p): ?>
        <div id="info-projeto-<?= $p['id'] ?>" style="background-color: lightgray; width: calc(50% - 16px); height: 128px;">
            <script type="module">
                const response = await fetch('pesquisa_de_projetos.php?info_projeto=<?= $p['id'] ?>');
                const data = await response.json();
                const divProjeto = document.getElementById('info-projeto-<?= $p['id'] ?>');
                divProjeto.innerText = JSON.stringify(data);
            </script>
        </div>
    <?php endforeach; ?>

    </div>

</div>

</body>
</html>