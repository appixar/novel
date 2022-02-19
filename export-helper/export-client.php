<?php
//===========================================================================================================
//===========================================================================================================
// IMPORT PRODUCTS FROM JSON
//===========================================================================================================
//===========================================================================================================
// START ARION FRAMEWORK
include __DIR__ . "/../arion/autoload.php";
new arion();

// MYSQL
$myShared = new my();
$my = new my(['id' => 'dynamic', 'wildcard' => 'jucy']);
//
$cli = $myShared->query("SELECT * FROM usuarios_clientes WHERE id_empresa = 267");

$jump = array();
for ($i = 0; $i < count($cli); $i++) {
    $user_key = geraSenha(32, true, true, true);
    $bday = $cli[$i]['data_aniversario'];
    if ($bday > 0) {
        if (strlen($bday) != 19) $bday = '';
    } else $bday = '';
    $phone = ltrim(clean($cli[$i]['telefone']), 0);
    if (strlen($phone) > 11) {
        $jump['phone']++;
        goto jump;
    }
    $fname = trim(mb_ucwords($cli[$i]['nome']));
    if (strlen($fname) > 64) {
        $jump['fname']++;
        goto jump;
    }
    $ins = array(
        'user_fname' => $fname,
        'user_lname' => trim(mb_ucwords($cli[$i]['sobrenome'])),
        'user_phone' => $phone,
        'user_login' => low($cli[$i]['email']),
        'user_pass' => $cli[$i]['senha'],
        'user_cpf' => clean($cli[$i]['cpf']),
        'user_pass' => $cli[$i]['senha'],
        'user_date_insert' => $cli[$i]['time_cadastro'],
        'user_status' => $cli[$i]['status'],
        'user_bday' => $bday,
        'user_key' => $user_key,
    );
    pre($ins);
    $cli_id = $my->insert('qmz_user', $ins);

    jump:
}
pre($jump);
