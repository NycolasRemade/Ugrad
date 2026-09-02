<?php
include 'header.php'
?>

<script>
    function getTextWidth() {

        const inputText = "Pesquisa de Projetos";
        const font = "1.5em Tan";

        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");
        context.font = font;
        const width = context.measureText(inputText).width;
        const formattedWidth = Math.ceil(width);
        console.log(formattedWidth);
    }

    getTextWidth();
</script>