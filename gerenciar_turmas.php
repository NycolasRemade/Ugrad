<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['usuario_tipo'];
$id_instituicao = $_SESSION['usuario_id_instituicao'];
if ($tipo_usuario != 2 && $tipo_usuario != 4) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Criar nova turma
    if ($acao === 'criar_turma') {
        $nome_turma = trim($_POST['nome_turma'] ?? '');

        if (!empty($nome_turma)) {
            if (mb_strlen($nome_turma) <= 25) {
                $stmt = $pdo->prepare('INSERT INTO turmas (nome, id_instituicao) VALUES (?, ?)');
                $stmt->execute([$nome_turma, $id_instituicao]);
                $mensagem_sucesso = 'Turma criada com sucesso!';
            } else {
                $mensagem_erro = 'O nome da turma deve ter no máximo 25 caracteres.';
            }
        } else {
            $mensagem_erro = 'Informe o nome da turma.';
        }
    }

    // Editar nome da turma
    elseif ($acao === 'editar_turma') {
        $id_turma = intval($_POST['id_turma'] ?? 0);
        $novo_nome = trim($_POST['novo_nome'] ?? '');

        if ($id_turma > 0 && !empty($novo_nome)) {
            if (mb_strlen($novo_nome) <= 25) {
                $stmt = $pdo->prepare('UPDATE turmas SET nome = ? WHERE id = ? AND id_instituicao = ?');
                $stmt->execute([$novo_nome, $id_turma, $id_instituicao]);
                
                if ($stmt->rowCount() > 0) {
                    $mensagem_sucesso = 'Nome da turma alterado com sucesso!';
                } else {
                    $mensagem_erro = 'Nenhuma alteração realizada ou turma não encontrada.';
                }
            } else {
                $mensagem_erro = 'O nome da turma deve ter no máximo 25 caracteres.';
            }
        } else {
            $mensagem_erro = 'Preencha o novo nome da turma.';
        }
    }

    // Adicionar aluno à turma
    elseif ($acao === 'adicionar_aluno') {
        $id_aluno = intval($_POST['id_aluno'] ?? 0);
        $id_turma = intval($_POST['id_turma'] ?? 0);

        if ($id_aluno > 0 && $id_turma > 0) {
            // Verifica se a turma existe e pertence à instituição
            $stmt_t = $pdo->prepare('SELECT id FROM turmas WHERE id = ? AND id_instituicao = ?');
            $stmt_t->execute([$id_turma, $id_instituicao]);

            // Verifica se o usuário selecionado é um ALUNO (tipo = 1)
            $stmt_a = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? AND tipo = 1');
            $stmt_a->execute([$id_aluno]);

            if ($stmt_t->fetch() && $stmt_a->fetch()) {
                $stmt_up = $pdo->prepare(
                   'UPDATE extra_usuarios
                    SET id_turma = ?, id_instituicao = ?
                    WHERE id_usuario = ?'
                );
                $stmt_up->execute([$id_turma, $id_instituicao, $id_aluno]);
                $mensagem_sucesso = 'Aluno adicionado à turma com sucesso!';
            } else {
                $mensagem_erro = 'Aluno ou Turma inválidos.';
            }
        } else {
            $mensagem_erro = 'Selecione um aluno e uma turma.';
        }
    }

    // Ação: Remover Aluno da Turma
    elseif ($acao === 'remover_aluno') {
        $id_aluno = intval($_POST['id_aluno'] ?? 0);

        if ($id_aluno > 0) {
            $stmt_rem = $pdo->prepare('UPDATE extra_usuarios SET id_turma = NULL WHERE id_usuario = ? AND id_instituicao = ?');
            $stmt_rem->execute([$id_aluno, $id_instituicao]);
            $mensagem_sucesso = 'Aluno removido da turma com sucesso!';
        }
    }
}

// 6. Consulta Turmas da Instituição
$stmt_turmas = $pdo->prepare('SELECT id, nome FROM turmas WHERE id_instituicao = ? ORDER BY nome ASC');
$stmt_turmas->execute([$id_instituicao]);
$turmas = $stmt_turmas->fetchAll();

// 7. Consulta Alunos da Instituição ou disponíveis no sistema
$stmt_alunos = $pdo->prepare('
    SELECT u.id, u.nome, u.email, e.id_turma, t.nome AS nome_turma
    FROM usuarios u
    LEFT JOIN extra_usuarios e ON e.id_usuario = u.id
    LEFT JOIN turmas t ON t.id = e.id_turma
    WHERE u.tipo = 1 AND (e.id_instituicao = ? OR e.id_instituicao IS NULL)
    ORDER BY u.nome ASC
');
$stmt_alunos->execute([$id_instituicao]);
$alunos = $stmt_alunos->fetchAll();

$alunos_por_turma = [];
foreach ($alunos as $a) {
    if (!empty($a['id_turma'])) {
        $alunos_por_turma[$a['id_turma']][] = $a;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Turmas - Ugrad</title>
</head>
<body>

    <h1>Gerenciamento de Turmas</h1>
    <p><strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>  (<?= $_SESSION['usuario_tipo'] == 4 ? 'Instituição' : 'Professor' ?>)</p>
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

    <h2>Turmas cadastradas</h2>

    <p>
        <button type="button" onclick="toggleFormCriarTurma()">+ Nova Turma</button>
    </p>

    <div id="form_criar_turma" style="display: none;">
        <form action="" method="POST">
            <input type="hidden" name="acao" value="criar_turma">
            
            <label for="nome_turma">Nova turma (máx 25 caracteres):</label><br>
            <input type="text" id="nome_turma" name="nome_turma" maxlength="25" required>
            
            <button type="submit">Criar turma</button>
        </form>
        <br>
    </div>

    <?php if (count($turmas) > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Turmas e alunos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turmas as $t): 
                    $alunos_da_turma = $alunos_por_turma[$t['id']] ?? [];
                ?>
                    <tr>
                        <td>
                            <!-- Editar nome da turma -->
                            <div id="div_nome_turma_<?= $t['id'] ?>" style="display: block;">
                                <?= htmlspecialchars($t['nome']) ?>
                                <button type="button" onclick="toggleEditarTurma(<?= $t['id'] ?>)">Alterar nome</button>
                                <button type="button" onclick="toggleAddAlunoTurma(<?= $t['id'] ?>)">+ Adicionar Aluno</button>
                            </div>
                            <div id="form_editar_turma_<?= $t['id'] ?>" style="display: none;">
                                <form action="" method="POST">
                                    <input type="hidden" name="acao" value="editar_turma">
                                    <input type="hidden" name="id_turma" value="<?= $t['id'] ?>">
                                    <input oninput="inputChangeEditarTurma(<?= $t['id'] ?>)" type="text" name="novo_nome" value="<?= htmlspecialchars($t['nome']) ?>" maxlength="25" required>
                                    <button id="botao_form_turma_<?= $t['id'] ?>" type="button" onclick="toggleEditarTurma(<?= $t['id'] ?>)">Cancelar</button>
                                </form>
                            </div>

                            <!-- Adicionar alunos à turma -->
                            <div id="form_add_aluno_turma_<?= $t['id'] ?>" style="display: none;">
                                <br>
                                <form action="" method="POST">
                                    <input type="hidden" name="acao" value="adicionar_aluno">
                                    <input type="hidden" name="id_turma" value="<?= $t['id'] ?>">
                                    
                                    <label for="select_aluno_<?= $t['id'] ?>">Adicionar aluno:</label>
                                    <select id="select_aluno_<?= $t['id'] ?>" name="id_aluno" required>
                                        <option value="">-- Selecione o Aluno --</option>
                                        <?php 
                                        foreach ($alunos as $a): 
                                            if ($a['id_turma'] != $t['id']): 
                                        ?>
                                            <option value="<?= $a['id'] ?>">
                                                <?= htmlspecialchars($a['nome']) ?>
                                                <?= $a['nome_turma'] ? '- Turma Atual: ' . htmlspecialchars($a['nome_turma']) : '- (Sem turma)' ?>
                                            </option>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </select>
                                    
                                    <button type="submit">Adicionar</button>
                                    <button type="button" onclick="toggleAddAlunoTurma(<?= $t['id'] ?>)">Cancelar</button>
                                </form>
                            </div>

                            <!-- Lista de alunos na turma -->
                             <details>
                                <summary>Alunos na turma (<?= count($alunos_da_turma) ?>)</summary>
                                <br>
                                <?php if (count($alunos_da_turma) > 0): ?>
                                    <table border="1" cellpadding="3" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>E-mail</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alunos_da_turma as $al): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($al['nome']) ?></td>
                                                    <td><?= htmlspecialchars($al['email']) ?></td>
                                                    <td>
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="acao" value="remover_aluno">
                                                            <input type="hidden" name="id_aluno" value="<?= $al['id'] ?>">
                                                            <button type="submit">Remover da Turma</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p><em>Nenhum aluno nesta turma ainda.</em></p>
                                <?php endif; ?>
                            </details>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma turma cadastrada até o momento.</p>
    <?php endif; ?>

    <script>
        function toggleFormCriarTurma() {
            const div_form = document.getElementById('form_criar_turma');
            if (div_form.style.display === 'none') {
                div_form.style.display = 'block';
            } else {
                div_form.style.display = 'none';
            }
        }
        function toggleEditarTurma(id) {
            const div_nome = document.getElementById('div_nome_turma_' + id);
            const div_form = document.getElementById('form_editar_turma_' + id);
            if (div_form.style.display === 'none') {
                div_nome.style.display = 'none';
                div_form.style.display = 'block';
            } else {
                div_nome.style.display = 'block';
                div_form.style.display = 'none';
            }
        }
        function inputChangeEditarTurma(id) {
            const botao_form = document.getElementById('botao_form_turma_' + id);
            botao_form.type = "submit";
            botao_form.innerText = "Salvar";
        }
        function toggleAddAlunoTurma(id) {
            const div_form = document.getElementById('form_add_aluno_turma_' + id);
            if (div_form.style.display === 'none') {
                div_form.style.display = 'block';
            } else {
                div_form.style.display = 'none';
            }
        }
    </script>

    <hr>

    <h2>Adicionar aluno à turma</h2>
    <?php if (count($turmas) > 0 && count($alunos) > 0): ?>
        <form action="" method="POST">
            <input type="hidden" name="acao" value="adicionar_aluno">

            <label for="id_aluno">Selecione o Aluno:</label><br>
            <select id="id_aluno" name="id_aluno" required>
                <option value="">-- Selecione um Aluno --</option>
                <?php foreach ($alunos as $a): ?>
                    <option value="<?= $a['id'] ?>">
                        <?= htmlspecialchars($a['nome']) ?> (<?= htmlspecialchars($a['email']) ?>) 
                        <?= $a['nome_turma'] ? '- Turma Atual: ' . htmlspecialchars($a['nome_turma']) : '- (Sem turma)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="id_turma_add">Selecione a Turma de Destino:</label><br>
            <select id="id_turma_add" name="id_turma" required>
                <option value="">-- Selecione uma Turma --</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <button type="submit">Adicionar Aluno à Turma</button>
        </form>
    <?php else: ?>
        <p>É necessário ter pelo menos uma turma criada e alunos cadastrados para realizar esta ação.</p>
    <?php endif; ?>

    <hr>

    <h2>Relação de Alunos</h2>
    <?php if (count($alunos) > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>ID Aluno</th>
                    <th>Nome do Aluno</th>
                    <th>E-mail</th>
                    <th>Turma Atual</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $a): ?>
                    <tr>
                        <td><?= $a['id'] ?></td>
                        <td><?= htmlspecialchars($a['nome']) ?></td>
                        <td><?= htmlspecialchars($a['email']) ?></td>
                        <td><?= $a['nome_turma'] ? htmlspecialchars($a['nome_turma']) : '<em>Sem Turma</em>' ?></td>
                        <td>
                            <?php if (!empty($a['id_turma'])): ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="acao" value="remover_aluno">
                                    <input type="hidden" name="id_aluno" value="<?= $a['id'] ?>">
                                    <button type="submit">Remover da Turma</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhum aluno encontrado.</p>
    <?php endif; ?>

</body>
</html>