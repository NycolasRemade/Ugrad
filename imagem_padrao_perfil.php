<?php 

//Código para pegar o BLOB de uma imagem (para inserir ela como imagem padrão de perfil)
if($_SERVER['REQUEST_METHOD'] === 'POST'){

$imagem = $_FILES['imagem_perfil'];
$imgInfo = getimagesize($imagem['tmp_name']);
$imagem_original = imagecreatefrompng($imagem['tmp_name']);
                    imagepalettetotruecolor($imagem_original);
                    imagealphablending($imagem_original, false);
                    imagesavealpha($imagem_original, true);

ob_start();
                imagewebp($imagem_original, null, 70);
                imagedestroy($imagem_original);
$imagem_nova = ob_get_clean();

echo $imagem_nova;
}
?>
            <form method="POST" enctype="multipart/form-data" id="form-imagem-perfil">
                <input type="hidden" name="acao" value="alterar_imagem">
                <input style="display:none" id="input-imagem-perfil" type="file" name="imagem_perfil" accept="image/jpeg, image/png, image/webp">
                <button id="botao-imagem-perfil" type="button" class="btn-novo">Definir imagem</button>
            </form>

            <script>
                const botaoImagemPerfil = document.getElementById("botao-imagem-perfil");
                const inputImagemPerfil = document.getElementById("input-imagem-perfil");
                const formImagemPerfil = document.getElementById("form-imagem-perfil");
                botaoImagemPerfil.onclick = function(e) {
                    inputImagemPerfil.click();
                };
                inputImagemPerfil.onchange = function(e) {
                    if (inputImagemPerfil.files.length > 0) formImagemPerfil.submit();
                };
            </script>