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

// Função auxiliar para gerar código alfanumérico único
function gerarCodigoCurto($tamanho = 6) {
    $caracteres = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    return substr(str_shuffle(str_repeat($caracteres, $tamanho)), 0, $tamanho);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id_professor = intval($_POST['id_professor'] ?? 0);

    // Ação: Gerar novo código de acesso para Professor (sem turma vinculada)
    if ($acao === 'gerar_codigo_professor') {
        try {
            // Garante a unicidade do código no banco de dados
            do {
                $novo_codigo = gerarCodigoCurto(6);
                $stmt_c = $pdo->prepare('SELECT codigo FROM codigo_instituicao WHERE codigo = ?');
                $stmt_c->execute([$novo_codigo]);
            } while ($stmt_c->fetch());

            // Insere o código para tipo_usuario = 2 (PROFESSOR) e id_turma = NULL
            $stmt_ins = $pdo->prepare(
                'INSERT INTO codigo_instituicao (id_instituicao, codigo, tipo_usuario, id_turma) 
                 VALUES (?, ?, 2, NULL)'
            );
            $stmt_ins->execute([$id_instituicao, $novo_codigo]);

            $mensagem_sucesso = "Código para professor gerado com sucesso: <code>" . htmlspecialchars($novo_codigo) . "</code>";
        } catch (Exception $e) {
            $mensagem_erro = 'Erro ao gerar o código para professor.';
        }
    }

    elseif ($id_professor > 0) {
        // Valida se o professor realmente pertence a esta instituição
        $stmt_check = $pdo->prepare(
           'SELECT u.id 
            FROM usuarios u 
            INNER JOIN extra_usuarios e ON e.id_usuario = u.id 
            WHERE u.id = ? AND u.tipo = 2 AND e.id_instituicao = ?'
        );
        $stmt_check->execute([$id_professor, $id_instituicao]);

        if ($stmt_check->fetch()) {
            
            // Rebaixar para Aluno
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
                    $stmt_e = $pdo->prepare('DELETE FROM extra_usuarios WHERE id_usuario = ?');
                    $stmt_e->execute([$id_professor]);

                    $stmt_u = $pdo->prepare('DELETE FROM usuarios WHERE id = ? AND tipo = 2');
                    $stmt_u->execute([$id_professor]);

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
$professores = $stmt_profs->fetchAll();

// Consulta códigos ativos criados para professores desta instituição
$stmt_codigos = $pdo->prepare(
    'SELECT codigo, data_criacao 
     FROM codigo_instituicao 
     WHERE id_instituicao = ? AND tipo_usuario = 2 AND id_turma IS NULL 
     ORDER BY data_criacao DESC'
);
$stmt_codigos->execute([$id_instituicao]);
$codigos_professores = $stmt_codigos->fetchAll();

//////////////////////////////////
$title = 'Gerenciamento de Professores';
$href = 'dashboard.php';
include 'header.php';
?>
    <div style="height: 200px"></div>

    <h1>Gerenciamento de Professores</h1>
    <p>Instituição: <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></strong></p>
    <p><a href="dashboard.php" class="btn-novo">Voltar ao Painel</a></p>

    <hr>

    <?php if (!empty($mensagem_sucesso)): ?>
        <p><strong>SUCESSO:</strong> <?= $mensagem_sucesso ?></p>
        <hr>
    <?php endif; ?>

    <?php if (!empty($mensagem_erro)): ?>
        <p><strong>ERRO:</strong> <?= htmlspecialchars($mensagem_erro) ?></p>
        <hr>
    <?php endif; ?>

    <h2>Professores cadastrados</h2>

    <!-- Botão para gerar código de professor e listagem dos códigos ativos -->
    <div style="margin-bottom: 20px;">
        Código: <?= 1; ?>
        <form action="" method="POST" style="display: inline-block;">
            <input type="hidden" name="acao" value="gerar_codigo_professor">
            <button type="submit" class="btn-novo">+ Gerar Código para Professor</button>
        </form>
    </div>

    <?php if (count($codigos_professores) > 0): ?>
        <details style="margin-bottom: 20px;">
            <summary><strong>Códigos de Cadastro de Professor Ativos (<?= count($codigos_professores) ?>)</strong></summary>
            <br>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Data de Criacao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codigos_professores as $cp): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($cp['codigo']) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($cp['data_criacao'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
    <?php endif; ?>

    <?php if (count($professores) > 0): ?>
        <p>
            <label for="campo-pesquisa"><strong>Pesquisar Professor:</strong></label>
            <input type="text" id="campo-pesquisa" onkeyup="filtrarProfessores()" placeholder="Digite o nome ou e-mail...">
        </p>
        <table border="1" cellpadding="5" cellspacing="0" id="tabela-professores">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Ações</th>
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
                                <button type="submit" onclick="return confirm('Tem certeza que deseja transformar este professor em aluno?');" class="btn-novo">
                                    Alterar para aluno
                                </button>
                            </form>

                            <!-- Excluir conta -->
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="excluir_conta">
                                <input type="hidden" name="id_professor" value="<?= $p['id'] ?>">
                                <button type="submit" onclick="return confirm('Tem certeza que deseja EXCLUIR permanentemente esta conta?');" class="btn-novo">
                                    Excluir conta
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>
            function filtrarProfessores() {
                const termo = document.getElementById('campo-pesquisa').value.toLowerCase();
                const linhas = document.querySelectorAll('#tabela-professores tbody tr');

                linhas.forEach(linha => {
                    const textoLinha = linha.textContent.toLowerCase();
                    linha.style.display = textoLinha.includes(termo) ? '' : 'none';
                });
            }
        </script>
    <?php else: ?>
        <p>Nenhum professor encontrado para esta instituição.</p>
    <?php endif; ?>

</body>
</html>