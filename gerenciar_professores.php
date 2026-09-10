<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['usuario_tipo'];
// Apenas instituições (tipo 4) podem acessar esta página
if ($tipo_usuario != 4) {
    header('Location: dashboard.php');
    exit;
}

$id_instituicao = $id_usuario;
$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_professor = intval($_POST['id_professor'] ?? 0);

    if ($id_professor > 0) {
        // Valida se o professor realmente pertence a esta instituição
        $stmt_check = $pdo->prepare(
           'SELECT u.id 
            FROM usuarios u 
            INNER JOIN extra_usuarios e ON e.id_usuario = u.id 
            WHERE u.id = ? AND u.tipo = 2 AND e.id_instituicao = ?');
        $stmt_check->execute([$id_professor, $id_instituicao]);

        if ($stmt_check->fetch()) {
            
            // Rebaixar para Aluno (Caso seja um aluno fazendo baguncinha)
            if ($acao === 'rebaixar_para_aluno') {
                $pdo->beginTransaction();
                try {
                    // Altera o tipo na tabela usuarios para 1 (ALUNO)
                    $stmt_u = $pdo->prepare('UPDATE usuarios SET tipo = 1 WHERE id = ?');
                    $stmt_u->execute([$id_professor]);

                    // Desvincula a turma do usuário na tabela extra_usuarios
                    $stmt_e = $pdo->prepare('UPDATE extra_usuarios SET id_turma = NULL WHERE id_usuario = ?');
                    $stmt_e->execute([$id_professor]);

                    $pdo->commit();
                    $mensagem_sucesso = 'Conta rebaixada para aluno com sucesso!';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $mensagem_erro = 'Erro ao alterar a conta do professor.';
                }
            } 
            
            // Excluir a conta completamente
            elseif ($acao === 'excluir_conta') {
                $pdo->beginTransaction();
                try {
                    // Remove da tabela extra_usuarios
                    $stmt_e = $pdo->prepare('DELETE FROM extra_usuarios WHERE id_usuario = ?; DELETE FROM usuarios WHERE id = ? AND tipo = 2');
                    $stmt_e->execute([$id_professor, $id_professor]);

                    $pdo->commit();
                    $mensagem_sucesso = 'Conta do professor excluída com sucesso!';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $mensagem_erro = 'Erro ao excluir a conta do professor.';
                }
            }
        } else {
            $mensagem_erro = 'Professor não encontrado ou desvinculado.';
        }
    }
}

// Consulta todos os professores (tipo 2) vinculados à instituição
$stmt_profs = $pdo->prepare(
   'SELECT u.id, u.nome, u.email
    FROM usuarios u
    INNER JOIN extra_usuarios e ON e.id_usuario = u.id
    WHERE u.tipo = 2 AND e.id_instituicao = ?
    ORDER BY u.nome ASC'
);
$stmt_profs->execute([$id_instituicao]);
$professores = $stmt_profs->fetchAll(PDO::FETCH_ASSOC);

//////////////////////////////////
$title = 'Gerenciamento de Professores';
$href = 'dashboard.php';
include 'header.php'
?>


    <h1>Gerenciamento de Professores</h1>
    <p>Instituição: <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></strong></p>
    <p><a href="dashboard.php">Voltar ao Painel</a></p>

    <hr>

    <?php if (!empty($mensagem_sucesso)): ?>
        <p><strong>SUCESSO:</strong> <?= htmlspecialchars($mensagem_sucesso) ?></p>
        <hr>
    <?php endif; ?>

    <?php if (!empty($mensagem_erro)): ?>
        <p><strong>ERRO:</strong> <?= htmlspecialchars($mensagem_erro) ?></p>
        <hr>
    <?php endif; ?>

    <h2>Professores cadastrados</h2>

    <?php if (count($professores) > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($professores as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nome']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td>
                            <!-- Alterar tipo para aluno -->
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="rebaixar_para_aluno">
                                <input type="hidden" name="id_professor" value="<?= $p['id'] ?>">
                                <button type="submit" onclick="return confirm('Tem certeza que deseja transformar este professor em aluno?');">
                                    Alterar para aluno
                                </button>
                            </form>

                            <!-- Excluir conta -->
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="excluir_conta">
                                <input type="hidden" name="id_professor" value="<?= $p['id'] ?>">
                                <button type="submit" onclick="return confirm('Tem certeza que deseja EXCLUIR permanentemente esta conta?');">
                                    Excluir conta
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhum professor encontrado para esta instituição.</p>
    <?php endif; ?>

</body>
</html>