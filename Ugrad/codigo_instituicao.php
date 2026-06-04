<?php
session_start();
require_once 'Servidor/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo_instituicao = trim($_POST['codigo_instituicao'] ?? '');
    if ($codigo_instituicao) {

        try {
            // Checa se o código da instituição existe e não expirou (limite no 'INTERVAL 1 WEEK')
            $stmt = $pdo->prepare(
                'SELECT id_instituicao, tipo_usuario FROM codigo_instituicao
                    WHERE codigo = ?
                   AND CURRENT_DATE() < DATE_ADD(data_criacao, INTERVAL 1 WEEK)'
            );
            $stmt->execute([$codigo_instituicao]);
            $codigo = $stmt->fetch();

            if ($codigo) {
                // guarda o código temporariamente para ser utilizado na página
                // da criação da conta, onde o usuário é criado no banco de dados
                $_SESSION['usuario_id_instituicao'] = $codigo['id_instituicao'];
                $_SESSION['usuario_tipo']           = $codigo['tipo_usuario'];
                header('Location: criar_conta.php');
                exit;
            } else {
                $erro = 'Código inválido, nenhuma instituição foi encontrada com este código.';
            }
        } catch (\PDOException $e) {
            $erro = 'Ocorreu um erro ao processar sua solicitação. Tente novamente.';
        }
    } else {
        $erro = 'Verifique o código e tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
    <title>Seleção de Instituição</title>
</head>

<body onLoad="window.scroll(0, 0)" style="overflow-y: hidden;">

    <div id="logo_2">
        <img src="Fotos/Logo_alt1.png" alt="logo_alt1">
        <div>
            <h1 class="meringue">Ugrad</h1>
        </div>
    </div>


    <div id="back">
        <a href="Ugrad.html">
            <svg xmlns="http://www.w3.org/2000/svg" width="72" height="71" viewBox="0 0 72 71" fill="none">
                <rect x="72" y="71" width="72" height="71" rx="35.5" transform="rotate(-180 72 71)" fill="#111111" />
                <path d="M34.0322 26.805L35.9522 28.725L32.5122 32.165L30.5122 33.845L30.5522 33.965L34.3922 33.725H47.8322V36.445H34.3922L30.5522 36.205L30.5122 36.325L32.5122 38.005L35.9522 41.445L34.0322 43.365L25.7522 35.085L34.0322 26.805Z" fill="white" />
            </svg>
        </a>
    </div>

    <div id="papel_login" style="height: 520px;">

        <?php if (!empty($erro)): ?>
            <div id="erro">
                <?= htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <svg xmlns="http://www.w3.org/2000/svg" width="952" height="520" viewBox="0 0 952 520" fill="none">
            <path d="M392 3.8147e-06L0 234.899V519.399H951.5V0L392 3.8147e-06Z" fill="white" />
        </svg>

        <h1>Cadastro</h1>
        <p name='dci'>
            Digite o Código da instituição
        </p>

        <form action="codigo_instituicao.php" method="POST" id="form_login">
            <div>
                <input type="text" class="input_login" name="codigo_instituicao" required placeholder="Código">
                <button type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="71" viewBox="0 0 72 71" fill="none">
                        <rect width="72" height="71" fill="#111111" />
                        <path d="M37.9678 44.195L36.0478 42.275L39.4878 38.835L41.4878 37.155L41.4478 37.035L37.6078 37.275H24.1678V34.555H37.6078L41.4478 34.795L41.4878 34.675L39.4878 32.995L36.0478 29.555L37.9678 27.635L46.2478 35.915L37.9678 44.195Z" fill="white" />
                    </svg>
                </button>
            </div>
        </form>

        <p name="desc">O código, após ser gerado, é válido apenas por 7 dias.<br><br>

        Caso tenha dúvidas, fale com um responsável da sua instituição</p>
    </div>

    <div id="smallboxes">
        <a href="criar_conta_em.php">
        <div class="small"><p>Quero cadastrar como investidor/empresa</p></div>
        </a>
    </div>

</body>

</html>