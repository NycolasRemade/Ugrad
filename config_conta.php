<?php
session_start();
require_once 'Servidor/config.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$mensagem = '';
$erro = '';

$max_allowed_packet = $pdo->query('SELECT @@global.max_allowed_packet')->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    // imagem de perfil
    if ($_POST['acao'] === 'alterar_imagem') {
        if (isset($_FILES['imagem_perfil'])) {

            $imagem = $_FILES['imagem_perfil'];
            $imgInfo = getimagesize($imagem['tmp_name']);

            if ($imagem['error'] !== UPLOAD_ERR_OK || !$imgInfo) {
                $erro = 'Erro no upload.';
            } else {
                $imagem_blob = file_get_contents($imagem['tmp_name']);

                if (!is_dir(__DIR__ . '/fotos-perfil/')) {
                    mkdir(__DIR__ . '/fotos-perfil/', 0755, true);
                }

                $stmt = $pdo->prepare('SELECT imagem_perfil FROM usuarios WHERE id = ?');
                $stmt->execute([$usuario_id]);
                $nome_imagem_perfil = $stmt->fetch();
                if (!$nome_imagem_perfil) {
                    $nome_imagem_perfil = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(16))) . time();
                }

                $destino = __DIR__ . '/fotos-perfil/' . $nome_imagem_perfil . '.webp';
                $mime = $imgInfo['mime'];
                $imagem_nova = NULL;
                switch ($mime) {
                    case 'image/jpeg':
                        $imagem_nova = imagecreatefromjpeg($imagem['tmp_name']);
                        $sucesso = imagewebp($imagem_nova, $destino, 70);
                        break;
                    case 'image/png':
                        $imagem_nova = imagecreatefrompng($imagem['tmp_name']);
                        imagepalettetotruecolor($imagem_nova);
                        imagealphablending($imagem_nova, false);
                        imagesavealpha($imagem_nova, true);
                        $sucesso = imagewebp($imagem_nova, $destino, 70);
                        break;
                    case 'image/webp':
                        $imagem_nova = imagecreatefromwebp($imagem['tmp_name']);
                        $sucesso = imagewebp($imagem_nova, $destino, 70);
                        break;
                    default:
                        $erro = 'Apenas os formatos .jpeg, .png e .webp são suportados; Selecione uma imagem válida.';
                }
                if (!$sucesso)
                imagedestroy($imagem_nova);

                $stmt = $pdo->prepare('UPDATE usuarios SET imagem_perfil = ? WHERE id = ?');
                $stmt->execute([$nome_imagem_perfil, $usuario_id]);

                $mensagem = 'Imagem de perfil atualizada!';
            }
        } else {
            $erro = 'Selecione uma imagem válida.';
        }
    }

    // nome
    if ($_POST['acao'] === 'alterar_nome') {
        $nome = trim($_POST['nome']);
        if (!empty($nome)) {
            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ? WHERE id = ?');
            $stmt->execute([$nome, $usuario_id]);
            $mensagem = 'Nome alterado com sucesso!';
        }
    }

    // e-mail
    if ($_POST['acao'] === 'alterar_email') {
        $email = trim($_POST['email']);
        if (!empty($email)) {
            try {
                $stmt = $pdo->prepare('UPDATE usuarios SET email = ? WHERE id = ?');
                $stmt->execute([$email, $usuario_id]);
                $mensagem = 'E-mail alterado com sucesso!';
            } catch (PDOException $e) {
                $erro = 'E-mail já está em uso ou é inválido.';
            }
        }
    }

    // senha
    if ($_POST['acao'] === 'alterar_senha') {
        $senha = trim($_POST['senha']);
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
            $stmt->execute([$senha_hash, $usuario_id]);
            $mensagem = 'Senha alterada com sucesso!';
        }
    }

    // descrição
    if ($_POST['acao'] === 'alterar_descricao') {
        $descricao = trim($_POST['descricao']);
        $stmt = $pdo->prepare('UPDATE usuarios SET descricao = ? WHERE id = ?');
        $stmt->execute([$descricao, $usuario_id]);
        $mensagem = 'Descrição alterada com sucesso!';
    }

    // aceitar convite de projeto
    if ($_POST['acao'] === 'aceitar_convite') {
        $id_convite = intval($_POST['id_convite']);
        // Status 2 corresponde a MEMBRO
        $stmt = $pdo->prepare('UPDATE proj_membros SET status_membro = 2 WHERE id = ? AND id_convidado = ?');
        $stmt->execute([$id_convite, $usuario_id]);
        $mensagem = 'Convite aceito!';
    }

    // recusar convite de projeto
    if ($_POST['acao'] === 'recusar_convite') {
        $id_convite = intval($_POST['id_convite']);
        $stmt = $pdo->prepare('DELETE FROM proj_membros WHERE id = ? AND id_convidado = ?');
        $stmt->execute([$id_convite, $usuario_id]);
        $mensagem = 'Convite recusado.';
    }

    // sair da conta
    if ($_POST['acao'] === 'sair_conta') {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // excluir conta
    if ($_POST['acao'] === 'excluir_conta') {
        $stmt = $pdo->prepare('UPDATE usuarios SET ativada = FALSE WHERE id = ?');
        $stmt->execute([$usuario_id]);
        session_destroy();
        $mensagem = 'Sua conta foi desativada.';
        header("Location: login.php");
        exit;
    }
}

$stmt_user = $pdo->prepare(
   'SELECT u.nome, u.email, u.descricao, u.imagem_perfil, t.nome AS tipo_nome 
    FROM usuarios u
    JOIN tipos_usuario t ON u.tipo = t.id
    WHERE u.id = ?'
);
$stmt_user->execute([$usuario_id]);
$usuario = $stmt_user->fetch();

$stmt_convites = $pdo->prepare(
   'SELECT 
        pm.id AS id_convite,
        p.nome AS nome_projeto,
        u_conv.nome AS nome_convidante,
        t_conv.nome AS tipo_convidante
    FROM proj_membros pm
    JOIN projetos p ON pm.id_projeto = p.id
    JOIN usuarios u_conv ON pm.id_convidante = u_conv.id
    JOIN tipos_usuario t_conv ON u_conv.tipo = t_conv.id
    WHERE pm.id_convidado = ? AND pm.status_membro = 3'
);
$stmt_convites->execute([$usuario_id]);
$convites = $stmt_convites->fetchAll();


//////////////////////////////////
$title = 'Perfil e Configurações';
$href = 'dashboard.php';
include 'header.php'
?>
    <div class='config_container'>

    <?php if ($mensagem): ?>
        <p id='message'><strong><?= htmlspecialchars($mensagem) ?></strong></p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p id='erro'><strong><?= htmlspecialchars($erro) ?></strong></p>
    <?php endif; ?>

    <div id='config_container'>

        <div class='img_container'>
            <?php if (!empty($usuario['imagem_perfil'])): ?>
                <div id="kirkle"><div class="config" style="background-image: url(fotos-perfil/<?= $usuario['imagem_perfil'] ?>.webp)"alt="Foto de Perfil"></div></div>
            <?php else: ?>
                <div id="kirkle"><a class='meringue'>U</a></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="alterar_imagem">
                <input type="file" name="imagem_perfil" accept="image/*" required>
                <button type="submit" class='btn-novo'>Definir imagem</button>
            </form>
        </div>

        <br>

        <div id='variables_container'>

        <form method="POST">
            <input type="hidden" name="acao" value="alterar_nome">
            <label for="nome">Nome</label><br>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
            <button type="submit" class='btn-novo'>Alterar</button>
        </form>

        <br>

        <form method="POST">
            <input type="hidden" name="acao" value="alterar_email">
            <label for="email">E-mail</label><br>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
            <button type="submit" class='btn-novo'>Alterar</button>
        </form>

        <br>

        <form method="POST">
            <input type="hidden" name="acao" value="alterar_senha">
            <label for="senha">Senha</label><br>
            <input type="password" id="senha" name="senha" placeholder="Nova senha" required>
            <button type="submit" class='btn-novo'>Alterar</button>
        </form>
            </div>

        <br>
        </div>

        <div id='descr_container'>

        <form method="POST">
            <input type="hidden" name="acao" value="alterar_descricao">
            <label for="descricao">Descrição</label><br>
            <textarea id="descricao" name="descricao" rows="4" cols="50" placeholder="Descrição curta sobre você ou sua instituição"><?= htmlspecialchars($usuario['descricao'] ?? '') ?></textarea>
            <button type="submit" class='btn-novo'>Alterar</button>
        </form>

        <form method="POST">
            <input type="hidden" name="acao" value="sair_conta">
            <button type="submit" class='btn-novo'>Sair da conta</button>
        </form>
            </div>



    <div>
        <h3>Convites de Projetos</h3>

        <?php if (empty($convites)): ?>
            <p>Nenhum convite pendente no momento.</p>
        <?php else: ?>
            <?php foreach ($convites as $convite): ?>
                <div>
                    <p>
                        <strong><?= htmlspecialchars(ucfirst(strtolower($convite['tipo_convidante']))) ?></strong><br>
                        Você foi convidado para participar do projeto <strong><?= htmlspecialchars($convite['nome_projeto']) ?></strong>
                    </p>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="acao" value="aceitar_convite">
                        <input type="hidden" name="id_convite" value="<?= $convite['id_convite'] ?>">
                        <button type="submit" title="Aceitar">✓</button>
                    </form>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="acao" value="recusar_convite">
                        <input type="hidden" name="id_convite" value="<?= $convite['id_convite'] ?>">
                        <button type="submit" title="Recusar">X</button>
                    </form>
                </div>
                <br>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <div>
        <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir sua conta?');">
            <input type="hidden" name="acao" value="excluir_conta">
            <button type="submit" class='btn-novo excluir'>Excluir minha conta</button>
        </form>
        <p>
            A exclusão de conta retira todas as informações relacionadas a ela que podem identificá-la, como links de contato, créditos de projetos e avaliações, porém comentários permanecerão, apenas sem a possibilidade de ver o perfil e seu nome.
        </p>
        <p>
            Ao excluir a conta, ela ainda será recuperável por 7 dias; Após o prazo, não há nada que podemos fazer por você.
        </p>
    </div>
    
    
</body>
</html>