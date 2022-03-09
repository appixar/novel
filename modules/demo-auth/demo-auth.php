<?php

class auth
{
    public function __construct($rules = array())
    {
        global $_HEADER, $_APP, $_AUTH, $_BODY;
        $_AUTH = false;

        // Default rules
        if (!isset($rules['required'])) $rules['required'] = true;
        if (!isset($rules['auth_post'])) $rules['auth_post'] = false; // AUTH DATA IN POST (NOT IN HEADER). UTIL FOR FORMS.

        //---------------------------------------------------------
        // GET HEADER DATA
        // -> Authorization: adm:jucy:543210
        //---------------------------------------------------------
        $my = new my();

        // HEADER AUTH DATA
        if (!$rules['auth_post']) {
            if (!@$_HEADER['authorization'] and $rules['required']) http::die(401);
            if (!@$_HEADER['authorization'] and !$rules['required']) return true;
            $header = explode(":", $_HEADER['authorization']);
        }
        // $_POST AUTH DATA
        else {
            if (!@$_POST['auth']) {
                http::die(401);
            }
            $header = explode(":", $_POST['auth']);
        }
        $auth = array();

        //-----------------------------------------------
        // USER AUTH DATA
        //-----------------------------------------------
        if ($header[0] === 'user') {
            $auth = array(
                // user
                'table' => 'qmz_user',
                'field_key' => 'user_key',
                'field_status' => 'user_status',
                'key' => @$header[2],
                // company
                'com_code' => @$header[1],
                'db' => 'dynamic',
                'db_wildcard' => $header[1]
            );
        }
        //-----------------------------------------------
        // ADMIN AUTH DATA
        //-----------------------------------------------
        if ($header[0] === 'adm') {
            $auth = array(
                // user
                'table' => 'qmz_admin',
                'field_key' => 'adm_key',
                'field_status' => 'adm_status',
                'key' => @$header[2],
                // company
                'com_code' => @$header[1],
                'db' => 0,
                'db_wildcard' => false
            );
        } elseif ($header[0] === 'api') {
            $auth = array(
                // user
                'table' => 'qmz_company',
                'field_key' => 'com_key',
                'field_status' => 'com_status',
                'key' => @$header[2],
                // company
                'com_code' => @$header[1],
                'db' => 0,
                'db_wildcard' => false
            );
        }
        if (empty($auth)) http::die(401);

        //---------------------------------------------------------
        // GET COMPANY DATA
        // -> ALWAYS REQUIRED IF IS NOT A GOD
        //---------------------------------------------------------
        // NO COMPANY IN HEADER
        if ($auth['com_code'] === '*') {
            // COMPANY IS OPTIONAL ONLY FOR ADMIN
            if ($header[0] !== 'adm') http::die(401, 'Users needs a company');
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
        $myConf = ['id' => $auth['db'], 'wildcard' => $auth['com_code']];
        $my = new my($myConf);
        if ($auth['key'] and $auth['key'] !== 'undefined') {
            $user = $my->query("SELECT * FROM {$auth['table']} WHERE {$auth['field_key']} = :key", $auth);
        }

        // VERIFY 
        if (!@$user[0] and $rules['required']) http::die(401, 'Invalid key');
        if (@$user[0]["{$auth['field_status']}"] != 1 and $rules['required']) http::die(401, 'Disabled account');
        if ($header[0] === 'api') {
            if (@$com[0]['com_code'] != $user[0]['com_code']) http::die(401, 'Key != Company');
        }

        //---------------------------------------------------------
        // GET COMPANY-CONFIG DATA
        // -> ALWAYS REQUIRED IF IS NOT A GOD
        //---------------------------------------------------------
        if (@$com[0]) {
            $myConf = ['id' => 'dynamic', 'wildcard' => $auth['com_code']];
            $my = new my($myConf);
            $conf = $my->query("SELECT * FROM qmz_config WHERE conf_id = 1");
        }

        //---------------------------------------------------------
        // RETURN USER + COMPANY + CONFIG DATA
        //---------------------------------------------------------
        if (@$user[0]) $res[$header[0]] = @$user[0];

        // APPEND COMPANY DATA
        if (@$com[0]) foreach ($com[0] as $k => $v) $res['com'][$k] = $v;

        // APPEND CONFIG DATA
        if (@$conf[0]) foreach ($conf[0] as $k => $v) $res['conf'][$k] = $v;

        // RETURN
        $_AUTH = $res;
        return $res;
    }
}
