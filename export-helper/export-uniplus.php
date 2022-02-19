<?php
// user_id = 37622 (TESTE joao@qmoleza.com)
//===========================================================================================================
//===========================================================================================================
// CLI-EXPORT - SEND NEW USERS TO JUCY
//===========================================================================================================
//===========================================================================================================
// START ARION FRAMEWORK
include __DIR__ . "/../arion/autoload.php";
$arion = new arion();

// MYSQL
$last_id = file_get_contents(__DIR__ . '/export-uniplus.txt');
$my = new my(['id' => 'dynamic', 'wildcard' => 'jucy']);
$now = date("Y-m-d H:i:s");

function sendData($payload)
{
    $headers = array(
        'Content-Type: application/json',
        'authorization: bearer d9d2028c45c24e1eb69c258f85866f79'
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://187.120.33.194:5555/entidade");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"postvar1=value1&postvar2=value2&postvar3=value3");
    //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('postvar1' => 'value1')));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    if (curl_errno($ch)) $error = curl_error($ch);
    curl_close($ch);
    if (isset($error)) return $error;
    return $output;
}

//===========================================================================================================
// RUN CRON
//===========================================================================================================
$res = $my->query("SELECT * FROM qmz_user WHERE user_id > $last_id LIMIT 100");

$total = count($res);

if ($total > 0) echo "SENDING $total NEW USERS TO UNIPLUS/JUCY ..." . PHP_EOL;
else echo "0 FOUND." . PHP_EOL;

for ($i = 0; $i < @count($res); $i++) {

    $user_id = $res[$i]['user_id'];

    echo "- User ID: <blue>$user_id</end>. Date: {$res[$i]['user_date_insert']}. Name: <yellow>{$res[$i]['user_fname']} {$res[$i]['user_lname']}</end> ($user_id)" . PHP_EOL;

    $addr = $my->query("SELECT addr.*, d.district, c.city FROM qmz_user_address addr INNER JOIN mirror_geo_district d ON d.district_id = addr.district_id INNER JOIN mirror_geo_city c ON c.city_id = addr.city_id WHERE user_id = '$user_id' AND addr_status = 1 ORDER BY addr_id DESC LIMIT 1");

    // data fix (curl error prevent)
    if (!$res[$i]['user_bday']) $res[$i]['user_bday'] = '1990-01-01 00:00:00';

    $cpf = $res[$i]['user_cpf'];
    $cpf0 = substr($cpf, 0, 3);
    $cpf1 = substr($cpf, 3, 3);
    $cpf2 = substr($cpf, 6, 3);
    $cpf3 = substr($cpf, 9, 2);
    $cpf = "$cpf0.$cpf1.$cpf2-$cpf3";

    $payload = json_encode(utf8ize(array(
        // personal data
        "nome" => $res[$i]['user_fname'] . ' ' . $res[$i]['user_lname'],
        "cpf" => $cpf,
        "telefone" => $res[$i]['user_phone'],
        "dataNascimento" => $res[$i]['user_bday'],
        "celular" => $res[$i]['user_phone'],
        "email" => $res[$i]['user_login'],
        // address
        "cep" => @$addr[0]['addr_cep'],
        "endereco" => @$addr[0]['addr_street'],
        "complemento" => @$addr[0]['addr_compl'],
        "bairro" => @$addr[0]['district'],
        "numeroEndereco" => @$addr[0]['addr_number'],
        "uf" => "ES",
        "cidade" => @$addr[0]['city'],
        "codigoIbge" => 0 // required
    )));
    echo $payload . PHP_EOL;
    //------------------------------------
    // send data
    //------------------------------------
    $output = sendData($payload);
    $arr = json_decode($output, true);
    if (!isset($arr['id'])) echo "- FAIL: $output" . PHP_EOL;
    else echo "- DONE! {$arr['id']}" . PHP_EOL;
}
if (@$user_id > 0) file_put_contents(__DIR__ . '/export-uniplus.txt', $user_id);
