<?php

class delivery
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // POST NEW DELIVERY AREA
    //------------------------------------------------
    public function post($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['city_id']) return false;

        // CHECK IF EXISTS
        $my = new my(dynamic());
        if (@$body['district_id']) $res = $my->query("SELECT * FROM qmz_delivery WHERE district_id = :district_id AND del_date_delete IS NULL", $body);
        else $res = $my->query("SELECT * FROM qmz_delivery WHERE city_id = :city_id AND district_id IS NULL AND del_date_delete IS NULL", $body);
        
        // ERROR
        if (!empty($res)) {
            $this->error = "Esta região já está definida.";
            return false;
        }

        // DATE
        unset($body['state_id']);
        $body['del_date_insert'] = date("Y-m-d H:i:s");

        // INSERT
        $id = $my->insert('qmz_delivery', $body);
        return true;
    }
    //------------------------------------------------
    // GET DELIVERY AREAS
    //------------------------------------------------
    public function get($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        // QUERY
        $my = new my(dynamic());
        $qr = "SELECT del.del_id, del.del_tax, del.del_min_value, s.state_uf, c.city, d.district "
            . "FROM qmz_delivery del "
            . "INNER JOIN mirror_geo_city c ON c.city_id = del.city_id "
            . "INNER JOIN mirror_geo_state s ON s.state_id = c.state_id "
            . "LEFT JOIN mirror_geo_district d ON d.district_id = del.district_id "
            . "WHERE del_date_delete IS NULL";
        $res = $my->query($qr);

        // RETURN
        $this->return = $res;
        return true;
    }
    //------------------------------------------------
    // DELETE DELIVERY AREA
    //------------------------------------------------
    public function delete($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['del_id']) return false;

        // DATE
        $now = date("Y-m-d H:i:s");

        // QUERY
        $my = new my(dynamic());
        $my->update("qmz_delivery", ['del_date_delete' => $now], ['del_id' => $body['del_id']]);
        return true;
    }
}
