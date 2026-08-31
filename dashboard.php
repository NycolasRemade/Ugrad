<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare('SELECT nome, email, tipo FROM usuarios WHERE id = ?');
$stmt->execute([$id_usuario]);
$dados = $stmt->fetch();


//////////////////////////////////
$title = 'Painel - Ugrad';
$href = 'Ugrad.html';
include 'header.php'
?>

    <main class="dashboard-container">
        <a href="config_conta.php"><div class="welcome-area">
            <img src="Fotos/Polygon 6.png" alt="" id="nome_png">
            <h2>Olá, <?= htmlspecialchars($dados['nome']) ?>!</h2>
        </div></a>

        <?php if ($dados['tipo'] === 1 || $dados['tipo'] === 5): // ALUNO ou ADMINISTRADOR
            $stmt_projetos = $pdo->prepare(
               'SELECT p.id AS id_do_projeto, p.nome AS nome_projeto
                FROM projetos p INNER JOIN proj_membros m 
                ON m.id_projeto = p.id 
                WHERE m.id_convidado = ?'
            );
            $stmt_projetos->execute([$id_usuario]);
            $projetos = $stmt_projetos->fetchAll();
            $stmt_membros = $pdo->prepare(
               'SELECT u.imagem_perfil, u.nome
                FROM usuarios u INNER JOIN proj_membros m
                ON u.id = m.id_convidado
                WHERE m.id_projeto = ?'
            );
        ?>

        <section class="projetos-secao">
            <div class="secao-header">
                <h3>Meus projetos</h3>
                <a class="btn-novo" href="criar_projeto.php">+ Novo projeto</a>
            </div>
            
            <div class="projetos-grid">
            <?php
            foreach ($projetos as $proj): 
                $stmt_membros->execute([$proj['id_do_projeto']]);
                $membros = $stmt_membros->fetchAll();
            ?>
                <a class="projeto-card box">
                    <p class="projeto-titulo"><?= htmlspecialchars($proj['nome_projeto']); ?></p>
                    <div class="projeto-membros">
                        <?php foreach ($membros as $m): ?>
                            <img class="membro-avatar" style="background-image: url(fotos-perfil/<?= $usuario['imagem_perfil'] ?>.webp)" title="<?= htmlspecialchars($m['nome']); ?>">
                        <?php endforeach; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            </div>
        </section>

        <?php elseif ($dados['tipo'] === 4): // INSTITUIÇÂO 
            $stmt = $pdo->prepare('SELECT nome FROM turmas WHERE id_instituicao = ?');
            $stmt->execute([$id_usuario]);
            $turmas = $stmt->fetchAll();

            $stmt = $pdo->prepare(
               'SELECT * 
                FROM usuarios u INNER JOIN extra_usuarios e 
                ON e.id_usuario = u.id 
                WHERE u.tipo = ? AND e.id_instituicao = ?'
            );
            $stmt->execute(['2', $id_usuario]);
            $professores = $stmt->fetchAll();
            $stmt->execute(['1', $id_usuario]);
            $alunos = $stmt->fetchAll();

            $stmt = $pdo->prepare(
               'SELECT p.id, p.nome, p.img, u.imagem_perfil, u.nome AS nome_usuario
                FROM projetos p INNER JOIN proj_membros pm
                ON pm.id_projeto = p.id
                INNER JOIN usuarios u
                ON pm.id_convidado = u.id
                INNER JOIN extra_usuarios eu
                ON eu.id_usuario = u.id
                WHERE eu.id_instituicao = ?'
            );
            $stmt->execute([$id_usuario]);
            $projetos = $stmt->fetchAll();
        ?>

        <details>
            <summary>
                <span>Turmas fixadas</span>
            </summary>
        </details>

        <details open>
            <summary>
                    <span>Turmas</span>
                    <button>+</button>
            </summary>
            <div>
            <?php foreach ($turmas as $t): ?>
                <div><?= htmlspecialchars($t['nome']) ?></div>
            <?php endforeach; ?>
            </div>
        </details>

        <details open>
            <summary>
                    <span>Professores</span>
                    <button>+</button>
            </summary>
            <div>
            <?php foreach ($professores as $prof): ?>
                <div><?= htmlspecialchars($prof['nome']) ?></div>
            <?php endforeach; ?>
            </div>
        </details>

        <details>
            <summary>
                <span>Alunos</span>
            </summary>
            <div>
            <?php foreach ($alunos as $a): ?>
                <div><?= htmlspecialchars($a['nome']) ?></div>
            <?php endforeach; ?>
            </div>
        </details>

        <details open>
            <summary>
                <span>Projetos dos alunos</span>
            </summary>
            <div class="projetos-grid">

            <?php
            $tamanho = count($projetos);
            for ($i = 0; $i < $tamanho; $i++):
                $id_projeto = $projetos[$i]['id'];
            ?>
                <a class="projeto-card box" href="editar_projeto.php?nome=<?= urlencode($projetos[$i]['nome']); ?>">
                    <p class="projeto-titulo"><?= htmlspecialchars($projetos[$i]['nome']); ?></p>

                    <div class="projeto-membros">
                    <?php while ($i < $tamanho && $id_projeto === $projetos[$i]['id']): ?>
                        <img class="membro-avatar" style="background-image: url(fotos-perfil/<?= $projetos[$i]['imagem_perfil'] ?>.webp)" title="<?= htmlspecialchars($projetos[$i]['nome_usuario']) ?>">
                    <?php $i++; endwhile; $i--;?>
                    </div>
                </a>

            <?php endfor; ?>
            </div>
        </details>



        <?php endif; ?>
    </main>

<script>
    function getTextWidth() {

            inputText = "Olá, <?= htmlspecialchars($dados['nome']); ?>!";
            font = "36px IBM";

            canvas = document.createElement("canvas");
            context = canvas.getContext("2d");
            context.font = font;
            width = context.measureText(inputText).width;
            formattedWidth = Math.ceil(width) + 50;

            document.getElementById('nome_png').style.width = formattedWidth + "px";
        } 

        getTextWidth()

</script>

</body>
</html>
