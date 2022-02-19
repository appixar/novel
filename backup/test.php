<?php
//header('Content-Type: text/html; charset=utf-8');
// CUSTOM SETTINGS
date_default_timezone_set('America/Sao_Paulo');

// START ARION
include __DIR__ . "/arion/autoload.php";
$arion = new arion();

set_time_limit(0);
ini_set('max_execution_time', 0);

//=======================================
// PRODUTOS SEM CODIGO DE BARRAS => CATEG
//=======================================
$res = jwquery('SELECT * FROM share_product WHERE pro_id >= 72561');
for ($i = 0; $i < count($res); $i++) {
    $r = $res[$i];
    $cat = jwquery("SELECT cat_id,cat_title FROM qmz_product_categ WHERE id_secao = '{$r['cat_id']}'");
    $cat_id = @$cat[0]['cat_id'];
    if (!$cat_id) $cat_id = 0;
    //echo "{$r['pro_title']} old_cat={$r['cat_id']} new_cat=$cat_id ({$cat[0]['cat_title']})<br/>";
    jwupdate("share_product", array('cat_id' => $cat_id), array('pro_id' => $r['pro_id']));
}
echo $i;
exit;

//=======================================
// PRODUTOS SEM CODIGO DE BARRAS
//=======================================
$res = jwquery('SELECT * FROM bk_produtos WHERE (cod_barras = "" OR cod_barras IS NULL) AND (id_medicamento IS NULL OR id_medicamento = 0)');
$x = 0;
for ($i = 0; $i < count($res); $i++) {
    $r = $res[$i];
    if (!is_numeric(@$r['produto_observacoes_carrinho'])) $r['produto_observacoes_carrinho'] = 0;
    if (!is_numeric(@$r['id_secao'])) $r['id_secao'] = 0;
    if (!@$r['id_secao']) $r['id_secao'] = 0;
    $x++;
    $ins = array(
        'old_id' => $r['id'],
        'pro_title' => addslashes($r['nome']),
        'pro_date_insert' => $r['timestamp'],
        'pro_status' => $r['status'],
        'pro_cart_notes' => $r['produto_observacoes_carrinho'],
        //'pro_barcode' => str_replace("'", '', $r['cod_barras']),
        'pro_descr' => addslashes($r['descricao']),
        'cat_id' => $r['id_secao']
    );
    pre($ins);
    $id = jwinsert('share_product', $ins);
    echo $id;
    jumpx:
    unset($r);
}
echo $x;
exit;


//=======================================
// IMPORTAÇÃO DE PRODUTOS
//=======================================
#$json = file_get_contents('test.json');
#$json = '{"produtos_ofertas": [{"cod_barras":"771","nome_produto":"ABACATE KG","preco_venda":9.980000,"preco_oferta":0.00,"preco_custo":5.750000,"estoque":100,"codigo_interno":"71466"},{"cod_barras":"7896434920174","nome_produto":"ABACAXI EM CALDA TRIANGULO 400ML","preco_venda":6.990000,"preco_oferta":0.00,"preco_custo":5.133975,"estoque":1,"codigo_interno":"51561"} ] }';
#$json = stripslashes(html_entity_decode(utf8_encode(trim($json))));
$json = utf8_encode(file_get_contents('test.json'));
$res = json_decode($json, true);
//pre($res);exit;
//echo json_last_error_msg();
// DISABLE ALL PRODUCTS
jwquery("UPDATE qmz_product SET pro_status = 0");
for ($i = 0; $i < count(@$res['produtos_ofertas']); $i++) {
    //
    $now = date('Y-m-d H:i:s');
    $r = $res['produtos_ofertas'][$i];
    // DATA TO INSERT/UPDATE
    $data = array(
        'pro_src_id' => $r['codigo_interno'],
        'pro_src_title' => addslashes($r['nome_produto']),
        'pro_price' => $r['preco_venda'],
        'pro_price_discount' => $r['preco_oferta'],
        'pro_price_cost' => $r['preco_custo'],
        'pro_stock' => $r['estoque'],
        'pro_date_update' => $now,
        'pro_status' => 1
    );
    //----------------------------------------------
    // OLD PRODUCT. UPDATE.
    // PRO ALREADY EXISTS IN CLIENT STOCK? UPDATE
    //----------------------------------------------
    $pro = jwquery("SELECT * FROM qmz_product WHERE pro_src_id = '{$r['codigo_interno']}'");
    $pro_id = @$pro[0]['pro_id'];
    if ($pro_id) {
        jwupdate('qmz_product', $data, array('pro_id' => $pro_id));
    }
    //----------------------------------------------
    // NEW PRODUCT. INSERT.
    // PRO DONT EXISTS IN CLIENT STOCK
    //----------------------------------------------
    else {
        // FIND PRO IN 'SHARED DB'
        $sha = jwquery("SELECT * FROM share_product WHERE pro_barcode = '{$r['cod_barras']}'");
        $sha_id = @$sha[0]['pro_id'];
        // EXISTS IN SHARED DB. 
        if ($sha_id) {
            $data['pro_qmz_id'] = $sha_id;
            $data['cat_id'] = $sha[0]['cat_id'];
            if (!$data['cat_id']) unset($data['cat_id']); // null bugfix
            $data['pro_title'] = addslashes($sha[0]['pro_title']);
            $data['pro_img'] = $sha[0]['pro_img'];
            $data['pro_barcode'] = $sha[0]['pro_barcode'];
        } else {
            // FIND PRO IN 'OFERTAS'
            $of = jwquery("SELECT pro.* FROM bk_ofertas bko INNER JOIN bk_produtos pro ON pro.id=bko=id_produto WHERE codigo_interno = '{$r['cod_barras']}' AND id_empresa=267");
            $of_id = @$of[0]['id'];
            // EXISTS IN SHARED DB. 
            if ($of_id) {
                $data['pro_qmz_id'] = $sha_id;
                $data['cat_id'] = $sha[0]['cat_id'];
                if (!$data['cat_id']) unset($data['cat_id']); // null bugfix
                $data['pro_title'] = addslashes($sha[0]['pro_title']);
                $data['pro_img'] = $sha[0]['pro_img'];
                $data['pro_barcode'] = $sha[0]['pro_barcode'];
            }

            // DONT EXIST IN 'SHARED DB' & OFERTAS. FILL FIELDS WITH SRC DATA.
            $data['pro_title'] = addslashes($r['nome_produto']);
        }
        $data['pro_date_insert'] = $now;
        // INSERT
        $pro_id = jwinsert('qmz_product', $data);
    }
    if ($i === 100) exit;
}
exit;

//=======================================
// IMAGENS
//=======================================
echo '<pre>';
$res = jwquery('SELECT produtos_id, codigo FROM bk_imagem WHERE produtos_id > 0');
$img = array();
for ($i = 0; $i < count($res); $i++) {
    if (!@$img[$res[$i]['produtos_id']]) {
        $img[$res[$i]['produtos_id']] = $res[$i]['codigo'];
    }
}
//echo count($img); exit;
$res = jwquery('SELECT pro_id,pro_title,pro_barcode,old_id FROM share_product WHERE pro_barcode IS NOT NULL');
//echo count($res); exit;
for ($i = 0; $i < count($res); $i++) {
    $id = $res[$i]['pro_id'];
    $old_id = $res[$i]['old_id'];
    $code = @$img[$old_id];
    if ($code) {
        $url = "https://painel.qmoleza.com.br/data/dynamic/produtos/$old_id/{$code}_sm.jpg";
        echo "$old_id => $id {$res[$i]['pro_title']} $url<br/>";
        jwupdate('share_product', array('pro_img' => $url), array('pro_id' => $id));
    }
}
exit;

//=======================================
// GUARDAR IDS ANTIGOS DOS PRODUTOS
//=======================================
$res = jwquery('SELECT pro_id,pro_title,pro_barcode FROM share_product WHERE pro_barcode IS NOT NULL');
//echo count($res); exit;
for ($i = 0; $i < count($res); $i++) {
    $id = $res[$i]['pro_id'];
    $r = jwquery("SELECT id FROM bk_produtos WHERE cod_barras='{$res[$i]['pro_barcode']}'");
    if (@$r[0]['id']) {
        $old_id = $r[0]['id'];
        echo "$old_id => $id {$res[$i]['pro_title']} <br/>";
        jwupdate('share_product', array('old_id' => $old_id), array('pro_id' => $id));
    }
}
exit;


//=======================================
// ATUALIZA ID_DEPARTAMENTO => CAT_PARENT_ID
//=======================================
echo '<pre>';
$res = jwquery('SELECT * FROM qmz_product_categ');
$cat = array();
for ($i = 0; $i < count($res); $i++) {
    $cat[$res[$i]['id_dep']] = $res[$i]['cat_id'];
}
for ($i = 0; $i < count($res); $i++) {
    $id = $res[$i]['cat_id'];
    $name = utf8_encode($res[$i]['cat_title']);
    $cat_old = $res[$i]['cat_parent_id'];
    $cat_new = @$cat[$res[$i]['cat_parent_id']];
    if (!$cat_old or !$cat_new) goto next_cat;
    echo "$id $name (parent: $cat_old => $cat_new)<br/>";
    jwupdate('qmz_product_categ', array('cat_parent_id' => $cat_new), array('cat_id' => $id));
    next_cat:
}
exit;

//=======================================
// ATUALIZA ID_SECAO => CAT_ID
//=======================================
echo '<pre>';
$res = jwquery('SELECT * FROM qmz_product_categ');
$cat = array();
for ($i = 0; $i < count($res); $i++) {
    $cat[$res[$i]['id_secao']] = $res[$i]['cat_id'];
}
$res = jwquery('SELECT pro.*, cat.cat_title FROM share_product pro INNER JOIN qmz_product_categ cat ON cat.id_secao = pro.cat_id');
for ($i = 0; $i < count($res); $i++) {
    //
    $last_id = @$id;
    $last_barcode = @$barcode;
    //
    $id = $res[$i]['pro_id'];
    $barcode = $res[$i]['pro_barcode'];
    //
    $name = utf8_encode($res[$i]['pro_title']);
    $cat_old = $res[$i]['cat_id'];
    $cat_new = @$cat[$res[$i]['cat_id']];
    if (!$cat_new) goto next_pro;
    $cat_title = utf8_encode($res[$i]['cat_title']);
    echo "$id $name ($barcode) ($cat_old => $cat_new) $cat_title<br/>";
    //if ($last_barcode == $barcode) echo "excluir $last_id<br/>";
    //
    jwupdate('share_product', array('cat_id' => $cat_new), array('pro_id' => $res[$i]['pro_id']));
    next_pro:
}
exit;

//=======================================
// SEÇÕES
//=======================================
$res = jwquery('SELECT * FROM bk_secoes');
for ($i = 0; $i < count($res); $i++) {
    $r = $res[$i];
    $ins = array(
        'cat_title' => addslashes($r['nome']),
        'cat_status' => $r['status'],
        'id_secao' => $r['id'],
        'cat_parent_id' => $r['id_departamento']
    );
    jwinsert('qmz_product_categ', $ins);
    unset($r);
}
echo $i;

exit;

//=======================================
// DEPARTAMENTOS 
//=======================================
$res = jwquery('SELECT * FROM bk_departamentos');
for ($i = 0; $i < count($res); $i++) {
    $r = $res[$i];
    $ins = array(
        'cat_title' => addslashes($r['nome']),
        'cat_status' => $r['status'],
        'id_dep' => $r['id']
    );
    jwinsert('qmz_product_categ', $ins);
    unset($r);
}
echo $i;
exit;


//=======================================
// SEÇÕES
//=======================================
$res = jwquery('SELECT * FROM bk_secoes');
for ($i = 0; $i < count($res); $i++) {
    $r = $res[$i];
    $ins = array(
        'cat_title' => addslashes($r['nome']),
        'cat_status' => $r['status'],
        'cat_old_id' => $r['id'],
        'cat_parent_id' => $r['id_departamento']
    );
    jwinsert('qmz_product_categ', $ins);
    unset($r);
}
echo $i;




//=======================================
// PRODUTOS 
//=======================================
$res = jwquery('SELECT * FROM produtos WHERE id_medicamento IS NULL OR id_medicamento = 0');
for ($i = 0; $i < count($res); $i++) {
    $r = $res[$i];
    if (!is_numeric(@$r['produto_observacoes_carrinho'])) $r['produto_observacoes_carrinho'] = 0;
    if (!is_numeric(@$r['id_secao'])) $r['id_secao'] = 0;
    if (!@$r['id_secao']) $r['id_secao'] = 0;
    if (!is_numeric(@$r['cod_barras'])) {
        $ignore[] = $r;
        goto jump;
    }
    $ins = array(
        'old_id' => $r['id'],
        'pro_title' => addslashes($r['nome']),
        'pro_date_insert' => $r['timestamp'],
        'pro_status' => $r['status'],
        'pro_cart_notes' => $r['produto_observacoes_carrinho'],
        'pro_barcode' => str_replace("'", '', $r['cod_barras']),
        'pro_descr' => addslashes($r['descricao']),
        'cat_id' => $r['id_secao']
        // remédio
        /*'drug_info' => $r['bula'],
        'drug_data' => $r['apresentacao'],
        'drug_ap' => $r['principio_ativo'],
        'drug_ms_register' => $r['registro_ms'],
        'drug_info' => $r['bula'],*/
    );
    jwinsert('share_product', $ins);
    jump:
    unset($r);
}
echo $i;
pre($ignore);
