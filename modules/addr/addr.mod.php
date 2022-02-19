<?php

class addr
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // UPDATE
    // -> $body [*addr_id]
    //------------------------------------------------
    public function put($body)
    {
        global $_AUTH;
        if (!@$body['addr_id']) return false;

        // check addr_id owner
        $my = new my(dynamic());
        $check = $my->query("SELECT addr_id FROM qmz_user_address WHERE addr_id=:addr_id and user_id = '{$_AUTH['user']['user_id']}'", $body);
        if (!@$check[0]) {
            $this->error = 'User ID != Addr ID';
            return false;
        }

        // util
        $now = date('Y-m-d H:i:s');

        // data
        $data = array(
            'state_id' => $body['state_id'],
            'city_id' => $body['city_id'],
            'district_id' => $body['district_id'],
            'addr_street' => trim(mb_ucwords($body['addr_street'])),
            'addr_number' => @$body['addr_number'],
            'addr_refer' => @$body['addr_refer'],
            'addr_cep' => @$body['addr_cep'],
            //
            'addr_status' => 1,
            'addr_date_update' => $now
        );
        // save data
        $my->update('qmz_user_address', $data, array('addr_id' => $body['addr_id']));
        return true;
    }
    //------------------------------------------------
    // DELETE
    // -> $body [*addr_id]
    //------------------------------------------------
    public function delete($body)
    {
        global $_AUTH;
        if (!@$body['addr_id']) return false;

        // check addr_id owner
        $my = new my(dynamic());
        $check = $my->query("SELECT addr_id FROM qmz_user_address WHERE addr_id=:addr_id and user_id = '{$_AUTH['user']['user_id']}'", $body);
        if (!@$check[0]) {
            $this->error = 'User ID != Addr ID';
            return false;
        }

        // util
        $now = date('Y-m-d H:i:s');

        // data
        $data = array(
            'addr_status' => 0,
            'addr_date_delete' => $now
        );
        // save data
        $my->update('qmz_user_address', $data, array('addr_id' => $body['addr_id']));
        return true;
    }
    //------------------------------------------------
    // GET USER ADDR
    //------------------------------------------------
    public function get()
    {
        global $_AUTH;
        $my = new my(dynamic());

        $user_id = $_AUTH['user']['user_id'];
        $qr = "" .
            "SELECT a.addr_id, a.addr_street, a.addr_number, a.district_id, d.district, a.city_id, c.city, a.state_id, s.state, a.addr_refer FROM qmz_user_address a " .
            "INNER JOIN mirror_geo_state s ON a.state_id = s.state_id " .
            "INNER JOIN mirror_geo_city c ON a.city_id = c.city_id " .
            "INNER JOIN mirror_geo_district d ON a.district_id = d.district_id " .
            "WHERE a.user_id = '$user_id' AND a.addr_status = 1";
        $res = $my->query($qr);

        // delivery tax
        for ($i = 0; $i < count($res); $i++) {
            // district tax
            $tax = $my->query("SELECT del_tax, del_min_value FROM qmz_delivery WHERE del_date_delete IS NULL AND district_id = '{$res[$i]['district_id']}'");
            if (@$tax[0]) {
                $res[$i]['tax_title'] = @$res[$i]['district'];
                $res[$i]['tax'] = @$tax[0]['del_tax'];
                $res[$i]['min_value'] = @$tax[0]['del_min_value'];
            }
            // city tax
            else {
                $tax = $my->query("SELECT del_tax, del_min_value FROM qmz_delivery WHERE del_date_delete IS NULL AND city_id = '{$res[$i]['city_id']}'");
                $res[$i]['tax_title'] = @$res[$i]['city'];
                $res[$i]['tax'] = @$tax[0]['del_tax'];
                $res[$i]['min_value'] = @$tax[0]['del_min_value'];
            }
        }

        // return
        $this->return = $res;
        return true;
    }
    //------------------------------------------------
    // GET DISTRICT LIST
    // -> $body ['city_id']
    //------------------------------------------------
    public function district($body)
    {
        // QUERY
        $my = new my(dynamic());
        $res = $my->query("SELECT district_id,district FROM mirror_geo_district WHERE city_id = :city_id ORDER BY district", $body);
        // UTF8 FIX
        //for ($i = 0; $i < count($res); $i++) $res[$i]['district'] = utf8_encode($res[$i]['district']);
        // RETURN
        $this->return = $res;
        return true;
    }
    //------------------------------------------------
    // GET CITY LIST
    // -> $body ['state_id']
    //------------------------------------------------
    public function city($body)
    {
        // QUERY
        $my = new my(dynamic());
        $res = $my->query("SELECT city_id,city FROM mirror_geo_city WHERE state_id = :state_id ORDER BY city", $body);
        // UTF8 FIX
        //for ($i = 0; $i < count($res); $i++) $res[$i]['city'] = utf8_encode($res[$i]['city']);
        // RETURN
        $this->return = $res;
        return true;
    }
    //------------------------------------------------
    // SEARCH ADDRESS BY CEP (VIACEP API)
    // -> $body ['addr_cep']
    //------------------------------------------------
    public function cep($body)
    {
        // DYN DB
        $my = new my(dynamic());

        // DATA
        $cep = $body['addr_cep'];
        if (strlen($cep) != 9) http::die(400, "Formato inválido: $cep");

        // GET VIACEP
        $link = "https://viacep.com.br/ws/$cep/json";
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (!@$data['uf']) {
            $this->error = 'Não foi possível localizar o seu CEP.';
            return false;
        }

        // TRY INDEX DATA
        $state = $my->query("SELECT * FROM mirror_geo_state WHERE state_uf = '{$data['uf']}'");
        $city = $my->query("SELECT * FROM mirror_geo_city WHERE city = '{$data['localidade']}' AND state_id='{$state[0]['state_id']}'");
        if (@$city[0] and $data['bairro']) $dist = $my->query("SELECT * FROM mirror_geo_district WHERE district = '{$data['bairro']}' AND city_id = '{$city[0]['city_id']}'");

        // REGISTER VIACEP DISTRICT IF DONT EXISTS IN DB
        if (@$city[0] and !@$dist[0] and $data['bairro']) {
            $ins = array(
                'city_id' => $city[0]['city_id'],
                'district' => $data['bairro']
            );
            $dist[0]['district_id'] = $my->insert('mirror_geo_district', $ins);
        }
        if (!@$dist[0]['district_id']) $dist[0]['district_id'] = '';

        // SET RETURN
        $addr = array(
            'addr_cep' => $cep,
            'state_id' => $state[0]['state_id'],
            'state' => $state[0]['state'],
            'city_id' => $city[0]['city_id'],
            'city' => $city[0]['city'],
            'district_id' => @$dist[0]['district_id'],
            'district' => @$dist[0]['district'],
            'addr_street' => $data['logradouro'],
            //
        );

        // SEND RETURN
        $this->return = $addr;
        return true;
    }
    //------------------------------------------------
    // INSERT
    //------------------------------------------------
    public function post($body)
    {
        global $_AUTH;

        // data
        $now = date('Y-m-d H:i:s');
        $data = array(
            'user_id' => $_AUTH['user']['user_id'],
            'addr_cep' => @$body['addr_cep'],
            'state_id' => $body['state_id'],
            'city_id' => $body['city_id'],
            'district_id' => $body['district_id'],
            'addr_street' => trim(mb_ucwords($body['addr_street'])),
            'addr_number' => @$body['addr_number'],
            'addr_refer' => @$body['addr_refer'],
            //
            'addr_status' => 1,
            'addr_date_insert' => $now
        );
        // save data
        $my = new my(dynamic());
        $id = $my->insert('qmz_user_address', $data);
        if (!is_numeric($id)) {
            $this->error = 'Desculpe, ocorreu um erro ao inserir o endereço.';
            return false;
        }
        //$this->return = $id;
        return true;
    }
}
