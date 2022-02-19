<?php
// get com_code => first parameter /css/<jucy>
if (!@$_PAR[0]) if (!@$res[0]) die('?');
$com_code = clean_spaces(clean($_PAR[0]));

// get config
$my = new my(['id' => 'dynamic', 'wildcard' => $com_code]);
$conf = $my->query("SELECT * FROM qmz_config WHERE conf_id = 1", ['com_code' => $com_code])[0];
if (empty($conf)) die(404);
$img = json_decode($conf['conf_json_img'], true);

// build default css
header('Content-type: text/css');
?>
.header-logo {
    background-image: url('<?= $_APP['URL'] ?>/upload/logo/<?= $img['logo_md'] ?>') !important;
}
.top-logo {
    background-image: url('<?= $_APP['URL'] ?>/upload/logo/<?= $img['logo_mono'] ?>') !important;
}
.menu-logo {
    background-image: url('<?= $_APP['URL'] ?>/upload/logo/<?= $img['logo_mono'] ?>') !important;
}
.splash-logo {
    background-image: url('<?= $_APP['URL'] ?>/upload/logo/<?= $img['logo_lg'] ?>') !important;
}

<?php
// echo custom css
echo $conf['conf_css'];
