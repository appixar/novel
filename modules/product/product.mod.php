<?php

class product
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // GET
    // -> $body [*pro_id]
    //------------------------------------------------
    public function get($body = array())
    {
        if (@$body['pro_id']) $where = "pro.pro_id = :pro_id";
        elseif (@$body['pro_src_id']) $where = "pro.pro_src_id = :pro_src_id";
        else return false;

        // QUERY
        $fields = "pro.*, off.off_id, cat.cat_title, cat.cat_parent_id AS pcat_id, oi.oi_price";
        $qr = ""
            . "SELECT $fields "
            . "FROM qmz_product pro "
            . "LEFT JOIN qmz_product_categ cat ON pro.cat_id = cat.cat_id "
            //. "INNER JOIN qmz_product_categ pcat ON cat.cat_parent_id = pcat.cat_id "
            . "LEFT JOIN qmz_offer_item oi ON oi.pro_id = pro.pro_id AND oi_date_delete IS NULL AND oi.off_status = 1 "
            . "LEFT JOIN qmz_offer off ON oi.off_id = off.off_id AND off.off_status = 1 AND off_date_delete IS NULL "
            . "WHERE $where "
            . "ORDER BY pro.pro_title";
        $my = new my(dynamic());
        $res = $my->query($qr, $body);

        //prex($res);

        // VERIFY
        if (!@$res[0]) return true;

        // PROCESS
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i]['oi_price']) {
                if (!$res[$i]['off_id']) unset($res[$i]['oi_price']);
            }
        }
        $res = $this->fixData($res);

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // GET ALL (WITH FILTERS)
    //------------------------------------------------
    public function getAll($body = array())
    {
        $where = 'pro.pro_status = 1';
        if (@$body['filter'] === 'no-img') $where = '(pro.pro_img IS NULL OR pro.pro_img = "") AND pro.pro_status = 1';
        if (@$body['filter'] === 'offer') $where = 'oi.off_id IS NOT NULL AND pro.pro_status = 1 AND off.off_status = 1';
        if (@$body['filter'] === 'pending') $where = 'pro.pro_status = 2';
        // QUERY
        $fields = "off.off_id, pcat.cat_title AS pcat_title, cat.cat_title, oi.oi_price, pro.pro_stock, pro.pro_id, pro.pro_status, pro.pro_barcode, pro.pro_src_id, pro.pro_title, pro.pro_price";
        $qr = ""
            . "SELECT $fields "
            . "FROM qmz_product pro "
            . "LEFT JOIN qmz_product_categ cat ON pro.cat_id = cat.cat_id "
            . "LEFT JOIN qmz_product_categ pcat ON cat.cat_parent_id = pcat.cat_id "
            . "LEFT JOIN qmz_offer_item oi ON oi.pro_id = pro.pro_id AND oi.oi_date_delete IS NULL AND oi.off_status = 1 "
            . "LEFT JOIN qmz_offer off ON oi.off_id = off.off_id AND off.off_status = 1 AND off_date_delete IS NULL "
            . "WHERE $where "
            . "ORDER BY pro.pro_title";
        $my = new my(dynamic());
        $res = $my->query($qr);

        // VERIFY
        if (!@$res[0]) return true;

        // PROCESS
        $res = $this->groupById($res);
        $res = $this->fixData($res);

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // GET IMPORT FILES
    //------------------------------------------------
    public function getImport()
    {
        // QUERY
        $qr = "SELECT * FROM qmz_product_import";
        $my = new my(dynamic());
        $res = $my->query($qr);

        // VERIFY
        if (!@$res[0]) return true;

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // POST IMPORT FILE
    //------------------------------------------------
    public function postImport($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['imp_file']) return false;

        // QUERY
        $my = new my(dynamic());
        $ins = array(
            'imp_file' => $body['imp_file'],
            'imp_date_insert' => date("Y-m-d H:i:s")
        );
        $id = $my->insert("qmz_product_import", $ins);

        if (!is_numeric($id)) return false;
        return true;
    }
    //------------------------------------------------
    // GET VIP PRODUCTS
    //------------------------------------------------
    public function getVip($body = array())
    {
        // QUERY
        $fields = "cat.cat_title, pro.pro_stock, pro.pro_id, pro.pro_status, pro.pro_barcode, pro.pro_src_id, pro.pro_title, pro.pro_price, pro_price_discount, pro_discount_min, pro_discount_max";
        $qr = ""
            . "SELECT $fields "
            . "FROM qmz_product pro "
            . "INNER JOIN qmz_product_categ cat ON pro.cat_id = cat.cat_id "
            . "WHERE pro_discount_status > 0 "
            . "ORDER BY pro.pro_title";
        $my = new my(dynamic());
        $res = $my->query($qr);

        // VERIFY
        if (!@$res[0]) return true;

        // PROCESS
        $res = $this->fixData($res);

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // PUT
    // -> $body [*pro_id]
    //------------------------------------------------
    public function put($body = array())
    {
        if (!@$body['pro_id']) return false;

        // Fix data
        $body = $this->fixBody($body);

        // QUERY
        $my = new my(dynamic());
        $my->update('qmz_product', $body, array('pro_id' => $body['pro_id']));

        // RETURN
        return true;
    }
    //------------------------------------------------
    // GET BY TITLE, ID OR BARCODE
    //------------------------------------------------
    public function getByTitle($body)
    {
        if (!@$body['pro_title'] and !@$body['pro_src_id'] and !@$body['pro_barcode']) return false;
        $data = array();
        $where = '';
        if (@$body['pro_title']) {
            $pro_title = $body['pro_title'];
            $pro_title = tirarAcentos(low($pro_title));
            //
            $data = ['pro_title' => "%$pro_title%"];
            $where = 'pro.pro_title LIKE :pro_title';
            //
        } elseif (@$body['pro_src_id']) {
            $data = ['pro_src_id' => $body['pro_src_id']];
            $where = 'pro.pro_src_id = :pro_src_id';
        } elseif (@$body['pro_barcode']) {
            $data = ['pro_barcode' => $body['pro_barcode']];
            $where = 'pro.pro_barcode = :pro_barcode';
        }
        //========================
        // QUERY
        //========================
        $fields = "pro.*, off.off_id, cat.cat_title, cat.cat_parent_id AS pcat_id, oi.oi_price, oi.oi_super_offer";
        $qr = ""
            . "SELECT $fields "
            . "FROM qmz_product pro "
            . "INNER JOIN qmz_product_categ cat ON cat.cat_id = pro.cat_id "
            . "LEFT JOIN qmz_offer_item oi ON oi.pro_id = pro.pro_id AND oi.oi_date_delete IS NULL AND oi.off_status = 1 "
            . "LEFT JOIN qmz_offer off ON oi.off_id = off.off_id AND off.off_status = 1 AND off_date_delete IS NULL "
            . "WHERE $where AND pro.pro_status = 1 "
            . "ORDER BY oi.oi_super_offer DESC, oi.oi_price DESC, pro.pro_title LIMIT 100";
        $my = new my(dynamic());
        $res = $my->query($qr, $data);
        // FAIL
        if (!@$res[0]) return true;
        // PROCESS
        $res = $this->groupById($res);
        $res = $this->fixData($res);
        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // GET VIP PRODUCTS
    //------------------------------------------------
    public function getVipApp($body)
    {
        //========================
        // QUERY
        //========================
        $fields = "pro.*, off.off_id, cat.cat_title, cat.cat_parent_id AS pcat_id, oi.oi_price, oi.oi_super_offer";
        $qr = ""
            . "SELECT $fields "
            . "FROM qmz_product pro "
            . "INNER JOIN qmz_product_categ cat ON cat.cat_id = pro.cat_id "
            . "LEFT JOIN qmz_offer_item oi ON oi.pro_id = pro.pro_id AND oi.oi_date_delete IS NULL AND oi.off_status = 1 "
            . "LEFT JOIN qmz_offer off ON oi.off_id = off.off_id AND off.off_status = 1 AND off_date_delete IS NULL "
            . "WHERE pro_discount_status > 0 AND pro.pro_status = 1 "
            . "ORDER BY oi.oi_super_offer DESC, oi.oi_price DESC, pro.pro_title LIMIT 100";
        $my = new my(dynamic());
        $res = $my->query($qr);
        // FAIL
        if (!@$res[0]) return true;
        // PROCESS
        $res = $this->groupById($res);
        $res = $this->fixData($res);
        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // GET CATEG. LIST
    //------------------------------------------------
    public function categ($body)
    {
        if (@$body['all']) $where = 'cat_status > 0 AND';
        else $where = 'cat_status = 1 AND';
        //========================
        // QUERY
        //========================
        $qr = ""
            . "SELECT * "
            . "FROM qmz_product_categ "
            . "WHERE $where cat_title IS NOT NULL AND cat_title <> '' "
            . "ORDER BY cat_title";
        $my = new my(dynamic());
        $res = $my->query($qr);
        if (!@$res[0]) return true;

        // PROCESS
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['cat_title'] = mb_ucwords($res[$i]['cat_title']);
        }

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // UPDATE CATEG
    //------------------------------------------------
    public function categPut($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['cat_id']) return false;

        $my = new my(dynamic());
        $res = $my->update('qmz_product_categ', $body, array('cat_id' => $body['cat_id']));

        // RETURN
        return true;
    }
    //------------------------------------------------
    // INSERT CATEG
    //------------------------------------------------
    public function categPost($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        $my = new my(dynamic());
        $id = $my->insert('qmz_product_categ', $body);
        if (!is_numeric($id)) return false;

        // RETURN
        return true;
    }
    //------------------------------------------------
    // GET PRODUCT WITH CATEG
    // -> $body ['cat_id']
    //------------------------------------------------
    public function getByCateg($body)
    {
        //========================
        // QUERY => CATEG
        //========================
        /*$qr = ""
            . "SELECT cat.cat_title, oi.oi_price, oi.oi_super_offer, pro.pro_id, pro.pro_title, pro.pro_price, pro.pro_img, pro.cat_id, pro.pro_price_discount, pro.pro_discount_min, pro.pro_discount_max, pro.pro_notes "
            . "FROM qmz_product pro "
            . "INNER JOIN qmz_product_categ cat ON pro.cat_id = cat.cat_id "
            . "LEFT JOIN qmz_offer_item oi ON oi.pro_id = pro.pro_id "
            . "WHERE pro.cat_id = :cat_id AND pro_status = 1 "
            . "ORDER BY oi.oi_super_offer DESC, oi.oi_price DESC, pro.pro_title LIMIT 50";
            */
        // CAT CHILD OR PARENT?
        $my = new my(dynamic());
        $cat = $my->query("SELECT cat_id, cat_parent_id FROM qmz_product_categ WHERE cat_id = :cat_id AND cat_status = 1", $body);

        // MYSQL FIELDS
        $fields = "pro.*, off.off_id, cat.cat_title, cat.cat_parent_id AS pcat_id, oi.oi_price, oi.oi_super_offer";

        // CAT CHILD
        if ($cat[0]['cat_parent_id']) {
            $where = "pro.cat_id = :cat_id";
        }
        // CAT PARENT
        else {
            // GET CAT CHILDS
            $child = $my->query("SELECT cat_id FROM qmz_product_categ WHERE cat_parent_id = :cat_id AND cat_status = 1", $body);
            $child_list = "";
            for ($i = 0; $i < count($child); $i++) {
                if ($child_list) $child_list .= ',';
                $child_list .= $child[$i]['cat_id'];
            }
            // WHERE
            $where = "cat.cat_id IN ($child_list)";
        }
        // QUERY
        $qr = ""
            . "SELECT $fields "
            . "FROM qmz_product pro "
            . "INNER JOIN qmz_product_categ cat ON cat.cat_id = pro.cat_id "
            . "LEFT JOIN qmz_offer_item oi ON oi.pro_id = pro.pro_id AND oi.oi_date_delete IS NULL AND oi.off_status = 1 "
            . "LEFT JOIN qmz_offer off ON oi.off_id = off.off_id AND off.off_status = 1 AND off_date_delete IS NULL "
            . "WHERE $where AND pro.pro_status = 1 "
            . "ORDER BY oi.oi_super_offer DESC, oi.oi_price DESC, pro.pro_title LIMIT 50";
        //
        $res = $my->query($qr, $body);
        if (!@$res[0]) return true;

        // PROCESS
        $res = $this->groupById($res);
        $res = $this->fixData($res);

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // FIX DATA TO SEND
    //------------------------------------------------
    public static function fixData($res)
    {
        for ($i = 0; $i < count($res); $i++) {
            // prices
            $price = $res[$i]['pro_price'];
            $res[$i]['pro_price_original'] = $price; // bugfix adm pro/form
            if (!@$res[$i]['off_id']) unset($res[$i]['oi_price']); // offer disabled, ignore oi price
            $price_offer = @$res[$i]['oi_price'];
            $price_vip = @$res[$i]['pro_price_discount'];
            $price_old = '';
            // fix data
            $res[$i]['pro_title'] = str_replace('&nbsp;', '', $res[$i]['pro_title']);
            $res[$i]['pro_title'] = mb_ucwords($res[$i]['pro_title']);
            $res[$i]['cat_title'] = mb_ucwords($res[$i]['cat_title']);
            // replace price
            if ($price_offer or ($price_vip and @$res[$i]['pro_discount_status'] > 0)) {
                if ($price_offer) {
                    $price_old = $price;
                    $price = $price_offer;
                }
                if ($price_vip) {
                    $price_old = $price;
                    $price = $price_vip;
                }
                $res[$i]['pro_price'] = $price;
                $res[$i]['pro_price_old'] = $price_old;
            }
        }
        return $res;
    }
    //------------------------------------------------
    // FIX RECEIVED DATA
    //------------------------------------------------
    private function fixBody($body)
    {
        // Fix data
        foreach ($body as $k => $v) {
            if ($v === '') $v = 'NULL';
            if (strpos($k, 'price') > -1) $v = str_replace(',', '.', $v);
            //$body[$k] = utf8_encode($v);
            $body[$k] = $v;
        }
        return $body;
    }
    //------------------------------------------------
    // UPLOAD PRODUCT IMAGE
    //------------------------------------------------
    public function putImage($body)
    {
        global $_AUTH, $_APP;
        if (!@$_AUTH['adm'] or !@$body['pro_img'] or !@$body['pro_id']) return false;

        // DATA
        $data = ['pro_img' => $_APP['URL'] . '/upload/product/' . $body['pro_img']];
        $condition = ['pro_id' => $body['pro_id']];

        // QUERY
        $my = new my(dynamic());
        $my->update("qmz_product", $data, $condition);

        return true;
    }
    public function putAttach($body)
    {
        global $_AUTH, $_APP;
        if (!@$_AUTH['adm'] or !@$body['pro_attach'] or !@$body['pro_id']) return false;

        // DATA
        $data = ['pro_attach' => $_APP['URL'] . '/upload/attach/' . $body['pro_attach']];
        $condition = ['pro_id' => $body['pro_id']];

        // QUERY
        $my = new my(dynamic());
        $my->update("qmz_product", $data, $condition);

        return true;
    }
    //------------------------------------------------
    // HELPER
    //------------------------------------------------
    public function groupById($res)
    {
        // MANUALLY GROUP BY PRO_ID
        $pro = array();
        $ids = array();
        for ($i = 0; $i < count($res); $i++) {
            /*
            // NEXT ITEM IS SAME ID?
            $x = $i + 1;
            $next = @$res[$x];
            if (@$next['pro_id'] === $res[$i]['pro_id']) goto next;
            // APPEND*/
            if (@$ids[$res[$i]['pro_id']]) goto next;
            $ids[$res[$i]['pro_id']] = 1;
            $pro[] = $res[$i];
            next:
        }
        $res = $pro;
        return $res;
    }
    //------------------------------------------------
    // V2 => IMPORT
    //------------------------------------------------
    public function disableAll($body = array())
    {
        global $_AUTH;
        if (!@$_AUTH['api']) return false;

        // QUERY
        $my = new my(dynamic());
        $my->query("UPDATE qmz_product SET pro_status = 0");
        $my->query("UPDATE qmz_product_categ SET cat_status = 0 WHERE cat_status = 1");

        return true;
    }
    public function productImport($body = array())
    {
        global $_AUTH;
        if (!@$_AUTH['api']) return false;
        
        // SHARED DB
        $myShared = new my();
        // DYNAMIC DB
        $my = new my(dynamic());
        // CAT ACTIVE CONTROL (PREVENT REPEAT)
        $cat_active = array();
        // LOOP PRODUCTS
        if (@!$body[0]) {
            $this->error = 'Incorrect JSON Array Format';
            return false;
        }
        if (@$body[100]) {
            $this->error = 'Max 100 Elements';
            return false;
        }
        for ($i = 0; $i < count($body); $i++) {
            //
            $now = date('Y-m-d H:i:s');
            $r = $body[$i];
            // DATA TO INSERT/UPDATE
            $data = array(
                'pro_src_id' => $r['pro_src_id'],
                'pro_src_title' => addslashes($r['pro_src_title']),
                'pro_barcode' => @$r['pro_barcode'],
                'pro_price' => $r['pro_price'],
                'pro_price_cost' => @$r['pro_price_cost'],
                'pro_stock' => $r['pro_stock'],
                'pro_date_update' => $now,
                'pro_status' => 1
            );
            //----------------------------------------------
            // OLD PRODUCT. UPDATE.
            // PRO ALREADY EXISTS IN CLIENT STOCK? UPDATE
            //----------------------------------------------
            $qr = "SELECT p.*, c.cat_parent_id FROM qmz_product p LEFT JOIN qmz_product_categ c ON c.cat_id = p.cat_id WHERE pro_src_id = '{$r['pro_src_id']}'";
            $pro = $my->query($qr);
            $pro_id = @$pro[0]['pro_id'];
            $cat_id = @$pro[0]['cat_id'];
            $cat_parent_id = @$pro[0]['cat_parent_id'];
            if (!$cat_id) $data['pro_status'] = 2;
            else $data['pro_status'] = 1;
            //
            if ($pro_id) {
                $my->update('qmz_product', $data, array('pro_id' => $pro_id));
            }
            //----------------------------------------------
            // NEW PRODUCT. INSERT.
            // PRO DONT EXISTS IN CLIENT STOCK
            //----------------------------------------------
            else {
                // FIND PRO IN 'SHARED DB'
                $qr = "SELECT p.*, c.cat_parent_id FROM qmz_product p LEFT JOIN qmz_product_categ c ON c.cat_id = p.cat_id WHERE pro_barcode = '{$r['pro_barcode']}'";
                $sha = $myShared->query($qr);
                $sha_id = @$sha[0]['pro_id'];
                $cat_id = @$sha[0]['cat_id'];
                $cat_parent_id = @$sha[0]['cat_parent_id'];
                //
                // EXISTS IN SHARED DB. 
                if ($sha_id) {
                    $data['pro_qmz_id'] = $sha_id;
                    $data['cat_id'] = $sha[0]['cat_id'];
                    //if (!$data['cat_id']) unset($data['cat_id']); // null bugfix
                    $data['pro_title'] = addslashes($sha[0]['pro_title']);
                    $data['pro_img'] = $sha[0]['pro_img'];
                    $data['pro_barcode'] = $sha[0]['pro_barcode'];
                    $data['pro_status'] = 1;
                } else {
                    // DONT EXIST IN 'SHARED DB' & OFERTAS. FILL FIELDS WITH SRC DATA.
                    $data['pro_title'] = addslashes($r['pro_src_title']);
                    $data['pro_status'] = 2; // PENDENTE
                }
                $data['pro_date_insert'] = $now;

                // INSERT
                $pro_id = $my->insert('qmz_product', $data);
            }
            // UPDATE CATEG STATUS
            if (!in_array($cat_id, $cat_active) and $cat_id > 0) {
                $cat_active[] = $cat_id;
                $my->update('qmz_product_categ', ['cat_status' => 1], ['cat_id' => $cat_id]);
                $my->update('qmz_product_categ', ['cat_status' => 1], ['cat_id' => $cat_parent_id]);
            }
        } // for
        return true;
    }
}
