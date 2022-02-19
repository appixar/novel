<?php

class offer
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // GET
    // -> $body [*off_id]
    //------------------------------------------------
    public function get($body)
    {
        global $_AUTH;
        if (!@$body['off_id']) return false;

        // QUERY
        $my = new my(dynamic());
        $offer = $my->query("SELECT * FROM qmz_offer WHERE off_id = :off_id", $body);
        $item = $my->query("SELECT oi.*, p.pro_title, p.pro_src_id, p.pro_price FROM qmz_offer_item oi INNER JOIN qmz_product p ON p.pro_id = oi.pro_id WHERE oi.off_id = :off_id AND oi_date_delete IS NULL", $body);
        if (!@$offer[0]) return true;

        // PROCESS
        $res['offer'] = $offer[0];
        $res['item'] = $item;

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // GET ALL
    //------------------------------------------------
    public function getAll()
    {
        global $_AUTH;

        // QUERY
        $my = new my(dynamic());
        $res = $my->query("SELECT o.*, COUNT(oi.oi_id) AS items FROM qmz_offer o LEFT JOIN qmz_offer_item oi ON oi.off_id = o.off_id WHERE oi.oi_date_delete IS NULL AND o.off_date_delete IS NULL GROUP BY o.off_id");
        if (!@$res[0]) return true;

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // POST
    // -> $body [offer]
    // -> $body [item]
    //------------------------------------------------
    public function post($body)
    {
        global $_AUTH;

        $now = date('Y-m-d H:i:s');

        // INSERT OFFER
        $body['off_date_insert'] = $now;
        //
        $my = new my(dynamic());
        $off_id = $my->insert('qmz_offer', $body);
        if (!$off_id) return false;

        // INSERT ITEMS
        /*$item = $body['item'];
        for ($i = 0; $i < count($item); $i++) {
            $item[$i]['off_id'] = $off_id;
            $item[$i]['oi_date_insert'] = $now;
            //
            $oi_id = $my->insert('qmz_offer_item', $item[$i]);
        }*/

        // RETURN
        $this->return = $off_id;
        return true;
    }
    //------------------------------------------------
    // PUT
    // -> $body [*off_id]
    //------------------------------------------------
    public function put($body)
    {
        global $_AUTH;
        if (!@$body['off_id']) return false;

        $body['off_date_update'] = date('Y-m-d H:i:s');

        // UPDATE OFFER
        $my = new my(dynamic());
        $my->update('qmz_offer', $body, array('off_id' => $body['off_id']));
        
        // UPDATE OFFER ITEM / OFFER STATUS (EASIEST QUERIES)
        $my->query("UPDATE qmz_offer_item SET off_status = :off_status WHERE off_id = :off_id", $body);

        // RETURN
        return true;
    }
    //------------------------------------------------
    // POST ITEM
    // -> $body [offer_id, oi_ ...]
    //------------------------------------------------
    public function postItem($body)
    {
        global $_AUTH;
        if (!@$body['off_id']) return false;

        $data = array(
            'off_id' => $body['off_id'],
            'off_status' => 0, // will be 1 after save form
            'pro_id' => $body['pro_id'],
            'oi_price' => floatval($body['oi_price']),
            'oi_super_offer' => $body['oi_super_offer'],
            'oi_show_home' => $body['oi_show_home'],
            'oi_date_insert' => date('Y-m-d H:i:s')
        );

        // QUERY
        $my = new my(dynamic());
        $check = $my->query('SELECT oi_id FROM qmz_offer_item WHERE off_id = :off_id AND pro_id = :pro_id', $body);
        if (!empty($check)) $my->update('qmz_offer_item', $data, array('oi_id' => $check[0]['oi_id']));
        else $my->insert('qmz_offer_item', $data);

        // RETURN
        return true;
    }
    //------------------------------------------------
    // DELETE ITEM
    // -> $body [oi_id]
    //------------------------------------------------
    public function deleteItem($body)
    {
        global $_AUTH;
        if (!@$body['oi_id']) return false;

        // QUERY
        $my = new my(dynamic());
        $my->update('qmz_offer_item', array('oi_date_delete' => date('Y-m-d H:i:s')), array('oi_id' => $body['oi_id']));

        // RETURN
        return true;
    }
}
