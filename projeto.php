<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_projeto = $_GET['id'];

$stmt = null;
$projeto = null;
$pode_editar_projeto = false;
// dados do projeto
if ($_SESSION['usuario_tipo'] === 1) {
    // ALUNO

    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        JOIN proj_membros pm ON p.id = pm.id_projeto
        WHERE p.id = ? AND (pm.id_convidado = ? OR p.estado = 3)'
    );
    // se o usuário for convidado
    // ou se o projeto for público
    $stmt->execute([$id_projeto, $_SESSION['usuario_id']]);
    $projeto = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT p.id FROM projetos p 
                           JOIN proj_membros pm ON p.id = pm.id_projeto 
                           WHERE pm.id_convidado = ?');
    $pode_editar_projeto = ($stmt->fetch()) ? true : false;

} elseif ($_SESSION['usuario_tipo'] === 2 || $_SESSION['usuario_tipo'] === 4) {
    // PROFESSOR ou INSTITUICAO

    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        INNER JOIN proj_membros pm ON p.id = pm.id_projeto
        LEFT JOIN extra_usuarios e ON e.id_usuario = pm.id_convidado
        WHERE p.id = ? AND ((pm.id_convidado = pm.id_convidante AND e.id_instituicao = ?) OR p.estado = 3)'
    );
    // se o membro principal (que é convidado e convidante) 
    // for da mesma instituição do usuário
    // ou se o projeto for público
    $stmt->execute([$id_projeto, $_SESSION['usuario_id_instituicao']]);
    $projeto = $stmt->fetch();

} elseif ($_SESSION['usuario_tipo'] === 3) {
    // EMPRESARIO

    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        WHERE p.id = ? AND p.estado = 3'
    );
    // se o projeto for público
    $stmt->execute([$id_projeto, $_SESSION['usuario_id_instituicao']]);
    $projeto = $stmt->fetch();

} elseif ($_SESSION['usuario_tipo'] === 5) {
    // ADMINISTRADOR

    $stmt = $pdo->prepare(
       'SELECT p.id, p.nome, p.img, pd.descricao, pd.historia 
        FROM projetos p 
        LEFT JOIN proj_dados pd ON p.id = pd.id_projeto 
        WHERE p.id = ?'
    );
    // qualquer projeto
    $stmt->execute([$id_projeto]);
    $projeto = $stmt->fetch();
    $pode_editar_projeto = true;

}

if (empty($projeto)) {
    header('Location: dashboard.php');
    exit;
}
$nome_projeto = $projeto['nome'];

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
        try {
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
        } catch (PDOException) {
            $mensagem_erro = 'Erro ao salvar comentário';
        }
        header("Location: projeto.php?id=$id_projeto");
        exit;
    }

    // Fallback sem JS: navegação normal com mensagem via sessão.
    flash_set($result['message']);
    header('Location: ' . ($result['removed'] ? 'pesquisa.php?tab=projetos' : ('projeto.php?id=' . $id)));
    exit;
}

$categorias = categorias_do_projeto($id);
$membros = membros_do_projeto($id);
$reportagens = reportagens_do_projeto($id);

if ($isPartial) {
    // Fragmento puro (sem <html>/header/footer) para ser injetado via fetch()
    require __DIR__ . '/includes/projeto_detail.php';
    exit;
}

$pageTitle = 'Painel de controle - Detalhes do projeto';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/projeto_detail.php';
require __DIR__ . '/includes/footer.php';
