<?php
session_start();
require_once 'Servidor/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['info_projeto'])) {

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
    if (isset($_GET['offset'])) {

        header('Content-Type: application/json');

        $offset = (int)$_GET['offset'];
        $stmt = $pdo->prepare(
           'SELECT id 
            FROM projetos 
            WHERE estado = 3 
            ORDER BY id 
            LIMIT 20 OFFSET ?'
        );
        $stmt->execute([$offset]);
        $projetos_id = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'ids' => $projetos_id ]);
        exit;
    }
}

$busca = trim($_GET['pesquisa'] ?? '');
$projetos_id = array();

if ($busca !== '') {
    // Busca de projetos por nome
    $stmt_proj = $pdo->prepare('SELECT id, nome, data_criacao, img FROM projetos WHERE estado = 3 AND nome LIKE ?');
    $stmt_proj->execute(['%' . $busca . '%']);
    $projetos_id = $stmt_proj->fetchAll();
    
} else {
    // Algorítimo de pesquisa de projetos (temporário-final-meioquefinal-sóquenão)
    $stmt_proj = $pdo->query('SELECT id FROM projetos WHERE estado = 3 ORDER BY id LIMIT 20');
    $projetos_id = $stmt_proj->fetchAll();

    $stmt_proj_qtd = $pdo->query('SELECT COUNT(id) FROM projetos WHERE estado = 3');
    $projetos_qtd = $stmt_proj_qtd->fetchColumn();

    // $projetos_id = $stmt_proj_todos->fetch();

    // $rand_id = array();

    // if ($projetos_qtd['count(id)'] == 0) {
    //     $rand_id['None'] = -1;
    // } else if ($projetos_qtd['count(id)'] < 20) {
    //     $rand_id = range(1,$projetos_qtd['count(id)']);
    //     shuffle($rand_id);
    // } else {
    //     while (sizeof($rand_id) < 20) {
    //         $rand_num = rand(1, $projetos_qtd['count(id)']);
    //         $rand_id[$rand_num] = $rand_num;
    //     }
    // }

    // foreach ($rand_id as $idp) {
    //     $stmt_proj = $pdo->prepare('SELECT id, nome, data_criacao, img FROM projetos WHERE id = ? AND estado = 3');
    //     $stmt_proj->execute([$idp]);
    //     $projetos_id = $stmt_proj->fetchAll();
        
    // }
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
                '<a class="projeto-card box" href="projeto.php?id=' + id +'">' +
                '<p class="projeto-titulo">'+ data.nome + '</p>' +
                '<br>Criado em: ' + data.data_criacao + 
                (data.descricao ? '<br>Descrição: ' + data.descricao : '') + 
                '<br><div style="width: 600px; height: 200px; background-position: center; background-size: cover; background-repeat: no-repeat; background-image: url(\'data:image/jpeg;base64,' + data.img + '\');"></div>' +
                '</a>';
        })
        .catch((error) => console.error(error));
    }
    let projetosRestantes = <?= $projetos_qtd ?? 0 ?> - 20;
    let offset = 20;
    function carregarMaisProjetos() {
        if (projetosRestantes <= 0) return;
        fetch('pesquisa_de_projetos.php?offset=' + offset)
        .then((response) => response.json())
        .then((data) => {
            const divFeedProjetos = document.getElementById("feed-projetos");
            const novosProjetos = document.createDocumentFragment();
            const ids = data.ids;
            for (let i = 0; i < ids.length; ++i) {
                const divProjeto = document.createElement("div");
                divProjeto.id = "info-projeto-" + ids[i];
                divProjeto.style="background-color: lightgray; width: calc(50% - 16px); height: fit-content;";
                const script = document.createElement("script");
                script.innerText = "carregarInfoProjeto(" + ids[i] + ");";
                divProjeto.appendChild(script);
                novosProjetos.appendChild(divProjeto);
            }
            divFeedProjetos.appendChild(novosProjetos);
            projetosRestantes -= 20;
            offset += 20;
            const botaoCarregarMais = document.getElementById('carregar-mais');
            if (projetosRestantes <= 0) {
                botaoCarregarMais.remove();
            } else { 
                divFeedProjetos.appendChild(botaoCarregarMais);
            }
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
        <button type="submit" class="btn-novo">Buscar</button>
        <?php if ($busca !== ''): ?>
            <a href="pesquisa_de_projetos.php" class="btn-novo btn-secundario">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($erro)): ?>
        <p style="color: red; padding: 0 20px;"><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    <main id="feed-projetos" style="display:flex; flex-wrap: wrap;">

    <?php if(!empty($projetos_id)): ?>
        <?php foreach ($projetos_id as $p): ?>
            <div id="info-projeto-<?= $p["id"] ?>" style="background-color: lightgray; width: calc(50% - 16px); height: fit-content;">
                <script>carregarInfoProjeto(<?= $p["id"] ?>);</script>
            </div>
        <?php endforeach; ?>
        <?php if (isset($projetos_qtd) && $projetos_qtd > 20): ?>
            <button id="carregar-mais" class="btn-novo" onclick="carregarMaisProjetos()">Ver mais</button>
        <?php endif; ?>

    <?php else:?>
        <p>Nenhum projeto</p>
    <?php endif;?>
    </main>

    </div>

</div>

</body>
</html>