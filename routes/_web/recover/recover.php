<?php
header('Content-type: text/html');

// get com_code => first parameter /css/<jucy>
if (!@$_PAR[0]) die('com_code?');
$com_code = clean_spaces(clean($_PAR[0]));

// get rec_code
if (!@$_PAR[1]) die('rec_code?');
$rec_code = clean_spaces(clean($_PAR[1]));

// get config
$my = new my(['id' => 'dynamic', 'wildcard' => $com_code]);
$conf = $my->query("SELECT * FROM qmz_config WHERE conf_id = 1");
if (empty($conf)) die('<pre>COMPANY NOT FOUND');

// check code
$res = $my->query("SELECT * FROM qmz_user_recover WHERE rec_code = '$rec_code' AND rec_status = 1");
if (!empty($res)) {
    // get user
    $user = $my->query("SELECT user_fname FROM qmz_user WHERE user_id = '{$res[0]['user_id']}'");
    if (empty($user)) die('<pre>USER NOT FOUND');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recuperação de Senha - <?= $conf[0]['conf_title'] ?></title>
    <style type='text/css'>
        body,
        html {
            padding: 16px;
            font-family: arial, sans-serif;
            font-size: 16px;
            line-height: 24px;
        }

        input {
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h3>Recuperação de Senha - <?= $conf[0]['conf_title'] ?></h3>

    <?php
    // LINK INVÁLIDO
    if (empty($res)) {
        echo "<p>Desculpe, este link expirou ou é inválido.</p>";
    }
    // LINK VÁLIDO
    else {
    ?>
        <?php if (@$_GET['err']) echo "<p style='color:red'>Desculpe, não foi possível alterar sua senha. Tente novamente.</p>"; ?>
        <p>Olá, <?= $user[0]['user_fname'] ?>!</p>
        <p>Por favor, escolha uma nova senha:</p>
        <form action='/_web/recover/.post' method='post'>
            <input type='hidden' name='com_code' value='<?= $com_code ?>' />
            <input type='hidden' name='rec_code' value='<?= $rec_code ?>' />
            <input required type='text' name='user_pass' placeholder="Nova senha" minlength="6" />
            <input type='submit' value='Alterar senha' />
        </form>
    <?php
    }
    ?>
    <p><a href='http://<?= $com_code ?>.qmoleza.com'>Cancelar</a></p>
</body>

</html>