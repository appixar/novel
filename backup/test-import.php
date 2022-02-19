<?php

//===========================================================================================================
//===========================================================================================================
// IMPORT PRODUCTS FROM JSON
//===========================================================================================================
//===========================================================================================================
// START ARION FRAMEWORK
include __DIR__ . "/arion/autoload.php";
new arion();

// START JOB
$job = new job(true); // true = ignore path permissions
$job->start();

// MYSQL
$my = new my(['id' => 'dynamic', 'wildcard' => 'polonini']);
$myShared = new my(['id' => '0']);

// PROCESS DATA
$json = utf8_encode(file_get_contents('test.json'));
$res = json_decode($json, true);
$job->say("- Processing " . count($res['produtos_ofertas']) . " items...", false, false);

// DISABLE ALL PRODUCTS
$my->query("UPDATE qmz_product SET pro_status = 0");
$my->query("UPDATE qmz_product_categ SET cat_status = 0 WHERE cat_status = 1");

// CAT ACTIVE CONTROL (PREVENT REPEAT)
$cat_active = array();

// LOOP
for ($i = 0; $i < count(@$res['produtos_ofertas']); $i++) {
    if ($i === 500) return true;
    //
    $now = date('Y-m-d H:i:s');
    $r = $res['produtos_ofertas'][$i];
    // DATA TO INSERT/UPDATE
    $data = array(
        'pro_src_id' => $r['codigo_interno'],
        'pro_src_title' => addslashes($r['nome_produto']),
        'pro_price' => $r['preco_venda'],
        //'pro_price_discount' => $r['preco_oferta'],
        'pro_price_cost' => $r['preco_custo'],
        'pro_stock' => $r['estoque'],
        'pro_date_update' => $now,
        'pro_status' => 1
    );
    $job->say("{$r['nome_produto']}", false, false, 'blue');
    //----------------------------------------------
    // OLD PRODUCT. UPDATE.
    // PRO ALREADY EXISTS IN CLIENT STOCK? UPDATE
    //----------------------------------------------
    $qr = "SELECT p.*, c.cat_parent_id FROM qmz_product p LEFT JOIN qmz_product_categ c ON c.cat_id = p.cat_id WHERE pro_src_id = '{$r['codigo_interno']}'";
    $pro = $my->query($qr);
    $pro_id = @$pro[0]['pro_id'];
    $cat_id = @$pro[0]['cat_id'];
    $cat_parent_id = @$pro[0]['cat_parent_id'];
    //
    $job->say("--- $qr", false, false);
    //
    if ($pro_id) {
        $job->say("------ Found in Current DB! => UPDATE.", false, false, 'green');
        $my->update('qmz_product', $data, array('pro_id' => $pro_id));
    }
    //----------------------------------------------
    // NEW PRODUCT. INSERT.
    // PRO DONT EXISTS IN CLIENT STOCK
    //----------------------------------------------
    else {
        // FIND PRO IN 'SHARED DB'
        $qr = "SELECT p.*, c.cat_parent_id FROM qmz_product p LEFT JOIN qmz_product_categ c ON c.cat_id = p.cat_id WHERE pro_barcode = '{$r['cod_barras']}'";
        $sha = $myShared->query($qr);
        $sha_id = @$sha[0]['pro_id'];
        $cat_id = @$sha[0]['cat_id'];
        $cat_parent_id = @$sha[0]['cat_parent_id'];
        //
        $job->say("--- $qr", false, false);
        //
        // EXISTS IN SHARED DB. 
        if ($sha_id) {
            $job->say("------ Found in Shared DB! => INSERT.", false, false, 'cyan');
            $data['pro_qmz_id'] = $sha_id;
            $data['cat_id'] = $sha[0]['cat_id'];
            if (!$data['cat_id']) unset($data['cat_id']); // null bugfix
            $data['pro_title'] = addslashes($sha[0]['pro_title']);
            $data['pro_img'] = $sha[0]['pro_img'];
            $data['pro_barcode'] = $sha[0]['pro_barcode'];
        } else {
            $job->say("------ NOT FOUND. => INSERT BLANK DATA.", false, false, 'cyan');
            // DONT EXIST IN 'SHARED DB' & OFERTAS. FILL FIELDS WITH SRC DATA.
            $data['pro_title'] = addslashes($r['nome_produto']);
            $data['pro_status'] = 2; // PENDENTE
            //----------------------------------------------------------
            // OBSOLETE
            //----------------------------------------------------------
            /*
            // FIND PRO IN 'OFERTAS'
            $qr = ""
                . "SELECT qmz.*, qmz.pro_id FROM bk_ofertas bko "
                . "INNER JOIN bk_produtos pro ON pro.id=bko.id_produto "
                . "INNER JOIN share_product qmz ON pro.id=qmz.old_id "
                . "WHERE bko.codigo_interno = '{$r['codigo_interno']}' AND bko.id_empresa=267";
            $of = jwquery($qr);
            //
            $job->say("--- $qr", false, false);
            //
            $of_id = @$of[0]['pro_id'];
            // EXISTS IN 'OFERTAS'
            if ($of_id) {
                $job->say("------ Found! => INSERT.", false, false, 'cyan');
                $data['pro_qmz_id'] = $of_id;
                $data['cat_id'] = $of[0]['cat_id'];
                if (!$data['cat_id']) unset($data['cat_id']); // null bugfix
                $data['pro_title'] = addslashes($of[0]['pro_title']);
                $data['pro_img'] = $of[0]['pro_img'];
                $data['pro_barcode'] = $of[0]['pro_barcode'];
            } else {
            $job->say("------ NOT FOUND. => INSERT BLANK DATA.", false, false, 'cyan');
            // DONT EXIST IN 'SHARED DB' & OFERTAS. FILL FIELDS WITH SRC DATA.
            $data['pro_title'] = addslashes($r['nome_produto']);
            $data['pro_status'] = 2; // PENDENTE
            }*/
        }
        if (!in_array($cat_id, $cat_active) and $cat_id > 0) {
            $cat_active[] = $cat_id;
            $job->say("------ Activate category $cat_id (parent=$cat_parent_id)", false, false, 'green');
            $my->update('qmz_product_categ', ['cat_status' => 1], ['cat_id' => $cat_id]);
            $my->update('qmz_product_categ', ['cat_status' => 1], ['cat_id' => $cat_parent_id]);
        }
        $data['pro_date_insert'] = $now;
        // INSERT
        $pro_id = $my->insert('qmz_product', $data);
        $job->say("------ NEW ID: $pro_id", false, false, 'green');
    }
    //if ($i === 30) exit;
}

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
