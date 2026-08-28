<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=$title?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<?php if(isset($href)): ?>
<body onLoad="window.scroll(0, 0)">


    <div id="navbar">
        <img src="Fotos/Polygon 2.png" alt="navbar">
        <a href="<?=$href?>">
            <h1 class="meringue">Ugrad</h1>
        </a>
    </div>
<?php endif;?>