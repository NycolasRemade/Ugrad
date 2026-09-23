<?php 

//Código para pegar o BLOB de uma imagem (para inserir ela como imagem padrão de perfil)

$imagem = 'Fotos/Ugrad logo cinza.png';
$imgInfo = getimagesize($imagem);
$imagem_original = imagecreatefrompng($imagem);
                    imagepalettetotruecolor($imagem_original);
                    imagealphablending($imagem_original, false);
                    imagesavealpha($imagem_original, true);

ob_start();
                imagewebp($imagem_original, null, 70);
                imagedestroy($imagem_original);
$imagem_nova = ob_get_clean();


            