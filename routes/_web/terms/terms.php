<?php
header('Content-type: text/html');

// get com_code => first parameter /css/<jucy>
if (!@$_PAR[0]) if (!@$res[0]) die('?');
$com_code = clean_spaces(clean($_PAR[0]));

// get config
$my = new my(['id' => 'dynamic', 'wildcard' => $com_code]);
$conf = $my->query("SELECT * FROM qmz_config WHERE conf_id = 1", ['com_code' => $com_code])[0];
if (empty($conf)) die(404);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Termos de Uso - <?= $conf['conf_title'] ?>" />
    <meta property="og:title" content="Termos de Uso - <?= $conf['conf_title'] ?>" />
    <meta property="og:description" content="Termos de Uso - <?= $conf['conf_title'] ?>" />
    <meta name="format-detection" content="telephone=no">
    <title>Termos de Uso - <?= $conf['conf_title'] ?></title>
    <style type='text/css'>
        body,
        html {
            padding: 16px;
            font-family: arial;
            font-size: 16px;
            line-height: 24px;
        }
    </style>
</head>

<body>
    <?= $conf['conf_info_terms'] ?>
</body>

</html>