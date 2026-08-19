<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_projeto'])) {
    $nome_projeto = trim($_POST['nome_projeto']);
    $membros = isset($_POST['membros']) ? array_filter($_POST['membros']) : [];
    $categorias = isset($_POST['categorias']) ? array_filter($_POST['categorias']) : [];

    if (!empty($nome_projeto)) {
        try {
            $pdo->beginTransaction();

            $stmt_proj = $pdo->prepare('INSERT INTO projetos (nome, estado) VALUES (?, 1)');
            $stmt_proj->execute([$nome_projeto]);
            $id_projeto = $pdo->lastInsertId();

            $stmt_dono = $pdo->prepare('INSERT INTO proj_membros (id_convidante, id_convidado, id_projeto, status_membro) VALUES (?, ?, ?, 1)');
            $stmt_dono->execute([$usuario_id, $usuario_id, $id_projeto]);

            if (!empty($membros)) {
                $stmt_membro = $pdo->prepare('INSERT INTO proj_membros (id_convidante, id_convidado, id_projeto, status_membro) VALUES (?, ?, ?, 3)');
                foreach ($membros as $id_convidado) {
                    if ($id_convidado != $usuario_id) {
                        $stmt_membro->execute([$usuario_id, $id_convidado, $id_projeto]);
                    }
                }
            }

            if (!empty($categorias)) {
                $stmt_categorias = $pdo->prepare('INSERT INTO proj_categorias (id_projeto, id_categoria) VALUES (?, ?)');
                foreach ($categorias as $id_categoria) {
                    $stmt_categorias->execute([$id_projeto, $id_categoria]);
                }
            }

            $pdo->commit();
            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = 'Erro ao criar projeto: ' . $e->getMessage();
        }
    } else {
        $erro = 'Preencha o nome do projeto.';
    }
}

$usuario_id_instituicao = $_SESSION['usuario_id_instituicao'];
$usuarios_query = $pdo->query(
   "SELECT u.id, u.nome, u.email 
    FROM usuarios u INNER JOIN extra_usuarios e
    ON u.id = e.id_usuario
    WHERE e.id_instituicao = $usuario_id_instituicao AND u.id != $usuario_id"
);
$lista_usuarios = $usuarios_query->fetchAll();


$categorias_query = $pdo->query('SELECT id, nome FROM categorias');
$lista_categorias = $categorias_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Novo projeto</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body  onLoad="window.scroll(0, 0)" style="overflow-y: hidden; overflow-x: hidden">

    <div id="navbar">
        <img src="Fotos/Polygon 2.png" alt="navbar">
        <a href="dashboard.php">
            <h1 class="meringue">Ugrad</h1>
        </a>
    </div>

    <div id="criacao_projetos">

    <h1>Novo projeto</h1>

    <form method="POST" action="">

        <div>
            <label for="nome_projeto">Nome do projeto</label><br>
            <input type="text" id="nome_projeto" name="nome_projeto" required>
        </div>
        <br>
        <div>
            <label>Integrantes do grupo</label><br>

            <div class="multiple_inline">
            <div id="membros-selecionados"></div>

            <div id="membros selector" class='selector' style="display: none;">
                <select id="select membro" onchange="confirmarSelecao('membros')">
                    <option value="">Selecione um aluno da turma...</option>
                    <?php foreach ($lista_usuarios as $u): ?>
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
            <label>Categorias</label><br>

            <div class="multiple_inline">
            <div id="categorias-selecionados"></div>

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
            <button type="button" onclick="window.history.back()">Cancelar</button>
            <button type="submit" name="criar_projeto">Criar</button>
        </div>

    </form>
    </div>

    <script>
        function mostrarSeletor(tipo) {
            const selectorDiv = document.getElementById(tipo + ' selector');

            if (selectorDiv.style.display === 'none') {
                selectorDiv.style.display = 'block';
            }
            else{
                selectorDiv.style.display = 'none';
            }
        }
        function confirmarSelecao(tipo) {
            const select = document.getElementById((tipo === 'membros') ? 'select membro' : 'select categoria');
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
                <button type="button" onclick="removerItem('${tipo}-item-${value}')">x</button>
            `;

            container.appendChild(itemDiv);

            select.selectedIndex = 0;
            document.getElementById(tipo + ' selector').style.display = 'none';
        }
        function removerItem(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.remove();
            }
        }
    </script>

</body>
</html>