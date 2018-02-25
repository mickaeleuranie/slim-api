<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <?php
    if (!isset($title)) :
        $title = 'Slim template';
    endif;
    ?>
    <title><?= $this->e($title) ?></title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700" rel="stylesheet">

    <?php // add needed CSS files
    if (isset($css)) :
        foreach ($css as $cssFile) :
        ?>
            <link rel="stylesheet" href="<?=$this->e($this->baseUrl('assets/') . 'css/' . $cssFile)?>" />
        <?php
        endforeach;
    endif; ?>

    <?php // add needed JS files to be loaded in the HEAD
    if (isset($js)) :
        foreach ($js as $jsFile) :
        ?>
            <script src="<?=$this->e($this->baseUrl('assets/') . 'js/' . $jsFile)?>"></script>
        <?php
        endforeach;
    endif; ?>
</head>
<body>
    <?= $this->section('content') ?>

    <?php // add needed JS files to be loaded in the bottom of body
    if (isset($jsBottom)) :
        foreach ($jsBottom as $jsBottomFile) :
        ?>
            <script src="<?=$this->e($this->baseUrl('assets/') . 'js/' . $jsBottomFile)?>" />
        <?php
        endforeach;
    endif; ?>
</body>
</html>
