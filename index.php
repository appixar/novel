<?php
// CUSTOM SETTINGS
date_default_timezone_set('America/Sao_Paulo');

// START ARION
include __DIR__ . "/arion/autoload.php";
$arion = new arion();
/*
$my = new my();
$ins = array(
    'user_login' => 'qqq@bbb.com',
    'user_pass' => 1,
    'user_fname' => 'José da Silva',
    'user_lname' => 'NULL'
);
$my->update("qmz_user", $ins, array('user_id' => 19));
exit;
*/
/*
$my = new my();
$ins = array(
    'user_login' => '___@aaa.com',
    'user_pass' => 5,
    'user_fname' => 'José da Silva',
    'user_lname' => 'NULL'
);
$id = $my->insert("qmz_user", $ins);
echo $id;
exit;
*/
/*
$my = new my();
$var = ['user_id' => 3];
$res = $my->query("SELECT * FROM qmz_user WHERE user_id = :user_id", $var);
pre($res);
exit;
*/
// START API SERVER
new apiServer();

// RENDER PAGE
$arion->build();
