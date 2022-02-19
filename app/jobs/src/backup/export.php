<?php

//===========================================================================================================
//===========================================================================================================
// CLI-EXPORT - SEND NEW USERS TO JUCY
//===========================================================================================================
//===========================================================================================================
// START ARION FRAMEWORK
include __DIR__ . "/../../../arion/autoload.php";
new arion();

// START JOB
$job = new job();
$job->start();

sleep(3);
file_put_contents(__DIR__ . 'export.txt', time(), FILE_APPEND);
sleep(3);
file_put_contents(__DIR__ . 'export.txt', time(), FILE_APPEND);
sleep(3);
file_put_contents(__DIR__ . 'export.txt', time(), FILE_APPEND);
$job->end();
exit;

//===========================================================================================================
// RUN CRON
//===========================================================================================================
$res = jwquery("SELECT * FROM usuarios_clientes WHERE id > '{$job->get_last_id()}' AND id_empresa = 267 LIMIT 100");

$total = count($res);

if ($total > 0) $job->say("SENDING $total NEW USERS TO JUCY(267) ...", true, true, "cyan");
else $job->say("0 FOUND.", false, true);

for ($i = 0; $i < count($res); $i++) {

    $user_id = $res[$i]['id'];

    $job->say("- User ID: <blue>$user_id</end>. Date: {$res[$i]['time_cadastro']}. Name: <yellow>{$res[$i]['nome']} {$res[$i]['sobrenome']}</end> ($user_id)", false, true);

    $addr = jwquery("SELECT * FROM usuarios_clientes_enderecos WHERE id_usuario = '$user_id' ORDER BY id DESC LIMIT 1");

    // data fix (curl error prevent)
    if (!$res[$i]['data_aniversario']) $res[$i]['data_aniversario'] = '1990-01-01 00:00:00';

    $payload = json_encode(utf8ize(array(
        // personal data
        "nome" => utf8_encode($res[$i]['nome'] . ' ' . $res[$i]['sobrenome']),
        "cpf" => $res[$i]['cpf'],
        "telefone" => $res[$i]['telefone'],
        "dataNascimento" => $res[$i]['data_aniversario'],
        "celular" => $res[$i]['telefone'],
        "email" => $res[$i]['email'],
        // address
        "cep" => $addr[0]['cep'],
        "endereco" => utf8_encode($addr[0]['rua']),
        "complemento" => utf8_encode($addr[0]['ponto_referencia']),
        "bairro" => utf8_encode($addr[0]['bairro']),
        "numeroEndereco" => $addr[0]['numero'],
        "uf" => "ES",
        "cidade" => utf8_encode($addr[0]['cidade']),
        "codigoIbge" => 0 // required
    )));
    $job->say("- Sendind data:", false, false);
    $job->say($payload, false, true);
    //------------------------------------
    // send data
    //------------------------------------
    $output = sendData($payload);
    $arr = json_decode($output, true);
    if (!isset($arr['id'])) $job->say("- FAIL: $output", false, true, 'red');
    else $job->say("- DONE! {$arr['id']}", false, true, 'green');
    $job->say("", false, false);
}
if ($user_id > 0) $job->set_last_id($user_id);
$job->end();
