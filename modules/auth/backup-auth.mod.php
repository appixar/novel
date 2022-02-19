<?php

class auth
{
    public function __construct($rules = array())
    {
        global $_HEADER, $_APP, $_AUTH, $_BODY;
        $_AUTH = false;

        // Default rules
        if (!isset($rules['required'])) $rules['required'] = true;
        if (!isset($rules['auth_post'])) $rules['auth_post'] = false;

        //---------------------------------------------------------
        // GET HEADER DATA
        // -> Authorization: adm jucy 543210
        //---------------------------------------------------------
        $my = new my();

        // CONFIG
        $auth_tables = array(
            'user' => 'qmz_user:user_key:dynamic',
            'adm' => 'qmz_admin:adm_key:0',
            'god' => 'qmz_god:god_key:0:god',
        );

        // HEADER AUTH DATA
        if (!$rules['auth_post']) {
            if (!@$_HEADER['authorization'] and $rules['required']) http::die(401);
            if (!@$_HEADER['authorization'] and !$rules['required']) return true;
            $auth_header = explode(":", $_HEADER['authorization']);
        }
        // $_POST AUTH DATA
        else {
            if (!@$_POST['auth']) http::die(401);
            $auth_header = explode(":", $_POST['auth']);
        }

        $auth = array(
            'com_code' => @$auth_header[1], // <jucy>
            'table' => @explode(":", $auth_tables[$auth_header[0]])[0], // qmz_user
            'key_name' => @explode(":", $auth_tables[$auth_header[0]])[1], // user_key
            'key_value' => @$auth_header[2], // <token>
            'prefix' => @explode("_", explode(":", $auth_tables[$auth_header[0]])[1])[0], // user_
            'db' => @$auth_header[2], // <jucy>
            'god' => @explode(":", $auth_tables[$auth_header[0]])[3], // asterisk
        );
        if (!@$auth['table'] or !@$auth['key_name']) http::die(401);
        
        //---------------------------------------------------------
        // GET COMPANY DATA
        // -> ALWAYS REQUIRED IF IS NOT A GOD
        //---------------------------------------------------------
        // NO COMPANY IN HEADER
        if ($auth['com_code'] === '*') {
            // COMPANY IS OPTIONAL ONLY FOR GODS
            if (!$auth['god']) http::die(401, 'Just a human');
        }
        // COMPANY FOUND
        else {
            $com = $my->query("SELECT * FROM qmz_company WHERE com_code = :com_code", $auth);
            if (!@$com[0]) http::die(401, 'Invalid company');
            if (@$com[0]['com_status'] != 1) http::die(401, 'Disabled company');
        }

        //---------------------------------------------------------
        // GET USER DATA
        //---------------------------------------------------------
        // DYNAMIC CONF
        $myConf = ['id' => 'dynamic', 'wildcard' => $auth['com_code']];

        // GOD AUTH
        if ($auth['god']) {
            $user = $my->query("SELECT * FROM {$auth['table']} WHERE {$auth['key_name']} = :key_value", $auth);
        }
        // NORMAL AUTH: DYNAMIC DB
        else {
            $myDynamic = new my($myConf);
            // QUERY
            $user = $myDynamic->query("SELECT * FROM {$auth['table']} WHERE {$auth['key_name']} = :key_value", $auth);
        }
        // VERIFY 
        if (!@$user[0] and $rules['required']) http::die(401, 'Invalid key');
        if (!@$user[0] and $auth['god']) http::die(401, 'GOD? Really? ¬¬');
        if (@$user[0]["{$auth['prefix']}_status"] != 1 and $rules['required']) http::die(401, 'Disabled account');

        // COMPARE USER COM_ID AND COMPANY ID
        if (
            (@$com[0]['com_id'] !== @$user[0]['com_id'])
            and !$auth['god']
            and $rules['required']
        ) http::die(401, 'User != Company');

        // HIDE PASS
        unset($user[0]["{$auth['prefix']}_pass"]);
        unset($user[0]["{$auth['prefix']}_password"]);

        //---------------------------------------------------------
        // GET COMPANY-CONFIG DATA
        // -> ALWAYS REQUIRED IF IS NOT A GOD
        //---------------------------------------------------------
        if ($auth['com_code'] !== '*') {
            if (!@$myDynamic) $myDynamic = new my($myConf);
            $conf = $myDynamic->query("SELECT * FROM qmz_config WHERE conf_id = 1");
        }

        //---------------------------------------------------------
        // RETURN USER + COMPANY + CONFIG DATA
        //---------------------------------------------------------
        $res[$auth['prefix']] = @$user[0];

        // APPEND COMPANY DATA
        if (@$com[0]) foreach ($com[0] as $k => $v) $res['com'][$k] = $v;

        // APPEND CONFIG DATA
        if (@$conf[0]) foreach ($conf[0] as $k => $v) $res['conf'][$k] = $v;

        // RETURN
        $_AUTH = $res;
        return $res;
    }
}
