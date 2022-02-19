<?php

class conf
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // UPDATE CONF
    //------------------------------------------------
    public function put($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        // QUERY
        $my = new my(dynamic());
        $my->update('qmz_config', $body, array('conf_id' => 1));
        return true;
    }
    //------------------------------------------------
    // DELETE DELIVERY AREA
    //------------------------------------------------
    public function logoPut($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        // CHECK
        if (@$body['logo_lg']) $upd = ['logo_lg' => $body['logo_lg']];
        elseif (@$body['logo_md']) $upd = ['logo_md' => $body['logo_md']];
        elseif (@$body['logo_mono']) $upd = ['logo_mono' => $body['logo_mono']];
        else return false;

        // QUERY
        $my = new my(dynamic());
        $res = $my->query("SELECT conf_json_img FROM qmz_config WHERE conf_id = 1");

        // PROCESS
        $data = json_decode($res[0]['conf_json_img'], true);
        $data = array_merge($data, $upd);
        $data = json_encode($data);
        $my->update("qmz_config", ['conf_json_img' => $data], ['conf_id' => 1]);

        return true;
    }
}
