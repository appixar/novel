<?php
//===========================================================================================================
//===========================================================================================================
// IMPORT PRODUCTS FROM JSON
//===========================================================================================================
//===========================================================================================================
// START ARION FRAMEWORK
include __DIR__ . "/../../../arion/autoload.php";
new arion();

// START JOB
$job = new job(true); // true = ignore path permissions
$job->start();

// MYSQL
// LOOP IN DATABASES
$myShared = new my();
$com = $myShared->query('SELECT * FROM qmz_company WHERE com_status = 1');

for ($x = 0; $x < count($com); $x++) {

    $my = new my(['id' => 'dynamic', 'wildcard' => $com[$x]['com_code']]);

    // PENDING LAST IMPORT FILE
    $imp = $my->query("SELECT * FROM qmz_product_import WHERE imp_status IS NULL ORDER BY imp_id DESC LIMIT 1");
    $job->say("- Company: {$com[$x]['com_code']}", true, false);
    if (empty($imp)) {
        $job->say("- Nothing here.", false, false);
        goto next_company;
    }

    // PROCESS DATA
    $json = utf8_encode(file_get_contents("{$_APP['URL']}/upload/import/{$imp[0]['imp_file']}"));
    $res = json_decode($json, true);
    if (json_last_error()) {
        $job->say("- JSON error: " . json_last_error(), false, false);
        $my->update("qmz_product_import", ['imp_status' => -1, 'imp_error' => json_last_error()], ["imp_id" => $imp[0]['imp_id']]);
        goto next_company;
    }
    if (!@$res['produtos_ofertas'] or empty($res['produtos_ofertas'])) {
        $job->say("- JSON NULL ?", false, false);
        $my->update("qmz_product_import", ['imp_status' => -1, 'imp_error' => "NOT_FOUND"], ["imp_id" => $imp[0]['imp_id']]);
        goto next_company;
    }
    $job->say("- Processing " . count($res['produtos_ofertas']) . " items...", false, false);

    // UPDATE STATUS
    $upd = [
        "imp_status" => 2,
        "imp_count_total" => count($res['produtos_ofertas']),
        "imp_date_start" => date("Y-m-d H:i:s")
    ];
    $my->update("qmz_product_import", $upd, ["imp_id" => $imp[0]['imp_id']]);
    $my->query("UPDATE qmz_product_import SET imp_status = -2 WHERE imp_status IS NULL"); // ignore others before

    // DISABLE ALL PRODUCTS
    $my->query("UPDATE qmz_product SET pro_status = 0");
    $my->query("UPDATE qmz_product_categ SET cat_status = 0 WHERE cat_status = 1");

    // CAT ACTIVE CONTROL (PREVENT REPEAT)
    $cat_active = array();

    // LOOP
    for ($i = 0; $i < count(@$res['produtos_ofertas']); $i++) {
        //if ($i === 500) return true;
        //
        $now = date('Y-m-d H:i:s');
        $r = $res['produtos_ofertas'][$i];
        // DATA TO INSERT/UPDATE
        $data = array();
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
        if (!$cat_id) $data['pro_status'] = 2;
        else $data['pro_status'] = 1;
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
                //if (!$data['cat_id']) unset($data['cat_id']); // null bugfix
                $data['pro_title'] = addslashes($sha[0]['pro_title']);
                $data['pro_img'] = $sha[0]['pro_img'];
                $data['pro_barcode'] = $sha[0]['pro_barcode'];
                $data['pro_status'] = 1;
            } else {
                $job->say("------ NOT FOUND. => INSERT BLANK DATA.", false, false, 'cyan');
                // DONT EXIST IN 'SHARED DB' & OFERTAS. FILL FIELDS WITH SRC DATA.
                $data['pro_title'] = addslashes($r['nome_produto']);
                $data['pro_status'] = 2; // PENDENTE
            }
            $data['pro_date_insert'] = $now;

            // INSERT
            $pro_id = $my->insert('qmz_product', $data);
            $job->say("------ NEW ID: $pro_id", false, false, 'green');
        }
        // UPDATE CATEG STATUS
        //$job->say($cat_id, true, false, 'green');
        if (!in_array($cat_id, $cat_active) and $cat_id > 0) {
            $cat_active[] = $cat_id;
            $job->say("------ Activate category $cat_id (parent=$cat_parent_id)", false, false, 'green');
            $my->update('qmz_product_categ', ['cat_status' => 1], ['cat_id' => $cat_id]);
            $my->update('qmz_product_categ', ['cat_status' => 1], ['cat_id' => $cat_parent_id]);
        }
        // COUNT CURRENT
        $count = $i + 1;
        $my->query("UPDATE qmz_product_import SET imp_count_current = $count WHERE imp_id = {$imp[0]['imp_id']}");
    }
    $upd = [
        "imp_status" => 1,
        "imp_date_end" => date("Y-m-d H:i:s")
    ];
    $my->update("qmz_product_import", $upd, ["imp_id" => $imp[0]['imp_id']]);

    next_company:
}
