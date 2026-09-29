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

$busca = trim($_GET['pesquisa'] ?? '');
$projetos_id = array();
$rand_id = array();

if ($busca !== '') {
    $stmt_proj = $pdo->prepare('SELECT id, nome, data_criacao, img FROM projetos WHERE estado = 3 AND nome LIKE ?');
    $stmt_proj->execute(['%' . $busca . '%']);
    $projetos_id = $stmt_proj->fetchAll();
    
}

else{
    $stmt_proj = $pdo->prepare('SELECT count(id) FROM projetos WHERE estado = 3');
    $stmt_proj->execute();
    $projetos_qtd = $stmt_proj->fetch();

    switch ($projetos_qtd) {
        case $projetos_qtd["count(id)"] == 0:
            $maxqtd = -1;
            break;

        case $projetos_qtd["count(id)"] < 20:
            $maxqtd = $projetos_qtd["count(id)"];
            break;
        
        default:
            $maxqtd = 20;
            break;
    }

    for ($i=0; $i <= $maxqtd; $i++) { 
        $rand_id[$i] = rand(0, $projetos_qtd["count(id)"]);
    }

    foreach ($rand_id as $idp) {
        $stmt_proj = $pdo->prepare('SELECT id, nome, data_criacao, img FROM projetos WHERE id = ? AND estado = 3');
        $stmt_proj->execute([$idp]);
        $projetos_id[] = $stmt_proj->fetchAll();
        
    }
}

if ($projetos_id) {
    echo json_encode([
        'success' => true,
        'id' => $id,
        'nome' => $proj['nome'],
        'data_criacao' => $proj['data_criacao'],
        'descricao' => $proj['descricao'],
        'img' => base64_encode($proj['img'])
    ]);
}


$usuario_id = $_SESSION['usuario_id'];
$usuario_id_instituicao = $_SESSION['usuario_id_instituicao'] ?? null;
//
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
            const divProjeto = document.getElementById("info-projeto-" + id);
            divProjeto.innerHTML = 
                "<a class='projeto-card box' href='projeto.php?id= " + id +">"
                '<p class="projeto-titulo">'+ data.id+ '</p>' 
                '<br>Criado em: ' + data.data_criacao + 
                '<br>Descrição: ' + data.descricao + 
                '<br><img style="width: 600px; height: 200px; background-position: center; background-size: cover; background-repeat: no-repeat; background-image: url(\'data:image/jpeg;base64,' + data.img + '\');"></a>';
        })
        .catch((error) => console.error(error));
    }

</script>

<div id='main_paper'>

    <form method="GET" action="" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input 
            type="text" 
            name="pesquisa" 
            placeholder="Pesquisar projetos por nome..." 
            value="<?= htmlspecialchars($busca) ?>" 
            style="padding: 8px 12px; width: 100%; max-width: 400px; font-size: 14px;"
        >
        <button type="submit" style="padding: 8px 16px; cursor: pointer;">Buscar</button>
        <?php if ($busca !== ''): ?>
            <a href="pesquisa_de_projetos.php" style="padding: 8px 16px; text-decoration: none; background: #ddd; color: #333; display: inline-flex; align-items: center;">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($erro)): ?>
        <p style="color: red; padding: 0 20px;"><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    <main id="feed-projetos" style="display:flex; flex-wrap: wrap;">

    <?php if($projetos_id): ?>
        <?php foreach ($projetos_id as $p): ?>
            <div id="info-projeto-<?= $p['id'] ?>" style="background-color: lightgray; width: calc(50% - 16px); height: fit-content;">
                <script>carregarInfoProjeto(<?= $p['id'] ?>);</script>
            </div>
        <?php endforeach; ?>

    <?php else:?>
        <p>Nenhum projeto</p>
    <?php endif;?>
    </main>

    </div>

</div>

</body>
</html>