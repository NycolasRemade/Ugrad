<?php
/**
 * privacidade.php — Política de Privacidade (Lei nº 13.709/2018, LGPD).
 * Página pública (não exige login), aberta pelo link da tela de login.
 * Texto de apoio do projeto: deve ser revisado pelo responsável pela plataforma.
 */

// Contato do encarregado pelo tratamento de dados (art. 41). Preencha, por exemplo:
// $contatoEncarregado = 'privacidade@seudominio.com';
$contatoEncarregado = '';

$title = 'Privacidade - Ugrad';
$conta = false;
include 'header.php';
?>

<body>

    <div id="back">
        <a href="login.php" aria-label="Voltar para o login">
            <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72" fill="none" aria-hidden="true">
                <rect x="72" y="72" width="72" height="72" rx="35.5" transform="rotate(-180 72 72)" fill="#111111" />
                <path d="M34.0322 26.805L35.9522 28.725L32.5122 32.165L30.5122 33.845L30.5522 33.965L34.3922 33.725H47.8322V36.445H34.3922L30.5522 36.205L30.5122 36.325L32.5122 38.005L35.9522 41.445L34.0322 43.365L25.7522 35.085L34.0322 26.805Z" fill="white" />
            </svg>
        </a>
    </div>

    <main style="max-width: 820px; margin: 0 auto; padding: 96px 24px 64px; background: #fff; color: #111; line-height: 1.6; font-size: 16px;">

        <h1 style="margin: 0 0 8px;">Política de Privacidade</h1>
        <p style="margin: 0 0 24px; font-size: 14px; opacity: .8;">
            Lei nº 13.709/2018 — Lei Geral de Proteção de Dados Pessoais (LGPD). Texto de apoio do projeto,
            a ser revisado pelo responsável pela plataforma.
        </p>

        <h2>Quem trata os seus dados</h2>
        <p>A plataforma Ugrad é a responsável (controladora) pelo tratamento dos dados pessoais descritos abaixo.</p>

        <h2>Quais dados tratamos</h2>
        <ul>
            <li>Nome, e-mail e descrição do perfil.</li>
            <li>Senha (guardada de forma protegida, nunca em texto legível).</li>
            <li>Foto de perfil, turma e instituição.</li>
            <li>Participação em projetos, comentários e denúncias.</li>
        </ul>

        <h2>Para que usamos</h2>
        <ul>
            <li>Criar e manter a sua conta e permitir o seu acesso.</li>
            <li>Organizar turmas, instituições e projetos.</li>
            <li>Analisar denúncias e manter a plataforma segura.</li>
        </ul>
        <p>Usamos apenas os dados necessários para essas finalidades.</p>

        <h2>Quem pode ver os seus dados</h2>
        <p>
            Administradores da plataforma, para gerenciar contas e moderar conteúdo, e, quando for o caso,
            a instituição à qual você pertence, para a gestão das turmas. Todas as ações dos administradores
            ficam registradas.
        </p>

        <h2>Seus direitos (art. 18)</h2>
        <p>Você pode pedir, a qualquer momento:</p>
        <ul>
            <li>confirmação de que tratamos os seus dados e acesso a eles;</li>
            <li>correção de dados incompletos, inexatos ou desatualizados;</li>
            <li>bloqueio, anonimização ou eliminação de dados desnecessários;</li>
            <li>portabilidade dos seus dados (você recebe um arquivo legível);</li>
            <li>eliminação da sua conta e dos dados ligados a ela;</li>
            <li>informação sobre com quem os dados são compartilhados;</li>
            <li>revogação do consentimento, quando ele for a base do tratamento.</li>
        </ul>

        <h2>Por quanto tempo guardamos</h2>
        <p>Enquanto a sua conta existir. Ao excluir a conta, os dados ligados a ela também são removidos.</p>

        <h2>Crianças e adolescentes</h2>
        <p>
            O tratamento de dados de menores de 18 anos é feito no melhor interesse deles (art. 14).
            Se você é menor de idade, peça ajuda ao seu responsável ou à sua instituição para usar a plataforma.
        </p>

        <h2>Como falar com a gente</h2>
        <p>
            Encarregado pelo tratamento de dados (art. 41):
            <?= $contatoEncarregado !== '' ? htmlspecialchars($contatoEncarregado) : 'contato a ser definido pela equipe da plataforma.' ?>
        </p>

    </main>

</body>

</html>
