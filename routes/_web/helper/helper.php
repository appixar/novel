<?php
exit;
$total = 100;
$myConf = ['id' => 'dynamic', 'wildcard' => 'teste-exodus'];
$my = new my($myConf);
//
// random addresses
$addr = $my->query("SELECT * FROM mirror_geo_district WHERE city_id = 9668 ORDER BY RAND() LIMIT $total");
$street = $my->query("SELECT * FROM mirror_geo_district WHERE district <> 'Centro' ORDER BY RAND() LIMIT $total");
//
$names = file_get_contents(__DIR__ . '/names.txt');
$names = explode("\n", $names);
for ($i = 0; $i < $total; $i++) {
    $r = mt_rand(0, count($names));
    $r2 = mt_rand(0, count($names));
    $r3 = mt_rand(0, count($names));
    //
    $bday = mt_rand(-631144800, 1262311200); // 1930 a 2010
    $bday = date("Y-m-d", $bday);
    //
    $now = mt_rand(1641006000, 1645153200); // 01/01/2022 a 18/02/2022
    $now = date("Y-m-d H:i:s", $now);
    //
    $login = low($names[$r]) . rand(1111, 9999) . '@gmail.com';
    $cpf = mt_rand(10000000000, 99999999999);

    $data_user = array(
        'user_fname' => $names[$r],
        'user_lname' => $names[$r2],
        'user_login' => $login,
        'user_cpf' => $cpf,
        'user_pass' => password_hash(123456, PASSWORD_DEFAULT), // $verify = password_verify($plaintext_password, $hash);
        'user_genre' => 'm',
        'user_bday' => $bday,
        'user_phone' => '11' . mt_rand(999000000, 999999999),
        'user_status' => 1,
        // api key
        'user_key' => $cpf,
        'user_key_date_insert' => $now,
        'user_date_insert' => $now
    );
    $user_id = $my->insert('qmz_user', $data_user);
    pre($data_user);
    $data_addr = array(
        'user_id' => $user_id,
        'district_id' => $addr[$i]['district_id'],
        'city_id' => 9668,
        'state_id' => 26,
        'addr_street' => "Rua {$street[$i]['district']}",
        'addr_number' => mt_rand(1, 900),
        'addr_date_insert' => $now
    );
    pre($data_addr);
    $addr_id = $my->insert('qmz_user_address', $data_addr);
}
